<?php

namespace App\Services;

use App\Core\RiotClient;
use App\Core\FileCache;
use App\Config;

class LiveGameService {
    private RiotClient $client;
    private FileCache $cache;

    public function __construct(RiotClient $client, FileCache $cache) {
        $this->client = $client;
        $this->cache = $cache;
    }

    /**
     * Orquestador principal: Obtiene la partida y analiza a cada jugador.
     */
    public function analyzeCurrentGame(string $gameName, string $tagLine) {
        // 1. Obtener cuenta inicial para tener el PUUID
        $account = $this->client->getAccountByRiotId($gameName, $tagLine);
        if (!$account) {
            throw new \Exception("Jugador no encontrado en esta región.", 404);
        }

        // 2. Obtener partida en vivo usando el PUUID
        $liveGame = $this->client->getLiveGameByPuuid($account['puuid']);
        if (!$liveGame) {
            throw new \Exception("El jugador no está en una partida activa.", 404);
        }

        $processedPlayers = [];

        // 3. Analizar a cada uno de los 10 participantes
        foreach ($liveGame['participants'] as $player) {
            $processedPlayers[] = $this->analyzePlayer($player);
        }

        return [
            'gameId' => $liveGame['gameId'],
            'gameMode' => $liveGame['gameMode'],
            'gameLength' => $liveGame['gameLength'] ?? 0,
            'players' => $processedPlayers
        ];
    }

    /**
     * Analiza un jugador individualmente (con caché para evitar 429)
     */
    private function analyzePlayer(array $participant) {
        $puuid = $participant['puuid'];
        // Cacheamos el análisis del jugador por el tiempo definido en Config (TTL_ACCOUNT)
        $cacheKey = "player_analysis_" . $puuid;

        $cached = $this->cache->get($cacheKey);
        if ($cached) {
            // Actualizamos solo los datos que cambian por partida (campeón y equipo)
            $cached['championId'] = $participant['championId'];
            $cached['teamId'] = $participant['teamId'];
            $cached['riotId'] = ($participant['riotId'] ?? 'Desconocido');
            return $cached;
        }

        // A. Obtener datos básicos de invocador para el Nivel
        $summoner = $this->client->getSummonerByPuuid($puuid);
        
        // Validación defensiva: Si no hay summoner o no tiene ID, devolvemos un perfil básico
        if (!$summoner || !isset($summoner['id'])) {
            return [
                'riotId' => ($participant['riotId'] ?? 'Desconocido'),
                'level' => 0,
                'rank' => 'ERROR',
                'status' => 'Desconocido',
                'smurfScore' => 0,
                'reasons' => ['No se pudo obtener información del servidor'],
                'championId' => $participant['championId'],
                'teamId' => $participant['teamId']
            ];
        }

        // B. Obtener Ligas (Rank, Winrate)
        $leagues = $this->client->getLeagueEntries($summoner['id']);
        
        $soloQ = null;
        if ($leagues && is_array($leagues)) {
            foreach ($leagues as $l) {
                if (isset($l['queueType']) && $l['queueType'] === 'RANKED_SOLO_5x5') {
                    $soloQ = $l;
                    break;
                }
            }
        }

        // C. Calcular Score
        $analysis = $this->calculateSmurfScore($summoner, $soloQ);
        
        // Datos de la partida actual
        $analysis['riotId'] = ($participant['riotId'] ?? 'Desconocido');
        $analysis['teamId'] = $participant['teamId'];
        $analysis['championId'] = $participant['championId'];

        // Guardar en caché el análisis pesado
        $this->cache->set($cacheKey, $analysis, Config::TTL_ACCOUNT);

        return $analysis;
    }

    /**
     * Lógica del Smurf Score
     */
    private function calculateSmurfScore(array $summoner, ?array $soloQ) {
        $score = 0;
        $reasons = [];
        
        $level = $summoner['summonerLevel'] ?? 0;
        $winrate = 0;
        $totalGames = 0;

        // Regla 1: Nivel de cuenta
        if ($level > 0 && $level < 40) {
            $score += 40;
            $reasons[] = "Nivel muy bajo ($level)";
        } elseif ($level > 0 && $level < 70) {
            $score += 20;
            $reasons[] = "Cuenta nueva ($level)";
        }

        // Regla 2: Winrate y cantidad de partidas
        if ($soloQ) {
            $totalGames = ($soloQ['wins'] ?? 0) + ($soloQ['losses'] ?? 0);
            if ($totalGames > 0) {
                $winrate = ($soloQ['wins'] / $totalGames) * 100;

                if ($winrate > 65 && $totalGames > 5) {
                    $score += 40;
                    $reasons[] = "Winrate sospechoso (" . round($winrate, 1) . "%)";
                }

                if ($totalGames < 20) {
                    $score += 20;
                    $reasons[] = "Muy pocas partidas rankeds ($totalGames)";
                }
            }
        }

        // Clasificación final
        $status = "Normal";
        if ($score >= 70) $status = "Smurf Probable";
        elseif ($score >= 40) $status = "Sospechoso";

        return [
            'level' => $level,
            'rank' => $soloQ ? ($soloQ['tier'] . " " . $soloQ['rank']) : 'UNRANKED',
            'lp' => $soloQ ? ($soloQ['leaguePoints'] ?? 0) : 0,
            'winrate' => round($winrate, 1),
            'totalGames' => $totalGames,
            'smurfScore' => $score,
            'reasons' => $reasons,
            'status' => $status
        ];
    }
}