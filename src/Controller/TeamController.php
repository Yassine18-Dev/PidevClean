<?php

namespace App\Controller;

use App\Entity\Team;
use App\Repository\InvitationRepository;
use App\Repository\PlayerRepository;
use App\Service\InvitationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Entity\Invitation;

class TeamController extends AbstractController
{
    #[Route('/team/{id}', name: 'team_show', methods: ['GET'])]
    public function show(Team $team): Response
    {
        return $this->render('team/show.html.twig', [
            'team' => $team,
        ]);
    }

    /**
     * ✅ SCÉNARIO A & B (Captain view)
     */
    #[Route('/my-team', name: 'my_team', methods: ['GET'])]
    public function myTeam(
        InvitationRepository $invitationRepo,
        PlayerRepository $playerRepo,
        Request $request
    ): Response {
        /** @var \App\Entity\User|null $user */
        $user = $this->getUser();
        if (!$user || $user->getRoleType() !== 'CAPTAIN') {
            throw $this->createAccessDeniedException('Réservé aux Capitaines.');
        }

        $player = $user->getPlayer();
        $team = $player ? $player->getTeam() : null;

        // Si le captain n'a pas encore de team (cas rare mais possible selon fixtures)
        if (!$team) {
            return $this->render('team/no_team.html.twig');
        }

        $q = $request->query->get('q', '');
        $freePlayers = $playerRepo->findAvailableForTeam($team, $q, 10);
        $joinRequests = $invitationRepo->findPendingCandidaturesForTeam($team);
        $sentInvitations = $invitationRepo->findPendingForTeam($team); // Captain -> Player

        return $this->render('team/my_team.html.twig', [
            'team' => $team,
            'freePlayers' => $freePlayers,
            'joinRequests' => $joinRequests,
            'sentInvitations' => $sentInvitations,
            'q' => $q
        ]);
    }

    /**
     * ✅ SCÉNARIO A : Action Inviter
     */
    #[Route('/team/{id}/invite/{playerId}', name: 'team_send_invite', methods: ['POST'])]
    public function sendInvite(
        Team $team,
        int $playerId,
        PlayerRepository $playerRepo,
        InvitationService $invitationService
    ): Response {
        /** @var \App\Entity\User|null $user */
        $user = $this->getUser();
        if (!$user || !$team->getOwner() || $user->getId() !== $team->getOwner()->getId()) {
            throw $this->createAccessDeniedException();
        }

        $targetPlayer = $playerRepo->find($playerId);
        if (!$targetPlayer) {
            $this->addFlash('error', 'Joueur introuvable.');
            return $this->redirectToRoute('my_team');
        }

        try {
            $invitationService->captainInvitesPlayer($team, $targetPlayer, $user->getPlayer());
            $this->addFlash('success', 'Invitation envoyée à ' . $targetPlayer->getNickname());
        } catch (\Throwable $e) {
            $this->addFlash('error', $e->getMessage());
        }

        return $this->redirectToRoute('my_team');
    }

    /**
     * ✅ SCÉNARIO B : Action Postuler (par un Player)
     */
    #[Route('/team/{id}/apply', name: 'team_apply', methods: ['POST'])]
    public function apply(Team $team, InvitationService $invitationService): Response
    {
        /** @var \App\Entity\User|null $user */
        $user = $this->getUser();
        $player = $user ? $user->getPlayer() : null;

        if (!$player) {
            throw $this->createAccessDeniedException();
        }

        try {
            $invitationService->playerAppliesToTeam($player, $team);
            $this->addFlash('success', 'Votre demande a été envoyée au capitaine.');
        } catch (\Throwable $e) {
            $this->addFlash('error', $e->getMessage());
        }

        return $this->redirectToRoute('team_show', ['id' => $team->getId()]);
    }

    /**
     * ✅ Action : Retirer un joueur de la team
     * Accessible uniquement par le Capitaine (Owner) de la team.
     */
    #[Route('/team/remove/{playerId}', name: 'team_remove_player', methods: ['POST'])]
    public function removePlayer(
        int $playerId,
        PlayerRepository $playerRepo,
        EntityManagerInterface $em
    ): Response {
        /** @var \App\Entity\User|null $user */
        $user = $this->getUser();
        if (!$user || $user->getRoleType() !== 'CAPTAIN') {
            throw $this->createAccessDeniedException('Réservé aux Capitaines.');
        }

        $targetPlayer = $playerRepo->find($playerId);
        if (!$targetPlayer) {
            $this->addFlash('error', 'Joueur introuvable.');
            return $this->redirectToRoute('my_team');
        }

        $team = $targetPlayer->getTeam();
        if (!$team || !$team->getOwner() || $user->getId() !== $team->getOwner()->getId()) {
            throw $this->createAccessDeniedException('Vous ne pouvez pas retirer ce joueur car il n\'appartient pas à votre équipe.');
        }

        // Sécurité : Le capitaine ne peut pas se retirer lui-même de sa propre team ici
        if ($targetPlayer->getUser() && $targetPlayer->getUser()->getId() === $user->getId()) {
            $this->addFlash('error', 'Vous ne pouvez pas vous retirer vous-même de votre équipe. Transférez la propriété d\'abord ou supprimez l\'équipe.');
            return $this->redirectToRoute('my_team');
        }

        // On casse la relation
        $targetPlayer->setTeam(null);
        $em->flush();

        $this->addFlash('success', 'Le joueur ' . $targetPlayer->getNickname() . ' a été retiré de l\'équipe.');

        return $this->redirectToRoute('my_team');
    }
}