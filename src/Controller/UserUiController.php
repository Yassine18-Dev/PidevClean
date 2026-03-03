<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\InvitationRepository;
use App\Service\DiscordAvatarService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class UserUiController extends AbstractController
{
    #[Route('/profile', name: 'ui_profile', methods: ['GET'])]
    public function profile(
        EntityManagerInterface $em,
        InvitationRepository $invitationRepository,
        DiscordAvatarService $discordAvatarService,
        \App\Service\RiotApiService $riotApi
    ): Response {
        /** @var User|null $user */
        $user = $this->getUser();
        if (!$user) return $this->redirectToRoute('ui_login');

        $user->setLastActivityAt(new \DateTimeImmutable());
        $em->flush();

        $player = method_exists($user, 'getPlayer') ? $user->getPlayer() : null;
        $receivedInvitations = $player ? $invitationRepository->findPendingForPlayer($player) : [];
        $discordAvatar = $discordAvatarService->getAvatarUrl($player);

        // ─── Riot Data ────────────────────────────────────────────
        $riotLinked  = $player && $player->getRiotPuuid();
        $lolData     = null;
        $valoData    = null;
        $lolMatches  = [];
        $valoMatches = [];

        if ($riotLinked) {
            $puuid  = $player->getRiotPuuid();
            $region = $player->getRiotRegion() ?? 'EUW1';

            try {
                $lolData    = $riotApi->getLolProfileAndRank($puuid, $region);
                $lolMatches = $riotApi->getLolLastMatches($puuid, $region, 3);
            } catch (\Throwable) {
                $lolData = ['error' => 'Données indisponibles'];
            }

            try {
                $valoData    = $riotApi->getValoProfileAndRank($puuid, $region);
                $valoMatches = $riotApi->getValoLastMatches($puuid, $region, 3);
            } catch (\Throwable) {
                $valoData = ['error' => 'Données indisponibles'];
            }
        }
        // ─────────────────────────────────────────────────────────

        return $this->render('front/profile.html.twig', [
            'user'                => $user,
            'player'              => $player,
            'receivedInvitations' => $receivedInvitations,
            'discordAvatar'       => $discordAvatar,
            // Discord
            'discordLinked'       => $player && $player->getDiscordId(),
            // Riot
            'riotLinked'          => (bool) $riotLinked,
            'lolData'             => $lolData,
            'lolMatches'          => $lolMatches,
            'valoData'            => $valoData,
            'valoMatches'         => $valoMatches,
        ]);
    }


    #[Route('/profile/edit', name: 'ui_profile_edit', methods: ['GET','POST'])]
    public function editProfile(
        Request $request,
        EntityManagerInterface $em,
        ValidatorInterface $validator
    ): Response {
        /** @var User|null $user */
        $user = $this->getUser();
        if (!$user) return $this->redirectToRoute('ui_login');

        if ($request->isMethod('POST')) {
            $user->setUsername(trim((string)$request->request->get('username')));
            $user->setEmail(trim((string)$request->request->get('email')));
            $user->setFavoriteGame((string)$request->request->get('favoriteGame'));
            $user->setBio((string)$request->request->get('bio'));

            $errors = $validator->validate($user);
            if (count($errors) > 0) {
                foreach ($errors as $e) $this->addFlash('error', $e->getPropertyPath().': '.$e->getMessage());
                return $this->redirectToRoute('ui_profile_edit');
            }

            $em->flush();
            $this->addFlash('success', 'Profile updated.');
            return $this->redirectToRoute('ui_profile');
        }

        return $this->render('front/edit_profile.html.twig', ['user' => $user]);
    }
}
