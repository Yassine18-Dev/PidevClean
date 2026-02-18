<?php

namespace App\Controller;

use App\Repository\GameRepository;
use App\Repository\TournamentRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ArenaMindController extends AbstractController
{
    #[Route('/arenamind', name: 'am_dashboard', methods: ['GET'])]
    public function dashboard(): Response
    {
        // Page "Season" (image 1)
        return $this->render('arenamind/dashboard.html.twig');
    }

    #[Route('/arenamind/games', name: 'am_games', methods: ['GET'])]
    public function games(GameRepository $gameRepository): Response
    {
        return $this->render('arenamind/games.html.twig', [
            'games' => $gameRepository->findAll(),
        ]);
    }

    #[Route('/arenamind/tournaments', name: 'am_tournaments', methods: ['GET'])]
    public function tournaments(TournamentRepository $tournamentRepository): Response
    {
        return $this->render('arenamind/tournaments.html.twig', [
            'tournaments' => $tournamentRepository->findBy([], ['startAt' => 'ASC']),
        ]);
    }
}
