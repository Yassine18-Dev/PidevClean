<?php

namespace App\Controller;

use App\Repository\InvitationRepository;
use App\Repository\PlayerRepository;
use App\Repository\TeamRepository;
use App\Service\RiotApiService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ProfileController extends AbstractController
{
    #[Route('/profile-v2', name: 'profile_index', methods: ['GET'])]
    public function index(
        EntityManagerInterface $em,
        PlayerRepository $playerRepo,
        TeamRepository $teamRepo,
        InvitationRepository $invitationRepo,
        RiotApiService $riotApi
    ): Response {
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('ui_login');
        }

        // last activity
        if (method_exists($user, 'setLastActivityAt')) {
            $user->setLastActivityAt(new \DateTimeImmutable());
            $em->flush();
        }

        // Player associé au user (relation 1-1)
        $player = method_exists($user, 'getPlayer') ? $user->getPlayer() : null;

        // Top players / teams (simple)
        $topPlayers = $playerRepo->findBy([], ['id' => 'DESC'], 5);
        $topTeams   = $teamRepo->findBy([], ['id' => 'DESC'], 5);

        // Invitations reçues (pending)
        $receivedInvitations = $player ? $invitationRepo->findBy([
            'player' => $player,
            'status' => 'pending'
        ], ['createdAt' => 'DESC']) : [];

        // ─── Riot Data ────────────────────────────────────────────
        $riotLinked = $player && $player->getRiotPuuid() !== null;
        $lolData    = null;
        $valoData   = null;

        if ($riotLinked) {
            $puuid  = $player->getRiotPuuid();
            $region = $player->getRiotRegion() ?? 'EUW1';

            try {
                $lolData = $riotApi->getLolProfileAndRank($puuid, $region);
                if (!isset($lolData['error'])) {
                    $lolData['lastMatches'] = $riotApi->getLolLastMatches($puuid, $region, 3);
                }
            } catch (\Throwable $e) {
                $lolData = ['error' => true];
            }

            try {
                $valoData = $riotApi->getValoProfileAndRank($puuid, $region);
                if (!isset($valoData['error'])) {
                    $valoData['lastMatches'] = $riotApi->getValoLastMatches($puuid, $region, 3);
                }
            } catch (\Throwable $e) {
                $valoData = ['error' => true];
            }
        }
        // ─────────────────────────────────────────────────────────

        return $this->render('front/profile.html.twig', [
            'user'               => $user,
            'player'             => $player,
            'topPlayers'         => $topPlayers,
            'topTeams'           => $topTeams,
            'receivedInvitations'=> $receivedInvitations,
            // Discord
            'discordLinked'      => $player && $player->getDiscordId(),
            // Riot
            'riotLinked'         => $riotLinked,
            'lolData'            => $lolData,
            'valoData'           => $valoData,
        ]);
    }
}