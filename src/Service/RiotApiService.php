<?php

namespace App\Service;

use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * RiotApiService — LoL + Valorant
 *
 * Endpoints utilisés :
 *  - Account-V1 (Riot)  : https://europe.api.riotgames.com/riot/account/v1/accounts/by-riot-id/{name}/{tag}
 *  - LoL Summoner-V4    : https://{region}.api.riotgames.com/lol/summoner/v4/summoners/by-puuid/{puuid}
 *  - LoL League-V4      : https://{region}.api.riotgames.com/lol/league/v4/entries/by-puuid/{puuid}
 *  - LoL Match-V5       : https://{routing}.api.riotgames.com/lol/match/v5/matches/by-puuid/{puuid}/ids
 *  - Valorant MMR (non-official Riot) : utilise Henrik Dev API (gratuit, pas de clé perso)
 *    → https://api.henrikdev.xyz/valorant/v1/by-puuid/mmr/{region}/{puuid}
 *    → https://api.henrikdev.xyz/valorant/v3/by-puuid/matches/{region}/{puuid}?size=3
 */
class RiotApiService
{
    private const ROUTING_MAP = [
        'EUW1'  => 'europe',
        'EUN1'  => 'europe',
        'TR1'   => 'europe',
        'RU'    => 'europe',
        'NA1'   => 'americas',
        'BR1'   => 'americas',
        'LA1'   => 'americas',
        'LA2'   => 'americas',
        'KR'    => 'asia',
        'JP1'   => 'asia',
        'OC1'   => 'sea',
    ];

    private const VALO_REGION_MAP = [
        'EUW1'  => 'eu',
        'EUN1'  => 'eu',
        'TR1'   => 'eu',
        'NA1'   => 'na',
        'BR1'   => 'br',
        'KR'    => 'kr',
        'AP'    => 'ap',
        'LA1'   => 'latam',
        'LA2'   => 'latam',
    ];

    public function __construct(
        private readonly HttpClientInterface $http,
        private readonly string $riotApiKey,
        private readonly LoggerInterface $logger,
    ) {}

    // =========================================================
    // PUBLIC API
    // =========================================================

    /**
     * Fetch PUUID + gameName + tagLine via Account-V1 by Riot ID
     * @throws \RuntimeException
     */
    public function fetchPuuidByRiotId(string $gameName, string $tagLine): array
    {
        $url = sprintf(
            'https://europe.api.riotgames.com/riot/account/v1/accounts/by-riot-id/%s/%s',
            rawurlencode($gameName),
            rawurlencode($tagLine)
        );
        $data = $this->getJson($url);
        if (empty($data['puuid'])) {
            throw new \RuntimeException('Riot account not found for ' . $gameName . '#' . $tagLine);
        }
        return $data; // ['puuid', 'gameName', 'tagLine']
    }

    /**
     * Fetch LoL profile + rank by PUUID
     * Returns array: [gameName, tagLine, summonerName, level, rank, tier, division, lp, region]
     */
    public function getLolProfileAndRank(string $puuid, string $region = 'EUW1'): array
    {
        try {
            // Summoner info
            $summonerUrl = sprintf(
                'https://%s.api.riotgames.com/lol/summoner/v4/summoners/by-puuid/%s',
                strtolower($region),
                $puuid
            );
            $summoner = $this->getJson($summonerUrl);

            // Rank info
            $leagueUrl = sprintf(
                'https://%s.api.riotgames.com/lol/league/v4/entries/by-puuid/%s',
                strtolower($region),
                $puuid
            );
            $leagueEntries = $this->getJson($leagueUrl);

            $rank = 'Unranked';
            $tier = null;
            $division = null;
            $lp = 0;

            foreach ($leagueEntries as $entry) {
                if (($entry['queueType'] ?? '') === 'RANKED_SOLO_5x5') {
                    $tier     = $entry['tier'] ?? null;
                    $division = $entry['rank'] ?? null;
                    $lp       = $entry['leaguePoints'] ?? 0;
                    $rank     = trim(($tier ?? '') . ' ' . ($division ?? '') . ' — ' . $lp . ' LP');
                    break;
                }
            }

            return [
                'summonerName' => $summoner['name'] ?? null,
                'level'        => $summoner['summonerLevel'] ?? null,
                'rank'         => $rank,
                'tier'         => $tier,
                'division'     => $division,
                'lp'           => $lp,
                'region'       => $region,
            ];
        } catch (\Throwable $e) {
            $this->logger->warning('[Riot] getLolProfileAndRank failed: ' . $e->getMessage());
            return ['error' => 'Données indisponibles', 'region' => $region];
        }
    }

    /**
     * Fetch last N LoL matches for a PUUID
     * Returns array of match summaries
     */
    public function getLolLastMatches(string $puuid, string $region = 'EUW1', int $limit = 3): array
    {
        try {
            $routing = self::ROUTING_MAP[$region] ?? 'europe';

            $idsUrl = sprintf(
                'https://%s.api.riotgames.com/lol/match/v5/matches/by-puuid/%s/ids?start=0&count=%d',
                $routing,
                $puuid,
                $limit
            );
            $matchIds = $this->getJson($idsUrl);

            $matches = [];
            foreach (array_slice($matchIds, 0, $limit) as $matchId) {
                $matchUrl = sprintf(
                    'https://%s.api.riotgames.com/lol/match/v5/matches/%s',
                    $routing,
                    $matchId
                );
                try {
                    $matchData = $this->getJson($matchUrl);
                    $info = $matchData['info'] ?? [];
                    $participants = $info['participants'] ?? [];

                    // Find current player participant
                    $participant = null;
                    foreach ($participants as $p) {
                        if (($p['puuid'] ?? '') === $puuid) {
                            $participant = $p;
                            break;
                        }
                    }

                    $gameStart = isset($info['gameStartTimestamp'])
                        ? (new \DateTimeImmutable())->setTimestamp((int)($info['gameStartTimestamp'] / 1000))
                        : null;

                    $matches[] = [
                        'matchId'   => $matchId,
                        'date'      => $gameStart?->format('d/m/Y H:i'),
                        'result'    => $participant ? ($participant['win'] ? 'Victory' : 'Defeat') : '—',
                        'champion'  => $participant['championName'] ?? '—',
                        'kda'       => $participant
                            ? (($participant['kills'] ?? 0) . '/' . ($participant['deaths'] ?? 0) . '/' . ($participant['assists'] ?? 0))
                            : '—',
                        'mode'      => $info['gameMode'] ?? '—',
                        'duration'  => isset($info['gameDuration'])
                            ? floor($info['gameDuration'] / 60) . 'm' . ($info['gameDuration'] % 60) . 's'
                            : '—',
                    ];
                } catch (\Throwable $e) {
                    $this->logger->warning('[Riot] Match detail failed for ' . $matchId . ': ' . $e->getMessage());
                    $matches[] = ['matchId' => $matchId, 'date' => '—', 'result' => '—', 'champion' => '—', 'kda' => '—', 'mode' => '—', 'duration' => '—'];
                }
            }
            return $matches;
        } catch (\Throwable $e) {
            $this->logger->warning('[Riot] getLolLastMatches failed: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Fetch Valorant rank info via Henrik Dev API (public, no personal key)
     * Returns array: [name, tag, rank, elo, region]
     */
    public function getValoProfileAndRank(string $puuid, string $region = 'EUW1'): array
    {
        try {
            $valoRegion = self::VALO_REGION_MAP[$region] ?? 'eu';
            $url = sprintf(
                'https://api.henrikdev.xyz/valorant/v1/by-puuid/mmr/%s/%s',
                $valoRegion,
                $puuid
            );
            $response = $this->http->request('GET', $url, [
                'timeout' => 5,
                'headers' => ['User-Agent' => 'ArenaMind/1.0'],
            ]);

            if ($response->getStatusCode() >= 400) {
                return ['error' => 'Valorant data unavailable', 'region' => $valoRegion];
            }

            $data = $response->toArray(false)['data'] ?? [];

            return [
                'rank'       => $data['currenttierpatched'] ?? 'Unranked',
                'elo'        => $data['elo'] ?? null,
                'mmr_change' => $data['mmr_change_to_last_game'] ?? null,
                'region'     => $valoRegion,
            ];
        } catch (\Throwable $e) {
            $this->logger->warning('[Riot] getValoProfileAndRank failed: ' . $e->getMessage());
            return ['error' => 'Données indisponibles', 'region' => 'eu'];
        }
    }

    /**
     * Fetch last N Valorant matches via Henrik Dev API
     */
    public function getValoLastMatches(string $puuid, string $region = 'EUW1', int $limit = 3): array
    {
        try {
            $valoRegion = self::VALO_REGION_MAP[$region] ?? 'eu';
            $url = sprintf(
                'https://api.henrikdev.xyz/valorant/v3/by-puuid/matches/%s/%s?size=%d',
                $valoRegion,
                $puuid,
                $limit
            );
            $response = $this->http->request('GET', $url, [
                'timeout' => 5,
                'headers' => ['User-Agent' => 'ArenaMind/1.0'],
            ]);

            if ($response->getStatusCode() >= 400) {
                return [];
            }

            $rawMatches = $response->toArray(false)['data'] ?? [];
            $matches = [];

            foreach (array_slice($rawMatches, 0, $limit) as $m) {
                $metadata  = $m['metadata'] ?? [];
                $players   = $m['players']['all_players'] ?? [];
                $teams     = $m['teams'] ?? [];

                // Find the current player in the match
                $playerData = null;
                foreach ($players as $p) {
                    if (($p['puuid'] ?? '') === $puuid) {
                        $playerData = $p;
                        break;
                    }
                }

                $myTeamColor = $playerData['team'] ?? null;
                $myTeamWon   = false;
                if ($myTeamColor) {
                    $teamKey = strtolower($myTeamColor);
                    $myTeamWon = (bool) ($teams[$teamKey]['has_won'] ?? false);
                }

                $gameStart = isset($metadata['game_start'])
                    ? (new \DateTimeImmutable())->setTimestamp($metadata['game_start'])
                    : null;

                $matches[] = [
                    'matchId'  => $metadata['matchid'] ?? '—',
                    'date'     => $gameStart?->format('d/m/Y H:i') ?? '—',
                    'result'   => $playerData ? ($myTeamWon ? 'Victory' : 'Defeat') : '—',
                    'map'      => $metadata['map'] ?? '—',
                    'mode'     => $metadata['mode'] ?? '—',
                    'agent'    => $playerData['character'] ?? '—',
                    'kda'      => $playerData
                        ? (($playerData['stats']['kills'] ?? 0) . '/' . ($playerData['stats']['deaths'] ?? 0) . '/' . ($playerData['stats']['assists'] ?? 0))
                        : '—',
                    'score'    => $playerData['stats']['score'] ?? '—',
                ];
            }

            return $matches;
        } catch (\Throwable $e) {
            $this->logger->warning('[Riot] getValoLastMatches failed: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Full linking: fetch puuid + set lol rank + valo rank in one shot
     * Returns ['puuid', 'gameName', 'tagLine', 'lolRank', 'valoRank']
     */
    public function linkAccountAndFetchRank(string $region, string $gameName, string $tagLine): array
    {
        if ($gameName === '' || $tagLine === '') {
            throw new \RuntimeException('gameName et tagLine sont requis.');
        }

        $account  = $this->fetchPuuidByRiotId($gameName, $tagLine);
        $puuid    = $account['puuid'];

        // LoL rank
        $lolData  = $this->getLolProfileAndRank($puuid, $region);
        $lolRank  = isset($lolData['error']) ? null : ($lolData['rank'] ?? null);

        // Valo rank (best-effort)
        $valoData = $this->getValoProfileAndRank($puuid, $region);
        $valoRank = isset($valoData['error']) ? null : ($valoData['rank'] ?? null);

        return [
            'puuid'    => $puuid,
            'gameName' => $account['gameName'] ?? $gameName,
            'tagLine'  => $account['tagLine'] ?? $tagLine,
            'rank'     => $lolRank ?? 'Unranked',   // legacy compat
            'lolRank'  => $lolRank,
            'valoRank' => $valoRank,
        ];
    }

    /**
     * Fetch LoL rank by PUUID (legacy compat for riot_refresh)
     */
    public function fetchRankByPuuid(string $region, string $puuid): string
    {
        $data = $this->getLolProfileAndRank($puuid, $region);
        return $data['rank'] ?? 'Unranked';
    }

    // =========================================================
    // PRIVATE HELPERS
    // =========================================================

    private function getJson(string $url): array
    {
        $response = $this->http->request('GET', $url, [
            'timeout' => 8,
            'headers' => [
                'X-Riot-Token' => $this->riotApiKey,
            ],
        ]);

        $status = $response->getStatusCode();
        if ($status === 404) {
            throw new \RuntimeException('Riot resource not found (404): ' . $url);
        }
        if ($status >= 400) {
            throw new \RuntimeException(sprintf('Riot API error %d for %s', $status, $url));
        }

        return $response->toArray(false);
    }
}