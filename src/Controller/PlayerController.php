<?php

namespace App\Controller;

use App\Repository\PlayerRepository;
use App\Entity\Player;
use App\Form\PlayerType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/player')]
class PlayerController extends AbstractController
{
    #[Route('/', name: 'app_player_index', methods: ['GET'])]
    public function index(Request $request, PlayerRepository $playerRepository): Response
    {
        $q = trim((string) $request->query->get('q', ''));
        $sort = (string) $request->query->get('sort', 'id'); // id|name|team
        $dir = strtolower((string) $request->query->get('dir', 'asc')) === 'desc' ? 'desc' : 'asc';

        $qb = $playerRepository->createQueryBuilder('p')
            ->leftJoin('p.team', 't')->addSelect('t');

        if ($q !== '') {
            $qb->andWhere('LOWER(p.nickname) LIKE :q OR LOWER(t.name) LIKE :q')
               ->setParameter('q', '%'.mb_strtolower($q).'%');
        }

        // whitelist tri
        $sortMap = [
            'id' => 'p.id',
            'name' => 'p.nickname',
            'team' => 't.name',
        ];
        $orderBy = $sortMap[$sort] ?? 'p.id';

        $qb->orderBy($orderBy, $dir);

        $players = $qb->getQuery()->getResult();

        return $this->render('player/index.html.twig', [
            'players' => $players,
            'q' => $q,
            'sort' => $sort,
            'dir' => $dir,
        ]);
    }

    #[Route('/new', name: 'app_player_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $player = new Player();
        $form = $this->createForm(PlayerType::class, $player);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Avatar selection (predefined)
            $avatarName = (string) $request->request->all('player')['avatarName'] ?? (string) $request->request->get('avatarName');
            if ($avatarName !== '') {
                $player->setAvatarName($avatarName);
            }
            $entityManager->persist($player);
            $entityManager->flush();

            return $this->redirectToRoute('app_player_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('player/new.html.twig', [
            'player' => $player,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_player_show', methods: ['GET'])]
    public function show(Player $player): Response
    {
        return $this->render('player/show.html.twig', [
            'player' => $player,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_player_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Player $player, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(PlayerType::class, $player);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $avatarName = (string) $request->request->all('player')['avatarName'] ?? (string) $request->request->get('avatarName');
            if ($avatarName !== '') {
                $player->setAvatarName($avatarName);
            }
            $entityManager->flush();

            return $this->redirectToRoute('app_player_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('player/edit.html.twig', [
            'player' => $player,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_player_delete', methods: ['POST'])]
    public function delete(Request $request, Player $player, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$player->getId(), (string) $request->request->get('_token'))) {
            $entityManager->remove($player);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_player_index', [], Response::HTTP_SEE_OTHER);
    }
}
