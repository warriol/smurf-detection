<?php
header('Content-Type: application/json');

require_once 'src/autoload.php';

use App\Config;
use App\Core\RiotClient;
use App\Core\FileCache;
use App\Services\LiveGameService;

$name = $_GET['name'] ?? null;
$tag = $_GET['tag'] ?? null;

if (!$name || !$tag) {
    echo json_encode(['error' => 'Faltan parámetros name o tag']);
    exit;
}

try {
    $cache = new FileCache(Config::CACHE_PATH);
    $client = new RiotClient(Config::RIOT_API_KEY, Config::DEFAULT_REGION);
    $service = new LiveGameService($client, $cache);

    $data = $service->analyzeCurrentGame($name, $tag);
    echo json_encode($data);

} catch (\Exception $e) {
    http_response_code($e->getCode() == 404 ? 404 : 500);
    echo json_encode([
        'error' => $e->getMessage()
    ]);
}