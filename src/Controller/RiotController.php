<?php

namespace App\Controller;

use App\Service\RiotApiService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class RiotController extends AbstractController
{
    /**
     * GET /riot/link  →  Formulaire de liaison Riot (gameName#tag)
     * POST /riot/link →  Traite la liaison
     */
    #[Route('/riot/link', name: 'riot_link', methods: ['GET', 'POST'])]
    public function link(Request $request, RiotApiService $riot, EntityManagerInterface $em): Response
    {
        /** @var \App\Entity\User|null $user */
        $user = $this->getUser();
        if (!$user) {
            $this->addFlash('error', 'You must be logged in.');
            return $this->redirectToRoute('ui_login');
        }

        // Récupérer ou créer le Player
        $player = $user->getPlayer();
        if (!$player) {
            $player = new \App\Entity\Player();
            $player->setUser($user);
            $player->setNickname($user->getUsername() ?? 'Player');
            $em->persist($player);
            $user->setPlayer($player);
        }

        if ($request->isMethod('POST')) {
            $gameName = trim((string) $request->request->get('game_name', ''));
            $tagLine  = trim((string) $request->request->get('tag_line', ''));
            $region   = trim((string) $request->request->get('region', 'EUW1'));

            // ✅ Normalisation: si l'utilisateur entre "Pseudo#Tag" dans le champ gameName
            if (str_contains($gameName, '#')) {
                [$gameName, $tagLine] = array_map('trim', explode('#', $gameName, 2));
            }

            // ✅ Strip le '#' si l'utilisateur l'a inclus dans le tag (ex: '#EUW' → 'EUW')
            $tagLine = ltrim($tagLine, '#');

            try {
                $data = $riot->linkAccountAndFetchRank($region, $gameName, $tagLine);


                $player->setRiotPuuid($data['puuid'] ?? null);
                $player->setRiotGameName($data['gameName'] ?? null);
                $player->setRiotTagLine($data['tagLine'] ?? null);
                $player->setRiotRank($data['rank'] ?? null);
                $player->setLolRank($data['lolRank'] ?? null);
                $player->setValoRank($data['valoRank'] ?? null);
                $player->setRiotRegion($region);
                $player->setRiotLinkedAt(new \DateTime('now'));
                $player->setUpdatedAt(new \DateTime('now'));

                $em->flush();

                $this->addFlash('success', 'Riot account linked successfully!');
                return $this->redirectToRoute('profile_index');
            } catch (\Throwable $e) {
                $this->addFlash('error', $e->getMessage());
            }
        }

        return $this->render('riot/link.html.twig', [
            'player' => $player,
        ]);
    }

    /**
     * POST /riot/refresh → Recalcule LoL rank + Valo rank
     */
    #[Route('/riot/refresh', name: 'riot_refresh', methods: ['POST', 'GET'])]
    public function refresh(RiotApiService $riot, EntityManagerInterface $em): Response
    {
        /** @var \App\Entity\User|null $user */
        $user = $this->getUser();
        if (!$user) {
            $this->addFlash('error', 'You must be logged in.');
            return $this->redirectToRoute('ui_login');
        }

        $player = $user->getPlayer();
        if (!$player || !$player->getRiotPuuid()) {
            $this->addFlash('error', 'No Riot account linked.');
            return $this->redirectToRoute('profile_index');
        }

        try {
            $puuid  = $player->getRiotPuuid();
            $region = $player->getRiotRegion() ?? 'EUW1';

            $lolData  = $riot->getLolProfileAndRank($puuid, $region);
            $valoData = $riot->getValoProfileAndRank($puuid, $region);

            // LoL
            if (!isset($lolData['error'])) {
                $player->setRiotRank($lolData['rank'] ?? null);
                $player->setLolRank($lolData['rank'] ?? null);
                $player->setRiotTier($lolData['tier'] ?? null);
                $player->setRiotDivision($lolData['division'] ?? null);
                $player->setRiotLp($lolData['lp'] ?? 0);
            }

            // Valo
            if (!isset($valoData['error'])) {
                $player->setValoRank($valoData['rank'] ?? null);
            }

            $player->setUpdatedAt(new \DateTime('now'));
            $em->flush();

            $this->addFlash('success', 'Riot ranks refreshed.');
        } catch (\Throwable $e) {
            $this->addFlash('error', $e->getMessage());
        }

        return $this->redirectToRoute('profile_index');
    }

    /**
     * GET|POST /riot/disconnect → Délier le compte Riot
     */
    #[Route('/riot/disconnect', name: 'riot_disconnect', methods: ['GET', 'POST'])]
    public function disconnect(EntityManagerInterface $em): Response
    {
        /** @var \App\Entity\User|null $user */
        $user = $this->getUser();
        if (!$user) {
            $this->addFlash('error', 'You must be logged in.');
            return $this->redirectToRoute('ui_login');
        }

        $player = $user->getPlayer();
        if (!$player) {
            return $this->redirectToRoute('profile_index');
        }

        $player->setRiotPuuid(null);
        $player->setRiotGameName(null);
        $player->setRiotTagLine(null);
        $player->setRiotRank(null);
        $player->setRiotSummonerName(null);
        $player->setRiotTier(null);
        $player->setRiotDivision(null);
        $player->setRiotLp(0);
        $player->setLolRank(null);
        $player->setValoRank(null);
        $player->setRiotRegion(null);
        $player->setRiotLinkedAt(null);
        $player->setRiotAccountId(null);
        $player->setUpdatedAt(new \DateTime('now'));

        $em->flush();

        $this->addFlash('info', 'Riot account disconnected.');
        return $this->redirectToRoute('profile_index');
    }
}