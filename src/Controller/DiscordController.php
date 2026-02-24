<?php

namespace App\Controller;

use App\Entity\Player;
use Doctrine\ORM\EntityManagerInterface;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class DiscordController extends AbstractController
{
    #[Route('/connect/discord', name: 'connect_discord')]
    public function connectDiscord(ClientRegistry $clientRegistry): RedirectResponse
    {
        // ⚠️ Le client doit s'appeler "discord" dans knpu_oauth2_client.yaml
        return $clientRegistry
            ->getClient('discord')
            ->redirect(['identify', 'email'], []);
    }

    #[Route('/connect/discord/check', name: 'connect_discord_check')]
    public function connectDiscordCheck(
        Request $request,
        ClientRegistry $clientRegistry,
        EntityManagerInterface $em
    ): RedirectResponse {
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('ui_login');
        }

        // récupère Player associé (OneToOne)
        $player = method_exists($user, 'getPlayer') ? $user->getPlayer() : null;
        if (!$player instanceof Player) {
            // si pas de player, on revient au profil
            return $this->redirectToRoute('ui_profile');
        }

        // OAuth: fetch user
        $client = $clientRegistry->getClient('discord');
        $discordUser = $client->fetchUser();

        // wohali/oauth2-discord-new retourne généralement ces champs
        $discordId = method_exists($discordUser, 'getId') ? $discordUser->getId() : null;

        // Selon provider, le username peut être "username" ou "global_name"
        $data = method_exists($discordUser, 'toArray') ? $discordUser->toArray() : [];
        $username = $data['username'] ?? $data['global_name'] ?? null;

        // Avatar (optionnel)
        $avatar = $data['avatar'] ?? null;
        $avatarUrl = null;
        if ($discordId && $avatar) {
            $avatarUrl = "https://cdn.discordapp.com/avatars/{$discordId}/{$avatar}.png";
        }

        if ($discordId) {
            $player->setDiscordId((string) $discordId);
            if ($username) {
                $player->setDiscordUsername((string) $username);
            }
            if ($avatarUrl) {
                $player->setDiscordAvatarUrl($avatarUrl);
            }
            $player->setDiscordLinkedAt(new \DateTime());
            $em->flush();

            $this->addFlash('success', 'Discord connecté ✅');
        } else {
            $this->addFlash('danger', 'Impossible de récupérer l’utilisateur Discord.');
        }

        return $this->redirectToRoute('ui_profile');
    }

    #[Route('/disconnect/discord', name: 'disconnect_discord')]
    public function disconnectDiscord(EntityManagerInterface $em): RedirectResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('ui_login');
        }

        $player = method_exists($user, 'getPlayer') ? $user->getPlayer() : null;
        if ($player instanceof Player) {
            $player->setDiscordId(null);
            $player->setDiscordUsername(null);
            $player->setDiscordAvatarUrl(null);
            $player->setDiscordLinkedAt(null);
            $em->flush();
        }

        $this->addFlash('success', 'Discord déconnecté ✅');
        return $this->redirectToRoute('ui_profile');
    }
}