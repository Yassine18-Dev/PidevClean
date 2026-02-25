<?php

namespace App\Controller;

use App\Entity\Ticket;
use App\Repository\TicketCategoryRepository;
use App\Repository\TicketRepository;
use App\Service\AnalyticsService;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Workflow\WorkflowInterface;

#[Route('/admin/support')]
class SupportAdminController extends AbstractController
{
    public function __construct(
        private TicketRepository $ticketRepo,
        private TicketCategoryRepository $categoryRepo,
        private AnalyticsService $analytics,
        private EntityManagerInterface $em,
    ) {}

    #[Route('', name: 'support_admin_dashboard', methods: ['GET'])]
    public function dashboard(): Response
    {
        $stats = $this->analytics->getDashboardStats();
        $byCategory = $this->analytics->getTicketsByCategory();
        $statusDist = $this->analytics->getStatusDistribution();

        return $this->render('support/admin/dashboard.html.twig', [
            'stats'        => $stats,
            'byCategory'   => $byCategory,
            'statusDist'   => $statusDist,
        ]);
    }

    #[Route('/tickets', name: 'support_admin_tickets', methods: ['GET'])]
    public function tickets(Request $request, PaginatorInterface $paginator): Response
    {
        $search     = $request->query->get('q', '');
        $status     = $request->query->get('status', '');
        $categoryId = $request->query->getInt('category', 0);

        $query = $this->ticketRepo->buildFilteredQuery(
            $search ?: null,
            $status ?: null,
            $categoryId ?: null,
        );

        $pagination = $paginator->paginate(
            $query,
            $request->query->getInt('page', 1),
            10
        );

        return $this->render('support/admin/tickets.html.twig', [
            'pagination' => $pagination,
            'categories' => $this->categoryRepo->findAllSorted(),
            'q'          => $search,
            'status'     => $status,
            'category'   => $categoryId,
        ]);
    }

    #[Route('/tickets/{id}', name: 'support_admin_ticket_show', methods: ['GET', 'POST'])]
    public function ticketShow(
        int $id,
        Request $request,
        WorkflowInterface $ticketLifecycle,
    ): Response {
        $ticket = $this->ticketRepo->find($id);
        if (!$ticket) {
            throw $this->createNotFoundException('Ticket not found');
        }

        // Save admin notes
        if ($request->isMethod('POST') && $request->request->has('admin_notes')) {
            $ticket->setAdminNotes($request->request->get('admin_notes'));
            $this->em->flush();
            $this->addFlash('success', 'Admin notes saved.');
            return $this->redirectToRoute('support_admin_ticket_show', ['id' => $id]);
        }

        // Available workflow transitions
        $transitions = $ticketLifecycle->getEnabledTransitions($ticket);

        return $this->render('support/admin/ticket_show.html.twig', [
            'ticket'      => $ticket,
            'transitions' => $transitions,
        ]);
    }

    #[Route('/tickets/{id}/transition/{transition}', name: 'support_admin_ticket_transition', methods: ['POST'])]
    public function applyTransition(
        int $id,
        string $transition,
        WorkflowInterface $ticketLifecycle,
    ): Response {
        $ticket = $this->ticketRepo->find($id);
        if (!$ticket) {
            throw $this->createNotFoundException('Ticket not found');
        }

        if ($ticketLifecycle->can($ticket, $transition)) {
            $ticketLifecycle->apply($ticket, $transition);
            $ticket->setUpdatedAt(new \DateTimeImmutable());
            $this->em->flush();
            $this->addFlash('success', "Transition \"{$transition}\" applied successfully.");
        } else {
            $this->addFlash('error', "Transition \"{$transition}\" is not available for this ticket.");
        }

        return $this->redirectToRoute('support_admin_ticket_show', ['id' => $id]);
    }

    #[Route('/analytics', name: 'support_admin_analytics', methods: ['GET'])]
    public function analytics(): Response
    {
        return $this->render('support/admin/analytics.html.twig', [
            'stats' => $this->analytics->getDashboardStats(),
        ]);
    }

    #[Route('/categories', name: 'support_admin_categories', methods: ['GET'])]
    public function categories(): Response
    {
        $categories = $this->categoryRepo->findAllSorted();

        return $this->render('support/admin/categories.html.twig', [
            'categories' => $categories,
        ]);
    }
}