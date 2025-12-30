<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once 'src/autoload.php';

use App\Config;
use App\Core\RiotClient;
use App\Core\FileCache;

echo "<h1>Diagnóstico de Conexión y Caché (XAMPP)</h1>";

// 1. Verificar lectura de la Key
$key = Config::RIOT_API_KEY;
$keyLength = strlen($key);
$firstPart = substr($key, 0, 8);

echo "<h3>1. Verificación de Configuración</h3>";
echo "Longitud de la Key: " . $keyLength . " caracteres<br>";
echo "Comienzo de la Key: <code>" . $firstPart . "...</code><br>";
echo "Carpeta de Caché: <code>" . Config::CACHE_PATH . "</code><br>";

if ($keyLength < 10 || strpos($key, 'XXXX') !== false) {
    die("<b style='color:red;'>❌ ERROR: La API Key no parece válida en src/Config.php</b>");
}

// 2. Intentar la petición con lógica de Caché
try {
    $client = new RiotClient(Config::RIOT_API_KEY, Config::DEFAULT_REGION);
    $cache = new FileCache(Config::CACHE_PATH);
    
    // Configura tus datos para la prueba
    $gameName = "RengoWilly"; 
    $tagLine = "LAS"; 
    
    // Generamos una clave única para este usuario en la caché
    $cacheKey = "account_data_" . md5($gameName . $tagLine);

    echo "<h3>2. Prueba de Flujo de Datos</h3>";
    
    // Intentamos obtener de la caché primero
    $account = $cache->get($cacheKey);

    if ($account) {
        echo "<b style='color:blue;'>ℹ️ INFO: ¡Datos recuperados desde la CACHÉ! (Sin peticiones a Riot)</b><br>";
    } else {
        echo "<b style='color:orange;'>ℹ️ INFO: La caché está vacía. Consultando a la API de Riot...</b><br>";
        
        $account = $client->getAccountByRiotId($gameName, $tagLine);

        if ($account) {
            // Guardamos en caché por 60 segundos para esta prueba
            $cache->set($cacheKey, $account, 60);
            echo "<b style='color:green;'>✅ Datos guardados en caché exitosamente por 60 segundos.</b><br>";
        }
    }

    if ($account) {
        echo "<br><b>Resultados del Usuario:</b><br>";
        echo "PUUID: " . $account['puuid'] . "<br>";
        echo "Nombre: " . $account['gameName'] . " #" . $account['tagLine'] . "<br>";
        
        echo "<p><i>💡 Sugerencia: Refresca la página. Deberías ver el mensaje azul indicando que los datos vienen de la caché.</i></p>";
    } else {
        echo "<b style='color:red;'>❌ 404: Usuario no encontrado.</b> Revisa el nombre y tag.";
    }

} catch (\Exception $e) {
    echo "<b style='color:red;'>❌ ERROR: " . $e->getMessage() . "</b>";
    
    if ($e->getCode() == 401) {
        echo "<br><br><b>Sugerencia:</b> Tu API Key no es válida. Genera una nueva en el portal de Riot.";
    }
}