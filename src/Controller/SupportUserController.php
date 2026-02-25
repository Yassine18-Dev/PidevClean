<?php

namespace App\Controller;

use App\Entity\Ticket;
use App\Entity\User;
use App\Form\TicketType;
use App\Repository\TicketRepository;
use App\Service\PriorityService;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/support')]
class SupportUserController extends AbstractController
{
    public function __construct(
        private TicketRepository $ticketRepo,
        private EntityManagerInterface $em,
        private PriorityService $priorityService,
    ) {}

    #[Route('', name: 'support_role_select', methods: ['GET'])]
    public function roleSelect(): Response
    {
        return $this->render('support/role_select.html.twig');
    }

    #[Route('/tickets', name: 'support_user_tickets', methods: ['GET'])]
    public function myTickets(Request $request, PaginatorInterface $paginator): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        $query = $this->ticketRepo->buildUserTicketsQuery($user->getId());

        $pagination = $paginator->paginate(
            $query,
            $request->query->getInt('page', 1),
            8
        );

        return $this->render('support/user/tickets.html.twig', [
            'pagination' => $pagination,
        ]);
    }

    #[Route('/tickets/new', name: 'support_user_ticket_new', methods: ['GET', 'POST'])]
    public function newTicket(Request $request): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        $ticket = new Ticket();

        $form = $this->createForm(TicketType::class, $ticket);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $ticket->setSubmitter($user);

            // Intelligent Priority Engine
            $categoryName = $ticket->getCategory() ? $ticket->getCategory()->getName() : 'General';
            $priority = $this->priorityService->evaluate(
                $ticket->getSubject(),
                $ticket->getDescription(),
                $categoryName
            );
            $ticket->setPriority($priority);

            $this->em->persist($ticket);
            $this->em->flush();

            $this->addFlash('success', "Ticket created successfully! Priority assigned: " . ucfirst($priority));
            return $this->redirectToRoute('support_user_tickets');
        }

        return $this->render('support/user/new_ticket.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/tickets/{id}', name: 'support_user_ticket_show', methods: ['GET'])]
    public function showTicket(int $id): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        $ticket = $this->ticketRepo->find($id);

        if (!$ticket || $ticket->getSubmitter()->getId() !== $user->getId()) {
            throw $this->createNotFoundException('Ticket not found');
        }

        return $this->render('support/user/ticket_show.html.twig', [
            'ticket' => $ticket,
        ]);
    }

    #[Route('/chatbot', name: 'support_chatbot', methods: ['GET'])]
    public function chatbot(): Response
    {
        return $this->render('support/chatbot.html.twig');
    }
}