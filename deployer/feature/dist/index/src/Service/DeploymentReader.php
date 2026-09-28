<?php

namespace MoveElevator\FeatureIndex\Service;

use MoveElevator\FeatureIndex\Model\Deployment;

class DeploymentReader
{
    public function read(string $instancePath, string $basePath): Deployment
    {
        $fallbackTimestamp = (int)filemtime($instancePath);
        $deployPath = $this->findDeployPath($instancePath, $basePath);
        if ($deployPath === null) {
            return new Deployment($fallbackTimestamp);
        }

        $release = $this->readLastRelease($deployPath . '/.dep/releases_log');
        $timestamp = strtotime((string)($release['created_at'] ?? ''));

        return new Deployment(
            $timestamp !== false ? $timestamp : $fallbackTimestamp,
            (string)($release['user'] ?? ''),
            (string)($release['release_name'] ?? ''),
            is_file($deployPath . '/.dep/deploy.lock') ? $this->readFile($deployPath . '/.dep/deploy.lock') : null,
        );
    }

    /**
     * An instance is either the deploy path itself or, with the url shortener, a symlink to its
     * current/<web_path>, so walk up from the resolved path to the directory holding .dep/
     */
    private function findDeployPath(string $instancePath, string $basePath): ?string
    {
        $base = realpath($basePath);
        $directory = realpath($instancePath);

        while ($directory !== false && $directory !== $base && $directory !== dirname($directory)) {
            if (is_dir($directory . '/.dep')) {
                return $directory;
            }
            $directory = dirname($directory);
        }

        return null;
    }

    /**
     * Deployer appends one JSON line per release to .dep/releases_log, the last one is the latest
     */
    private function readLastRelease(string $releasesLog): array
    {
        $lines = is_readable($releasesLog) ? file($releasesLog, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) : false;
        $release = $lines ? json_decode((string)end($lines), true) : null;

        return is_array($release) ? $release : [];
    }

    private function readFile(string $path): string
    {
        return is_readable($path) ? trim((string)file_get_contents($path)) : '';
    }
}
