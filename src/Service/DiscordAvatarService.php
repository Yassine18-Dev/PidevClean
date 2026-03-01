<?php

namespace App\Service;

use App\Entity\Player;

class DiscordAvatarService
{
    public function getAvatarUrl(?Player $player): ?string
    {
        if (!$player) return null;

        $id = $player->getDiscordId();
        $hash = $player->getDiscordAvatar();

        if ($id && $hash) {
            $ext = str_starts_with($hash, 'a_') ? 'gif' : 'png';
            return sprintf('https://cdn.discordapp.com/avatars/%s/%s.%s', $id, $hash, $ext);
        }

        // fallback legacy
        return $player->getDiscordAvatarUrl();
    }
}