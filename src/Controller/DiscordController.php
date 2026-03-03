<?php

namespace App\Controller;

use Doctrine\ORM\EntityManagerInterface;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class DiscordController extends AbstractController
{
    /**
     * ✅ Route appelée par Twig: path('connect_discord')
     * Redirige vers Discord OAuth
     */
    #[Route('/connect/discord', name: 'connect_discord', methods: ['GET'])]
    public function connect(ClientRegistry $clientRegistry): RedirectResponse
    {
        // scopes minimal : identify + email (email optionnel)
        return $clientRegistry->getClient('discord')->redirect(['identify', 'email']);
    }

    /**
     * ✅ Callback Discord
     * IMPORTANT: dans config/packages/knpu_oauth2_client.yaml tu dois avoir:
     * redirect_route: connect_discord_check
     */
    #[Route('/connect/discord/check', name: 'connect_discord_check', methods: ['GET'])]
    public function check(
        ClientRegistry $clientRegistry,
        EntityManagerInterface $em
    ): RedirectResponse {
        /** @var \App\Entity\User|null $user */
        $user = $this->getUser();
        if (!$user) {
            $this->addFlash('error', 'You must be logged in.');
            return $this->redirectToRoute('ui_login');
        }

        // Récupérer ou créer le Player associé
        $player = $user->getPlayer();
        if (!$player) {
            $player = new \App\Entity\Player();
            $player->setUser($user);
            $player->setNickname($user->getUsername() ?? 'Player');
            $em->persist($player);

            // Important: setter la relation inverse manuellement si non géré par persist
            $user->setPlayer($player);
        }

        try {
            /** @var \KnpU\OAuth2ClientBundle\Client\Provider\DiscordClient $client */
            $client = $clientRegistry->getClient('discord');
            $accessToken = $client->getAccessToken();
            $discordUser = $client->fetchUserFromToken($accessToken);

            $discordId = $discordUser->getId();
            $username  = $discordUser->getUsername(); // "name" Discord
            $data      = $discordUser->toArray();
            $avatar    = $data['avatar'] ?? null;

            $player->setDiscordId($discordId);
            $player->setDiscordUsername($username);
            $player->setDiscordAvatar($avatar);
            $player->setDiscordLinkedAt(new \DateTime('now'));

            // Optionnel : stocker url si tu utilises encore discord_avatar_url
            if ($discordId && $avatar) {
                $ext = str_starts_with($avatar, 'a_') ? 'gif' : 'png';
                $player->setDiscordAvatarUrl('https://cdn.discordapp.com/avatars/' . $discordId . '/' . $avatar . '.' . $ext);
            } else {
                $player->setDiscordAvatarUrl(null);
            }

            $em->flush();

            $this->addFlash('success', 'Discord connected successfully!');
        } catch (IdentityProviderException $e) {
            $this->addFlash('error', 'Discord OAuth error: ' . $e->getMessage());
        } catch (\Throwable $e) {
            $this->addFlash('error', 'Unexpected error: ' . $e->getMessage());
        }

        return $this->redirectToRoute('ui_profile');
    }

    /**
     * ✅ Route appelée par Twig: path('disconnect_discord')
     * Déconnecte Discord côté app (Option A: garde l'avatar)
     */
    #[Route('/disconnect/discord', name: 'disconnect_discord', methods: ['GET','POST'])]
    public function disconnect(EntityManagerInterface $em): Response
    {
        /** @var \App\Entity\User|null $user */
        $user = $this->getUser();
        $player = $user ? $user->getPlayer() : null;
        if (!$player) {
            throw $this->createAccessDeniedException();
        }

        // Option A: On garde l'avatar (discordAvatar et discordAvatarUrl)
        // Mais on supprime l'ID et l'état de connexion fonctionnel
        $player->setDiscordId(null);
        $player->setDiscordUsername(null);
        $player->setDiscordLinkedAt(null);

        $em->flush();

        $this->addFlash('info', 'Compte Discord déconnecté (données de profil conservées).');
        return $this->redirectToRoute('ui_profile');
    }
}