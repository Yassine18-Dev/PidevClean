<?php

namespace App\Service;

use App\Entity\Invitation;
use App\Entity\Player;
use App\Entity\Team;
use App\Repository\InvitationRepository;
use Doctrine\ORM\EntityManagerInterface;

class InvitationService
{
    public const DAILY_LIMIT = 10;
    public const EXPIRY_DAYS = 7;

    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly InvitationRepository $invitationRepo,
    ) {}

    /**
     * ✅ SCÉNARIO A : Captain invite un joueur
     */
    public function captainInvitesPlayer(Team $team, Player $player, Player $captain): Invitation
    {
        $this->validateCanInvite($team, $player);

        $invitation = new Invitation();
        $invitation->setTeam($team);
        $invitation->setPlayer($player);
        $invitation->setInvitedBy($captain);
        $invitation->setType(Invitation::TYPE_INVITATION);
        $invitation->setStatus(Invitation::STATUS_PENDING);

        $this->em->persist($invitation);
        $this->em->flush();

        return $invitation;
    }

    /**
     * ✅ SCÉNARIO B : Joueur demande à rejoindre une équipe
     */
    public function playerAppliesToTeam(Player $player, Team $team): Invitation
    {
        $this->validateCanApply($player, $team);

        $invitation = new Invitation();
        $invitation->setTeam($team);
        $invitation->setPlayer($player);
        $invitation->setInvitedBy($player);
        $invitation->setType(Invitation::TYPE_CANDIDATURE);
        $invitation->setStatus(Invitation::STATUS_PENDING);

        $this->em->persist($invitation);
        $this->em->flush();

        return $invitation;
    }

    /**
     * ✅ Action commune : Accepter (soit le joueur accepte l'invitation, soit le capitaine accepte la candidature)
     */
    public function accept(Invitation $invitation, Player $actor): void
    {
        if ($invitation->getStatus() !== Invitation::STATUS_PENDING) {
            throw new \RuntimeException('Cette invitation n\'est plus active.');
        }

        $player = $invitation->getPlayer();
        $team = $invitation->getTeam();

        // Sécurité : Seul le joueur concerné ou le capitaine de l'équipe peut accepter selon le type
        if ($invitation->getType() === Invitation::TYPE_INVITATION) {
            if ($actor->getId() !== $player->getId()) {
                throw new \RuntimeException('Seul le joueur invité peut accepter.');
            }
        } else {
            // Candidature : Seul le capitaine (owner de la team) peut accepter
            if (!$team->getOwner() || $actor->getUser()?->getId() !== $team->getOwner()->getId()) {
                throw new \RuntimeException('Seul le capitaine de l\'équipe peut accepter une candidature.');
            }
        }

        // Validations finales
        if ($player->getTeam()) {
            throw new \RuntimeException('Le joueur a déjà une équipe.');
        }
        if (!$team->hasAvailableSlot()) {
            throw new \RuntimeException('L\'équipe est complète.');
        }

        // Exécution
        $invitation->setStatus(Invitation::STATUS_ACCEPTED);
        $player->setTeam($team);

        // Auto-décliner les autres invitations en attente pour ce joueur
        foreach ($this->invitationRepo->findOtherPendingInvitesForPlayer($player, $invitation) as $other) {
            $other->setStatus(Invitation::STATUS_DECLINED);
        }

        $this->em->flush();
    }

    /**
     * ✅ Action commune : Refuser
     */
    public function decline(Invitation $invitation, Player $actor): void
    {
        if ($invitation->getStatus() !== Invitation::STATUS_PENDING) {
            return;
        }

        // Logique de permission identique à accept
        $this->validatePermission($invitation, $actor);

        $invitation->setStatus(Invitation::STATUS_DECLINED);
        $this->em->flush();
    }

    private function validateCanInvite(Team $team, Player $player): void
    {
        if (!$team->hasAvailableSlot()) {
            throw new \RuntimeException('L\'équipe est déjà complète (5/5).');
        }
        if ($player->getTeam()) {
            throw new \RuntimeException('Ce joueur appartient déjà à une équipe.');
        }
        if ($this->invitationRepo->hasPendingInvite($team, $player, Invitation::TYPE_INVITATION)) {
            throw new \RuntimeException('Une invitation est déjà en attente pour ce joueur.');
        }
    }

    private function validateCanApply(Player $player, Team $team): void
    {
        if ($player->getTeam()) {
            throw new \RuntimeException('Vous avez déjà une équipe.');
        }
        if (!$team->hasAvailableSlot()) {
            throw new \RuntimeException('Cette équipe est complète.');
        }
        if ($this->invitationRepo->hasPendingInvite($team, $player, Invitation::TYPE_CANDIDATURE)) {
            throw new \RuntimeException('Vous avez déjà une demande en attente pour cette équipe.');
        }
    }

    private function validatePermission(Invitation $invitation, Player $actor): void
    {
        if ($invitation->getType() === Invitation::TYPE_INVITATION) {
            // Qui peut refuser ? Le joueur ou le capitaine qui a changé d'avis
            // Pour simplifier, on laisse le joueur refuser.
            if ($actor->getId() !== $invitation->getPlayer()->getId() && 
                ($invitation->getTeam()->getOwner() && $actor->getUser()?->getId() !== $invitation->getTeam()->getOwner()->getId())) {
                throw new \RuntimeException('Permission refusée.');
            }
        } else {
            // Candidature : Le joueur peut annuler sa propre demande, ou le capitaine peut refuser.
             if ($actor->getId() !== $invitation->getPlayer()->getId() && 
                ($invitation->getTeam()->getOwner() && $actor->getUser()?->getId() !== $invitation->getTeam()->getOwner()->getId())) {
                throw new \RuntimeException('Permission refusée.');
            }
        }
    }

    public function expireOldInvitations(): int
    {
        $threshold = (new \DateTimeImmutable('now'))->sub(new \DateInterval('P' . self::EXPIRY_DAYS . 'D'));
        $expired = 0;
        foreach ($this->invitationRepo->findPendingOlderThan($threshold) as $inv) {
            $inv->setStatus(Invitation::STATUS_EXPIRED);
            $expired++;
        }
        if ($expired > 0) $this->em->flush();
        return $expired;
    }

    public function expiresInDays(Invitation $inv): int
    {
        $expiresAt = $inv->getExpiresAt() ?? $inv->getCreatedAt()->modify('+' . self::EXPIRY_DAYS . ' days');
        $diff = (new \DateTimeImmutable('now'))->diff($expiresAt);
        return $expiresAt < new \DateTimeImmutable('now') ? 0 : (int) $diff->format('%a');
    }
}