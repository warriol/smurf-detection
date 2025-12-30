<?php

/**
 * Autoloader minimalista compatible con PSR-4.
 * Convierte App\Core\FileCache en src/Core/FileCache.php
 */
spl_autoload_register(function ($class) {
    $prefix = 'App\\';
    $base_dir = __DIR__ . '/'; // Apunta a la carpeta src/

    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }

    $relative_class = substr($class, $len);
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';

    if (file_exists($file)) {
        require $file;
    }
});