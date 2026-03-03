<?php

namespace App\Controller;

use App\Repository\TeamRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class TeamListController extends AbstractController
{
    #[Route('/team/', name: 'team_index', methods: ['GET'])]
    public function index(TeamRepository $teamRepo): Response
    {
        $teams = $teamRepo->findBy([], ['id' => 'DESC'], 50);

        return $this->render('team/index.html.twig', [
            'teams' => $teams,
        ]);
    }
}