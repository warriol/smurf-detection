<?php

namespace App;

class Config {
    // REEMPLAZA ESTO CON TU KEY REAL
    const RIOT_API_KEY = 'RGAPI-785c0cbe-0f53-41ab-a367-a3d3f6a9afff';
    
    // Configuración por defecto
    const DEFAULT_REGION = 'la2';
    
    // Ruta de caché (Fuera de public_html si es posible)
    // En iFastNet, si tu web está en /home/user/public_html, esto irá a /home/user/cache_storage
    const CACHE_PATH = __DIR__ . '/../../cache_storage/';
    
    // TTLs en segundos
    const TTL_ACCOUNT = 86400 * 7; // 1 semana
    const TTL_LIVE_GAME = 120;     // 2 minutos
}