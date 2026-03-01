<?php

namespace App\Service;

use App\Entity\Invitation;
use App\Entity\Player;
use App\Entity\Team;
use App\Repository\InvitationRepository;
use Doctrine\ORM\EntityManagerInterface;

class InvitationService
{
    public function __construct(
        private readonly InvitationRepository $invitationRepository,
        private readonly EntityManagerInterface $em,
    ) {}

    /**
     * Business rules:
     * - captain can invite only players without a team (LoL/Valorant)
     * - anti-spam: max 5 invitations / 24h / team
     * - cannot invite if team is full
     * - cannot have multiple pending invitations from same team
     * - invitations expire after 7 days (set at creation)
     *
     * @return array{0: bool, 1: ?string}
     */
    public function canSendInvitation(Team $team, Player $captain, Player $target): array
    {
        if (!$team->hasAvailableSlot()) {
            return [false, 'Équipe complète (max joueurs atteint).'];
        }

        // anti-spam team wide
        if ($this->invitationRepository->countSentLast24hForTeam($team) >= 5) {
            return [false, 'Anti-spam : maximum 5 invitations par équipe sur 24h.'];
        }

        // same game required
        if ($target->getGame() !== $team->getGame()) {
            return [false, 'Le joueur n’est pas sur le même jeu que l’équipe.'];
        }

        // For LoL/Valorant: player cannot already be in a team
        if (in_array($team->getGame(), ['lol', 'valorant'], true) && $target->getTeam() !== null) {
            return [false, 'Ce joueur est déjà dans une équipe.'];
        }

        // no duplicate pending invitation for same team/player
        $existing = $this->invitationRepository->findPendingForTeamAndPlayer($team, $target);
        if ($existing && !$existing->isExpired()) {
            return [false, 'Invitation déjà en attente pour ce joueur.'];
        }

        return [true, null];
    }

    public function sendInvitation(Team $team, Player $captain, Player $target): Invitation
    {
        $inv = new Invitation();
        $inv->setTeam($team);
        $inv->setInvitedBy($captain);
        $inv->setPlayer($target);
        $inv->setStatus(Invitation::STATUS_PENDING);

        $this->em->persist($inv);
        $this->em->flush();

        return $inv;
    }

    public function expireIfNeeded(Invitation $invitation): void
    {
        if ($invitation->getStatus() !== Invitation::STATUS_PENDING) {
            return;
        }

        if ($invitation->isExpired()) {
            $invitation->setStatus(Invitation::STATUS_EXPIRED);
            $this->em->flush();
        }
    }

    /**
     * On accept:
     * - add player to team
     * - mark invitation accepted
     * - decline all other pending invitations for that player
     */
    public function acceptInvitation(Invitation $invitation, Player $player): void
    {
        $this->expireIfNeeded($invitation);

        if ($invitation->getStatus() !== Invitation::STATUS_PENDING) {
            throw new \RuntimeException('Invitation non valide.');
        }

        $team = $invitation->getTeam();
        if (!$team || !$team->hasAvailableSlot()) {
            throw new \RuntimeException('Équipe complète.');
        }

        if (in_array($team->getGame(), ['lol', 'valorant'], true) && $player->getTeam() !== null) {
            throw new \RuntimeException('Vous êtes déjà dans une équipe.');
        }

        $player->setTeam($team);
        $invitation->setStatus(Invitation::STATUS_ACCEPTED);

        // decline all other pending invitations
        foreach ($this->invitationRepository->findOtherPendingForPlayer($player, $invitation) as $other) {
            $this->expireIfNeeded($other);
            if ($other->getStatus() === Invitation::STATUS_PENDING) {
                $other->setStatus(Invitation::STATUS_DECLINED);
            }
        }

        $this->em->flush();
    }

    public function declineInvitation(Invitation $invitation, Player $player): void
    {
        $this->expireIfNeeded($invitation);

        if ($invitation->getStatus() !== Invitation::STATUS_PENDING) {
            throw new \RuntimeException('Invitation non valide.');
        }

        $invitation->setStatus(Invitation::STATUS_DECLINED);
        $this->em->flush();
    }

    public function cancelInvitation(Invitation $invitation, Team $team): void
    {
        if ($invitation->getTeam()?->getId() !== $team->getId()) {
            throw new \RuntimeException('Invitation invalide.');
        }

        $this->expireIfNeeded($invitation);

        if ($invitation->getStatus() !== Invitation::STATUS_PENDING) {
            throw new \RuntimeException('Impossible d’annuler: invitation non en attente.');
        }

        $invitation->setStatus(Invitation::STATUS_DECLINED);
        $this->em->flush();
    }
}
