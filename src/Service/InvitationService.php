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
     * @return array{0: bool, 1: ?string}
     */
    public function canSendInvitation(Team $team, Player $captain, Player $target): array
    {
        // Anti-spam: max 5/day per captain
        if ($this->invitationRepository->countSentToday($captain) >= 5) {
            return [false, 'Anti-spam : maximum 5 invitations par jour.'];
        }

        if (!$team->hasAvailableSlot()) {
            return [false, 'Équipe complète (max joueurs atteint).'];
        }

        // For LoL/Valorant: player cannot already be in a team
        if (in_array($team->getGame(), ['lol', 'valorant'], true) && $target->getTeam() !== null) {
            return [false, 'Ce joueur est déjà dans une équipe.'];
        }

        // Same game required
        if ($target->getGame() !== $team->getGame()) {
            return [false, 'Le joueur n’est pas sur le même jeu que l’équipe.'];
        }

        // Already invited
        $existing = $this->invitationRepository->findPending($team, $target);
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
            $invitation->setStatus(Invitation::STATUS_DECLINED);
            $this->em->flush();
        }
    }
}
