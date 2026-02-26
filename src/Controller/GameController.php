<?php

namespace App\Controller;

use App\Entity\Game;
use App\Form\GameType;
use App\Repository\GameRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\HttpClient\HttpClientInterface;

#[Route('/game')]
class GameController extends AbstractController
{
    private string $rawgApiKey;

    public function __construct(string $rawgApiKey)
    {
        $this->rawgApiKey = $rawgApiKey;
    }

    /* ──────────────────────────────────────────
       CRUD – Index
    ────────────────────────────────────────── */
    #[Route('/', name: 'app_game_index', methods: ['GET'])]
    public function index(Request $request, GameRepository $gameRepository): Response
    {
        $q    = trim((string) $request->query->get('q', ''));
        $sort = (string) $request->query->get('sort', 'id');
        $dir  = strtolower((string) $request->query->get('dir', 'asc')) === 'desc' ? 'desc' : 'asc';

        $qb = $gameRepository->createQueryBuilder('g');
        if ($q !== '') {
            $qb->andWhere('LOWER(g.name) LIKE :q')
               ->setParameter('q', '%'.mb_strtolower($q).'%');
        }
        $sortMap = ['id' => 'g.id', 'name' => 'g.name', 'maxPlayers' => 'g.maxPlayers'];
        $qb->orderBy($sortMap[$sort] ?? 'g.id', $dir);

        return $this->render('game/index.html.twig', [
            'games' => $qb->getQuery()->getResult(),
            'q'     => $q,
            'sort'  => $sort,
            'dir'   => $dir,
        ]);
    }

    /* ──────────────────────────────────────────
       RAWG – Explore (liste paginée)
    ────────────────────────────────────────── */
    #[Route('/explore', name: 'app_game_explore', methods: ['GET'])]
    public function explore(Request $request, HttpClientInterface $http): Response
    {
        $search = trim((string) $request->query->get('search', ''));
        $page   = max(1, (int) $request->query->get('page', 1));

        $params = ['key' => $this->rawgApiKey, 'page_size' => 20, 'page' => $page];
        if ($search !== '') {
            $params['search'] = $search;
        }

        $rawgGames = [];
        $count     = 0;
        $hasNext   = false;
        $hasPrev   = false;
        $error     = null;

        try {
            $resp = $http->request('GET', 'https://api.rawg.io/api/games', [
                'query' => $params, 'timeout' => 10,
            ]);
            $data = $resp->toArray(false);
            if (isset($data['results'])) {
                $rawgGames = $data['results'];
                $count     = $data['count']    ?? 0;
                $hasNext   = $data['next']     !== null;
                $hasPrev   = $data['previous'] !== null;
            } else {
                $error = 'Réponse inattendue de l\'API RAWG.';
            }
        } catch (\Throwable $e) {
            $error = 'Impossible de contacter l\'API RAWG : '.$e->getMessage();
        }

        return $this->render('game/explore.html.twig', [
            'rawgGames' => $rawgGames,
            'search'    => $search,
            'page'      => $page,
            'count'     => $count,
            'hasNext'   => $hasNext,
            'hasPrev'   => $hasPrev,
            'error'     => $error,
        ]);
    }

    /* ──────────────────────────────────────────
       RAWG – Détail d'un jeu (toutes infos)
    ────────────────────────────────────────── */
    #[Route('/explore/{rawgId}', name: 'app_game_explore_detail', methods: ['GET'], requirements: ['rawgId' => '\d+'])]
    public function exploreDetail(int $rawgId, HttpClientInterface $http): Response
    {
        $key   = $this->rawgApiKey;
        $game  = null;
        $shots = [];
        $error = null;

        try {
            // Infos principales
            $resp = $http->request('GET', "https://api.rawg.io/api/games/{$rawgId}", [
                'query' => ['key' => $key], 'timeout' => 10,
            ]);
            $game = $resp->toArray(false);

            if (isset($game['detail'])) {          // RAWG retourne {detail: "Not found."} si introuvable
                $error = 'Jeu introuvable sur RAWG.';
                $game  = null;
            } else {
                // Screenshots
                $sResp = $http->request('GET', "https://api.rawg.io/api/games/{$rawgId}/screenshots", [
                    'query' => ['key' => $key], 'timeout' => 10,
                ]);
                $sData = $sResp->toArray(false);
                $shots = $sData['results'] ?? [];
            }
        } catch (\Throwable $e) {
            $error = 'Impossible de contacter l\'API RAWG : '.$e->getMessage();
        }

        return $this->render('game/explore_detail.html.twig', [
            'game'        => $game,
            'screenshots' => $shots,
            'error'       => $error,
        ]);
    }

    /* ──────────────────────────────────────────
       CRUD – New
    ────────────────────────────────────────── */
    #[Route('/new', name: 'app_game_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $game = new Game();
        $form = $this->createForm(GameType::class, $game);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($game);
            $em->flush();
            $this->addFlash('success', 'Jeu créé avec succès.');
            return $this->redirectToRoute('app_game_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('game/new.html.twig', ['game' => $game, 'form' => $form]);
    }

    /* ──────────────────────────────────────────
       CRUD – Show
    ────────────────────────────────────────── */
    #[Route('/{id}', name: 'app_game_show', methods: ['GET'])]
    public function show(Game $game): Response
    {
        return $this->render('game/show.html.twig', ['game' => $game]);
    }

    /* ──────────────────────────────────────────
       CRUD – Edit
    ────────────────────────────────────────── */
    #[Route('/{id}/edit', name: 'app_game_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Game $game, EntityManagerInterface $em): Response
    {
        $form = $this->createForm(GameType::class, $game);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();
            $this->addFlash('success', 'Jeu mis à jour.');
            return $this->redirectToRoute('app_game_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('game/edit.html.twig', ['game' => $game, 'form' => $form]);
    }

    /* ──────────────────────────────────────────
       CRUD – Delete
    ────────────────────────────────────────── */
    #[Route('/{id}', name: 'app_game_delete', methods: ['POST'])]
    public function delete(Request $request, Game $game, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('delete'.$game->getId(), (string) $request->request->get('_token'))) {
            $em->remove($game);
            $em->flush();
            $this->addFlash('success', 'Jeu supprimé.');
        }

        return $this->redirectToRoute('app_game_index', [], Response::HTTP_SEE_OTHER);
    }
}
