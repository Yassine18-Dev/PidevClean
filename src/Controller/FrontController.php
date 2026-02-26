<?php

namespace App\Controller;

use App\Repository\TournamentRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class FrontController extends AbstractController
{
    #[Route('/', name: 'front_home', methods: ['GET'])]
    public function home(): Response
    {
        return $this->render('front/home.html.twig');
    }

    #[Route('/tournaments', name: 'front_tournaments', methods: ['GET'])]
    public function tournaments(TournamentRepository $tournamentRepository): Response
    {
        // Exemple : afficher les tournois les plus récents d'abord
        $tournaments = $tournamentRepository->findBy([], ['startAt' => 'ASC']);

        return $this->render('front/tournaments.html.twig', [
            'tournaments' => $tournaments,
        ]);
    }
}