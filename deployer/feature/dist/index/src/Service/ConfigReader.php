<?php

namespace MoveElevator\FeatureIndex\Service;

class ConfigReader
{
    private const CONFIG_PATH = __DIR__ . '/../../../index.config.php';

    private static ?array $config = null;

    public function initConfig(): array
    {
        return self::$config ??= require self::CONFIG_PATH;
    }
}
