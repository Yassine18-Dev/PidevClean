<?php

namespace App\Controller;

use App\Entity\Invitation;
use App\Repository\InvitationRepository;
use App\Service\InvitationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/invitations')]
class InvitationController extends AbstractController
{
    public function __construct(
        private readonly InvitationRepository $invitationRepo,
        private readonly InvitationService $invitationService,
        private readonly EntityManagerInterface $em,
    ) {}

    #[Route('', name: 'invitation_index', methods: ['GET'])]
    public function index(): Response
    {
        $user = $this->getUser();
        if (!$user || !method_exists($user, 'getPlayer') || !$user->getPlayer()) {
            throw $this->createAccessDeniedException();
        }

        $player = $user->getPlayer();

        $this->invitationService->expireOldInvitations();
        $pending = $this->invitationRepo->findPendingForPlayer($player);

        return $this->render('invitation/index.html.twig', [
            'player' => $player,
            'pendingInvitations' => $pending,
            'expiresIn' => fn(Invitation $i) => $this->invitationService->expiresInDays($i),
        ]);
    }

    #[Route('/{id}/accept', name: 'invitation_accept', methods: ['POST', 'GET'])]
    public function accept(Invitation $invitation): Response
    {
        $user = $this->getUser();
        if (!$user || !method_exists($user, 'getPlayer') || !$user->getPlayer()) {
            throw $this->createAccessDeniedException();
        }

        try {
            $this->invitationService->accept($invitation, $user->getPlayer());
            $this->addFlash('success', 'Invitation accepted. You joined the team!');
        } catch (\Throwable $e) {
            $this->addFlash('danger', $e->getMessage());
        }

        return $this->redirectToRoute('ui_profile');
    }

    #[Route('/{id}/decline', name: 'invitation_decline', methods: ['POST', 'GET'])]
    public function decline(Invitation $invitation): Response
    {
        $user = $this->getUser();
        if (!$user || !method_exists($user, 'getPlayer') || !$user->getPlayer()) {
            throw $this->createAccessDeniedException();
        }

        try {
            $this->invitationService->decline($invitation, $user->getPlayer());
            $this->addFlash('info', 'Invitation declined.');
        } catch (\Throwable $e) {
            $this->addFlash('danger', $e->getMessage());
        }

        return $this->redirectToRoute('ui_profile');
    }
}