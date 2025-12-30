<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once 'src/autoload.php';

use App\Config;
use App\Core\RiotClient;

echo "<h1>Diagnóstico de Conexión (XAMPP)</h1>";

// 1. Verificar lectura de la Key
$key = Config::RIOT_API_KEY;
$keyLength = strlen($key);
$firstPart = substr($key, 0, 8);

echo "<h3>1. Verificación de Configuración</h3>";
echo "Longitud de la Key: " . $keyLength . " caracteres (Debería ser aprox 42)<br>";
echo "Comienzo de la Key: <code>" . $firstPart . "...</code><br>";

if ($keyLength < 10 || strpos($key, 'XXXX') !== false) {
    die("<b style='color:red;'>❌ ERROR: La API Key no parece válida en src/Config.php</b>");
}

// 2. Intentar la petición
try {
    $client = new RiotClient(Config::RIOT_API_KEY, Config::DEFAULT_REGION);
    
    // Cambia esto por tu Riot ID real para la prueba definitiva
    $gameName = "RengoWilly"; 
    $tagLine = "LAS"; 

    echo "<h3>2. Petición a Riot</h3>";
    echo "Endpoint: <code>/riot/account/v1/accounts/by-riot-id/</code><br>";
    echo "Buscando: <code>$gameName #$tagLine</code><br>";
    echo "Región (Base): <code>" . Config::DEFAULT_REGION . "</code> (Cluster: americas)<br><hr>";

    $account = $client->getAccountByRiotId($gameName, $tagLine);

    if ($account) {
        echo "<b style='color:green;'>✅ ¡CONEXIÓN EXITOSA!</b><br>";
        echo "PUUID: " . $account['puuid'];
    } else {
        echo "<b style='color:orange;'>⚠️ 404: Usuario no encontrado.</b><br>";
        echo "Esto significa que la Key FUNCIONA, pero el nombre o tag están mal, o no pertenecen a la región configurada.";
    }

} catch (\Exception $e) {
    echo "<b style='color:red;'>❌ ERROR: " . $e->getMessage() . "</b>";
    
    if ($e->getCode() == 401) {
        echo "<br><br><b>Sugerencia:</b> Ve al portal de Riot, genera una 'New Development Key', cópiala de nuevo y asegúrate de guardar el archivo Config.php en XAMPP.";
    }
}