<?php

declare(strict_types=1);

namespace MoveElevator\DeployerTools\Utility;

use function Deployer\get;
use function Deployer\run;
use function Deployer\test;

class EnvUtility
{
    /**
     * Get remote environment variables from .env file
     *
     * @param string $envFilePath
     * @return array
     * @throws \Exception
     */
    public static function getRemoteEnvVars(string $envFilePath = '/.env'): array
    {
        $activePath = get('deploy_path') . '/' . (test('[ -L {{deploy_path}}/release ]') ? 'release' : 'current');

        if (!test("[ -e {$activePath}{$envFilePath} ]")) {
            // no env file found, maybe not initialized?
            return [];
        }
        $remoteEnv = run('cat ' . $activePath . $envFilePath);
        $lines = array_filter(explode(PHP_EOL, $remoteEnv));
        $vars = [];

        foreach ($lines as $line) {
            if (str_starts_with(trim($line), '#')) {
                continue;
            }
            $result = explode('=', $line, 2);
            if (count($result) < 2) {
                continue;
            }
            $vars[trim($result[0])] = str_replace('\'', '', trim($result[1]));
        }

        return $vars;
    }
}
