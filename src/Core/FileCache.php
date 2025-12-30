<?php

namespace App\Core;

/**
 * FileCache: Maneja el almacenamiento persistente en archivos con TTL.
 */
class FileCache {
    private string $cacheDir;

    public function __construct(string $cacheDir) {
        $this->cacheDir = rtrim($cacheDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        if (!is_dir($this->cacheDir)) {
            mkdir($this->cacheDir, 0755, true);
        }
    }

    private function getFilePath(string $key): string {
        return $this->cacheDir . md5($key) . '.json';
    }

    public function set(string $key, $data, int $ttl): bool {
        $content = [
            'expires_at' => time() + $ttl,
            'data' => $data
        ];
        return file_put_contents($this->getFilePath($key), json_encode($content)) !== false;
    }

    public function get(string $key) {
        $path = $this->getFilePath($key);
        if (!file_exists($path)) return null;

        $content = json_decode(file_get_contents($path), true);
        if (!$content || time() > $content['expires_at']) {
            @unlink($path); // Eliminar si expiró
            return null;
        }

        return $content['data'];
    }
}
