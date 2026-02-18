<?php

declare(strict_types=1);

namespace MoveElevator\DeployerTools\Database\Manager;

use function Deployer\debug;
use function Deployer\get;
use function Deployer\has;

/**
 * Database Management "Root"
 *
 * This manager supports the database management via a root user with full privileges.
 */
class Root extends AbstractManager implements ManagerInterface
{
    public function create(): void
    {
        debug('Creating database');
        $databaseName = $this->getDatabaseName();
        $additionalParams = '';

        if (has('database_collation')) {
            $additionalParams .= ' COLLATE ' . get('database_collation');
        }

        if (has('database_charset')) {
            $additionalParams .= ' CHARACTER SET ' . get('database_charset');
        }

        $this->run("CREATE DATABASE IF NOT EXISTS `$databaseName`{$additionalParams};", false);
    }

    public function delete(string $feature): void
    {
        debug('Deleting database');
        $databaseName = $this->getDatabaseName($feature);
        $databaseRemoveCommand = "DROP DATABASE IF EXISTS `$databaseName`;";
        $this->run($databaseRemoveCommand);
    }

    public function exists(?string $feature = null): bool
    {
        debug('Check database exists');
        $feature = $this->getFeatureName($feature);
        $databaseName = $this->getDatabaseName($feature);
        $databaseExistsCommand = "SHOW DATABASES LIKE '$databaseName';";
        $result = $this->run($databaseExistsCommand);
        return !empty(trim($result));
    }
}
