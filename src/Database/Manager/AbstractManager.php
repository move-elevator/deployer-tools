<?php

namespace MoveElevator\DeployerTools\Database\Manager;

use MoveElevator\DeployerTools\Database\Exception\DatabaseException;
use MoveElevator\DeployerTools\Utility\VarUtility;

use function Deployer\get;
use function Deployer\run;
use function Deployer\input;
use function Deployer\runExtended;
use function Deployer\test;

abstract class AbstractManager
{
    public function run(string $command, $useDoubleQuotes = true): string
    {
        try {
            $databaseUser = get('database_user');
            $databaseHost = get('database_host');
            $databasePort = get('database_port');
            $databasePassword = VarUtility::getDatabasePassword();
            $quote = $useDoubleQuotes ? '"' : '\'';

            if (empty($databaseUser) || empty($databaseHost) || empty($databasePassword)) {
                throw DatabaseException::configurationMissing('database connection parameters');
            }

            return runExtended(get('mysql') . " -u$databaseUser -p'%secret%' -h$databaseHost -P$databasePort -e {$quote}$command{$quote}", [], null, null, $databasePassword, real_time_output: false);
        } catch (\Exception $e) {
            throw DatabaseException::connectionFailed($e->getMessage(), $e);
        }
    }

    /**
     * Generate a database name
     * @param ?string $feature
     * @return string
     */
    public function getDatabaseName(?string $feature = null): string
    {
        $feature = $feature ?: input()->getOption('feature');
        $project = get('project');
        return substr($this->getFeatureName("{$project}--{$feature}"), 0, 63);
    }


    /**
     * @param string|null $feature
     * @return array|string|string[]|null
     */
    public function getFeatureName(?string $feature = null)
    {
        $feature = $feature ?: input()->getOption('feature');

        return preg_replace('/[^A-Za-z0-9\_\-.]/', '', (string) $feature);
    }
}
