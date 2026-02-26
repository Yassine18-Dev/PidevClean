<?php

namespace App\Controller;

use App\Entity\Tournament;
use App\Form\TournamentType;
use App\Repository\TournamentRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/tournament')]
class TournamentCrudController extends AbstractController
{
    #[Route('/', name: 'am_tournament_index', methods: ['GET'])]
    public function index(TournamentRepository $tournamentRepository): Response
    {
        return $this->render('tournament/index.html.twig', [
            'tournaments' => $tournamentRepository->findBy([], ['startAt' => 'ASC']),
        ]);
    }

    #[Route('/new', name: 'am_tournament_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $tournament = new Tournament();
        $form = $this->createForm(TournamentType::class, $tournament);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($tournament);
            $em->flush();
            $this->addFlash('success', 'Tournament créé avec succès.');
            return $this->redirectToRoute('am_tournament_index');
        }

        return $this->render('tournament/new.html.twig', [
            'tournament' => $tournament,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'am_tournament_show', methods: ['GET'])]
    public function show(Tournament $tournament): Response
    {
        return $this->render('tournament/show.html.twig', [
            'tournament' => $tournament,
        ]);
    }

    #[Route('/{id}/edit', name: 'am_tournament_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Tournament $tournament, EntityManagerInterface $em): Response
    {
        $form = $this->createForm(TournamentType::class, $tournament);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();
            $this->addFlash('success', 'Tournament mis à jour.');
            return $this->redirectToRoute('am_tournament_index');
        }

        return $this->render('tournament/edit.html.twig', [
            'tournament' => $tournament,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'am_tournament_delete', methods: ['POST'])]
    public function delete(Request $request, Tournament $tournament, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('delete_tournament_'.$tournament->getId(), (string)$request->request->get('_token'))) {
            $em->remove($tournament);
            $em->flush();
            $this->addFlash('success', 'Tournament supprimé.');
        }

        return $this->redirectToRoute('am_tournament_index');
    }
}
