<?php

namespace App\Core;

class RiotClient {
    private string $apiKey;
    private string $region;

    public function __construct(string $apiKey, string $region) {
        $this->apiKey = trim($apiKey);
        $this->region = strtolower($region);
    }

    public function request(string $endpoint, string $platform = null) {
        $baseUrl = "https://" . ($platform ?: $this->region) . ".api.riotgames.com";
        $url = $baseUrl . $endpoint;

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ["X-Riot-Token: " . $this->apiKey]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode === 429) throw new \Exception("Rate limit excedido.", 429);
        if ($httpCode === 404) return null;
        if ($httpCode !== 200) throw new \Exception("Error Riot API: " . $httpCode);

        return json_decode($response, true);
    }

    public function getAccountByRiotId(string $gameName, string $tagLine) {
        $cluster = $this->getClusterByRegion($this->region);
        return $this->request("/riot/account/v1/accounts/by-riot-id/" . rawurlencode($gameName) . "/" . rawurlencode($tagLine), $cluster);
    }

    public function getLiveGameByPuuid(string $puuid) {
        return $this->request("/lol/spectator/v5/active-games/by-summoner/" . $puuid);
    }

    public function getSummonerByPuuid(string $puuid) {
        return $this->request("/lol/summoner/v4/summoners/by-puuid/" . $puuid);
    }

    public function getLeagueEntries(string $summonerId) {
        return $this->request("/lol/league/v4/entries/by-summoner/" . $summonerId);
    }

    private function getClusterByRegion(string $region): string {
        $mapping = [
            'la1' => 'americas', 'la2' => 'americas', 'na1' => 'americas', 'br1' => 'americas',
            'euw1' => 'europe', 'eun1' => 'europe', 'tr1' => 'europe', 'ru' => 'europe',
            'kr' => 'asia', 'jp1' => 'asia'
        ];
        return $mapping[$region] ?? 'americas';
    }
}