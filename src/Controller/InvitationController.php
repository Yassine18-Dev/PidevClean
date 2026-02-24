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
        $player = method_exists($user, 'getPlayer') ? $user->getPlayer() : null;
        if (!$player) {
            $this->addFlash('error', 'Vous devez avoir un profil joueur pour inviter.');
            return $this->redirectToRoute('ui_profile');
        }

        // Captain guard (simple): must be CAPTAIN + belong to this team
        if (method_exists($user, 'getRoleType') && $user->getRoleType() !== 'CAPTAIN') {
            throw $this->createAccessDeniedException('Captain only');
        }
        if (!$player->getTeam() || $player->getTeam()->getId() !== $team->getId()) {
            throw $this->createAccessDeniedException('Not your team');
        }

        $pending = $invitationRepository->findPendingForTeam($team);
        foreach ($pending as $inv) {
            $invitationService->expireIfNeeded($inv);
        }

        $search = trim((string) $request->query->get('q', ''));
        $results = $search !== '' ? $playerRepository->searchByNickname($search, 10) : [];

        if ($request->isMethod('POST')) {
            $targetId = (int) $request->request->get('player_id');
            $target = $targetId ? $playerRepository->find($targetId) : null;

            if (!$target) {
                $this->addFlash('error', 'Joueur invalide.');
                return $this->redirectToRoute('team_invitations', ['id' => $team->getId()]);
            }

            [$ok, $reason] = $invitationService->canSendInvitation($team, $player, $target);
            if (!$ok) {
                $this->addFlash('error', $reason ?? 'Impossible d\'envoyer l\'invitation.');
                return $this->redirectToRoute('team_invitations', ['id' => $team->getId()]);
            }

            $invitationService->sendInvitation($team, $player, $target);
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

        // Re-fetch after potential expiration
        $invs = $invitationRepository->findReceivedPending($player);

        return $this->render('invitation/index.html.twig', [
            'invitations' => $invs,
        ]);
    }

    #[Route('/invitations/{id}/accept', name: 'invitation_accept', methods: ['POST'])]
    public function accept(
        Invitation $invitation,
        EntityManagerInterface $em,
        InvitationService $invitationService,
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $invitationService->expireIfNeeded($invitation);

        $user = $this->getUser();
        $player = method_exists($user, 'getPlayer') ? $user->getPlayer() : null;
        if (!$player || $invitation->getPlayer()->getId() !== $player->getId()) {
            throw $this->createAccessDeniedException();
        }

        if ($invitation->getStatus() !== Invitation::STATUS_PENDING) {
            $this->addFlash('error', 'Invitation non valide.');
            return $this->redirectToRoute('invitation_index');
        }

        $team = $invitation->getTeam();
        if (!$team || !$team->hasAvailableSlot()) {
            $this->addFlash('error', 'Équipe complète.');
            return $this->redirectToRoute('invitation_index');
        }

        // For LoL/Valorant: player cannot already be in a team
        if (in_array($team->getGame(), ['lol', 'valorant'], true) && $player->getTeam() !== null) {
            $this->addFlash('error', 'Vous êtes déjà dans une équipe.');
            return $this->redirectToRoute('invitation_index');
        }

        $player->setTeam($team);
        $invitation->setStatus(Invitation::STATUS_ACCEPTED);
        $em->flush();

        $this->addFlash('success', 'Invitation acceptée.');
        return $this->redirectToRoute('invitation_index');
    }

    #[Route('/invitations/{id}/decline', name: 'invitation_decline', methods: ['POST'])]
    public function decline(
        Invitation $invitation,
        EntityManagerInterface $em,
        InvitationService $invitationService,
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $invitationService->expireIfNeeded($invitation);

        $user = $this->getUser();
        $player = method_exists($user, 'getPlayer') ? $user->getPlayer() : null;
        if (!$player || $invitation->getPlayer()->getId() !== $player->getId()) {
            throw $this->createAccessDeniedException();
        }

        if ($invitation->getStatus() !== Invitation::STATUS_PENDING) {
            $this->addFlash('error', 'Invitation non valide.');
            return $this->redirectToRoute('invitation_index');
        }

        $invitation->setStatus(Invitation::STATUS_DECLINED);
        $em->flush();

        $this->addFlash('success', 'Invitation refusée.');
        return $this->redirectToRoute('invitation_index');
    }
}
