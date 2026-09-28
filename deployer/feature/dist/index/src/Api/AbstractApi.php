<?php

namespace MoveElevator\FeatureIndex\Api;

use MoveElevator\FeatureIndex\Service\IOService;

abstract class AbstractApi
{

    const CACHE_PATH = __DIR__ . '/../../var/cache/';
    const CACHE_LIFETIME = 300;

    // cache files are PHP files returning the data, since the cache directory is inside the web root
    // and must not serve the cached responses as plain text
    protected function getCache(string $key, string $cachePath = self::CACHE_PATH, int $cacheLifeTime = self::CACHE_LIFETIME): ?array
    {
        $filePath = $cachePath . $key . '.php';
        if (!file_exists($filePath) || (filemtime($filePath) + $cacheLifeTime) <= time()) {
            return null;
        }

        $data = require $filePath;
        return is_array($data) ? $data : null;
    }

    protected function setCache(string $key, array $data, string $cachePath = self::CACHE_PATH): void
    {
        $ioService = new IOService();
        $ioService->directoryExists($cachePath, true);

        umask(0002);
        file_put_contents($cachePath . $key . '.php', "<?php\n\nreturn " . var_export($data, true) . ";\n", LOCK_EX);
    }
}
