# smurf-detection
COnsumir API de RIOT para detectar SMUR en aprtidas.


# Estrcutura de capreta

/ (root del hosting)
├── cache_storage/          # Fuera de la vista pública (donde se guardan los archivos .cache)
├── logs/                   # Errores y logs del sistema
-----------------------------------------------------------------------------------------------
└── public_html/            # Archivos accesibles vía web
    ├── index.html          # Frontend (SPA)
    ├── api.php             # Punto de entrada de la API
    └── src/                # Código fuente PHP
        ├── Config.php      # Constantes y API Keys
        ├── Core/           # Clases base (Cache, Http)
        ├── Services/       # Lógica de Riot y Smurf Detection
        └── Utils/          # Helpers (Formateo de datos)


# Consultas partidas en vivo

El flujo lógico será:

    Entrada: Riot ID (Nombre#Tag).

    Paso A: Obtener el PUUID del usuario.

    Paso B: Consultar si ese PUUID está en una partida activa.

    Paso C: Para cada uno de los 10 participantes, obtener:

        Nivel de cuenta (Summoner-v4).

        Rango, Victorias y Derrotas (League-v4).

    Paso D: Calcular el "Smurf Score" basado en esos datos.