<?php

namespace MoveElevator\FeatureIndex\Api;

use MoveElevator\FeatureIndex\Model\Entry;
use MoveElevator\FeatureIndex\Service\IOService;
use MoveElevator\FeatureIndex\Utility\EntryUtility;

abstract class AbstractApi
{

    const CACHE_PATH = __DIR__ . '/../../var/cache/';
    const CACHE_LIFETIME = 300;

    protected function getCache(string $issue, $cachePath = self::CACHE_PATH, $cacheLifeTime = self::CACHE_LIFETIME) {
        $ioService = new IOService();

        $ioService->directoryExists($cachePath, true);

        $filePath = $cachePath . $issue;
        if (file_exists($filePath)) {
            $createDate = filectime($filePath);

            if (($createDate + $cacheLifeTime) > time()) {
                return json_decode(file_get_contents($filePath),true);
            }
        }
        return false;
    }

    protected function setCache(string $issue, string $content, $cachePath = self::CACHE_PATH) {
        $ioService = new IOService();
        $ioService->directoryExists($cachePath, true);
        $filePath = $cachePath . $issue;

        umask(0002);
        file_put_contents($filePath, $content);
    }

    // todo: cleanup
    public function cleanUpCache($cachePath = self::CACHE_PATH, $cacheLifeTime = self::CACHE_LIFETIME) {
        $ioService = new IOService();
        if (!$ioService->directoryExists($cachePath)) return;
        foreach (array_diff(scandir($cachePath), array('.', '..')) as $cacheFile) {
            $createDate = filectime($cacheFile);
            if (($createDate + $cacheLifeTime) <= time()) {
                unlink($cacheFile);
            }
        }
    }
}
