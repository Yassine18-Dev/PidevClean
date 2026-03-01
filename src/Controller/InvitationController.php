<?php

namespace App\Controller;

use App\Entity\Invitation;
use App\Repository\InvitationRepository;
use App\Repository\PlayerRepository;
use App\Repository\TeamRepository;
use App\Service\InvitationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class InvitationController extends AbstractController
{
    #[Route('/team/{id}/invitations', name: 'team_invitations', methods: ['GET','POST'])]
    public function teamInvitations(
        int $id,
        Request $request,
        TeamRepository $teamRepository,
        PlayerRepository $playerRepository,
        InvitationRepository $invitationRepository,
        InvitationService $invitationService,
        EntityManagerInterface $em,
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $team = $teamRepository->findOneWithPlayers($id);
        if (!$team) {
            throw $this->createNotFoundException();
        }

        $user = $this->getUser();
        $captain = method_exists($user, 'getPlayer') ? $user->getPlayer() : null;
        if (!$captain) {
            $this->addFlash('error', 'Vous devez avoir un profil joueur pour inviter.');
            return $this->redirectToRoute('ui_profile');
        }

        // Captain guard (simple): must be CAPTAIN + belong to this team
        if (method_exists($user, 'getRoleType') && $user->getRoleType() !== 'CAPTAIN') {
            throw $this->createAccessDeniedException('Captain only');
        }
        if (!$captain->getTeam() || $captain->getTeam()->getId() !== $team->getId()) {
            throw $this->createAccessDeniedException('Not your team');
        }

        // Expire pending invitations on display
        $pending = $invitationRepository->findPendingForTeam($team);
        foreach ($pending as $inv) {
            $invitationService->expireIfNeeded($inv);
        }
        $pending = $invitationRepository->findPendingForTeam($team);

        $search = trim((string) $request->query->get('q', ''));
        $results = $playerRepository->searchAvailableForTeam($team, $search, 12);

        if ($request->isMethod('POST')) {
            $targetId = (int) $request->request->get('player_id');
            $target = $targetId ? $playerRepository->find($targetId) : null;

            if (!$target) {
                $this->addFlash('error', 'Joueur invalide.');
                return $this->redirectToRoute('team_invitations', ['id' => $team->getId()]);
            }

            [$ok, $reason] = $invitationService->canSendInvitation($team, $captain, $target);
            if (!$ok) {
                $this->addFlash('error', $reason ?? 'Impossible d\'envoyer l\'invitation.');
                return $this->redirectToRoute('team_invitations', ['id' => $team->getId()]);
            }

            $invitationService->sendInvitation($team, $captain, $target);
            $this->addFlash('success', 'Invitation envoyée.');
            return $this->redirectToRoute('team_invitations', ['id' => $team->getId()]);
        }

        return $this->render('team/invitations.html.twig', [
            'team' => $team,
            'pending' => $pending,
            'search' => $search,
            'results' => $results,
        ]);
    }

    #[Route('/team/{teamId}/invitations/{id}/cancel', name: 'team_invitation_cancel', methods: ['POST'])]
    public function cancel(
        int $teamId,
        Invitation $invitation,
        TeamRepository $teamRepository,
        InvitationService $invitationService,
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $team = $teamRepository->find($teamId);
        if (!$team) {
            throw $this->createNotFoundException();
        }

        $user = $this->getUser();
        $captain = method_exists($user, 'getPlayer') ? $user->getPlayer() : null;
        if (!$captain) {
            throw $this->createAccessDeniedException();
        }

        if (method_exists($user, 'getRoleType') && $user->getRoleType() !== 'CAPTAIN') {
            throw $this->createAccessDeniedException('Captain only');
        }
        if (!$captain->getTeam() || $captain->getTeam()->getId() !== $team->getId()) {
            throw $this->createAccessDeniedException('Not your team');
        }

        try {
            $invitationService->cancelInvitation($invitation, $team);
            $this->addFlash('success', 'Invitation annulée.');
        } catch (\Throwable $e) {
            $this->addFlash('error', $e->getMessage());
        }

        return $this->redirectToRoute('team_invitations', ['id' => $team->getId()]);
    }

    #[Route('/invitations', name: 'invitation_index', methods: ['GET'])]
    public function received(
        InvitationRepository $invitationRepository,
        InvitationService $invitationService,
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $user = $this->getUser();
        $player = method_exists($user, 'getPlayer') ? $user->getPlayer() : null;
        if (!$player) {
            return $this->render('invitation/index.html.twig', [
                'invitations' => [],
            ]);
        }

        $invs = $invitationRepository->findReceivedPending($player);
        foreach ($invs as $inv) {
            $invitationService->expireIfNeeded($inv);
        }
        $invs = $invitationRepository->findReceivedPending($player);

        return $this->render('invitation/index.html.twig', [
            'invitations' => $invs,
        ]);
    }

    #[Route('/invitations/{id}/accept', name: 'invitation_accept', methods: ['POST'])]
    public function accept(
        Invitation $invitation,
        InvitationService $invitationService,
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $user = $this->getUser();
        $player = method_exists($user, 'getPlayer') ? $user->getPlayer() : null;
        if (!$player || $invitation->getPlayer()->getId() !== $player->getId()) {
            throw $this->createAccessDeniedException();
        }

        try {
            $invitationService->acceptInvitation($invitation, $player);
            $this->addFlash('success', 'Invitation acceptée. Bienvenue dans l\'équipe !');
        } catch (\Throwable $e) {
            $this->addFlash('error', $e->getMessage());
        }

        return $this->redirectToRoute('ui_profile');
    }

    #[Route('/invitations/{id}/decline', name: 'invitation_decline', methods: ['POST'])]
    public function decline(
        Invitation $invitation,
        InvitationService $invitationService,
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $user = $this->getUser();
        $player = method_exists($user, 'getPlayer') ? $user->getPlayer() : null;
        if (!$player || $invitation->getPlayer()->getId() !== $player->getId()) {
            throw $this->createAccessDeniedException();
        }

        try {
            $invitationService->declineInvitation($invitation, $player);
            $this->addFlash('success', 'Invitation refusée.');
        } catch (\Throwable $e) {
            $this->addFlash('error', $e->getMessage());
        }

        return $this->redirectToRoute('ui_profile');
    }
}
