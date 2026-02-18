<?php

declare(strict_types=1);

namespace MoveElevator\DeployerTools\Database\Manager;

use MoveElevator\DeployerTools\Database\Exception\DatabaseException;

use function Deployer\debug;
use function Deployer\get;
use function Deployer\set;
use function Deployer\has;
use function Deployer\run;
use function Deployer\input;
use function Deployer\upload;
use function Deployer\runExtended;
use function Deployer\test;

/**
 * Database Management "Simple"
 *
 * This manager supports the database management via a simple user with limited privileges. The idea behind this is to use a fixed pool of existing databases.
 */
class Simple extends AbstractManager implements ManagerInterface
{
    private readonly FileAssignmentManager $assignmentManager;

    public function __construct()
    {
        $this->assignmentManager = new FileAssignmentManager();
    }
    public function create(): void
    {
        debug('Creating database');
        $this->ensureDatabasePoolExists();

        if (!$this->hasFreeAssignments() && null === $this->assignmentManager->getAssignment($this->getFeatureName())) {
            throw DatabaseException::noFreeDatabases();
        }

        $database = $this->assignmentManager->getAssignment($this->getFeatureName()) ?: $this->getFreeAssignment();
        $databaseConfiguration = $this->getDatabaseConfiguration($database);
        $this->checkAssignmentConfiguration($databaseConfiguration);
        $this->assignmentManager->updateAssignment($database, $this->getFeatureName());
        $this->initDatabaseConfiguration($database);
    }

    public function delete(string $feature): void
    {
        debug('Deleting database');
        $this->ensureDatabasePoolExists();
        $this->initDatabaseConfiguration(feature: $feature);
        $this->assignmentManager->removeAssignment($feature);
        $this->run($this->generateDropTablesQuery($this->getDatabaseName($feature)));
    }

    public function exists(?string $feature = null): bool
    {
        debug('Check database exists');
        $feature = $this->getFeatureName($feature);
        $this->ensureDatabasePoolExists();
        $databaseName = $this->getDatabaseName($feature);

        if (empty($databaseName)) {
            return false;
        }

        $this->initDatabaseConfiguration(feature: $feature);
        $databaseExistsCommand = sprintf(
            "SELECT COUNT(*) AS count FROM information_schema.tables WHERE table_schema = '%s';",
            $databaseName
        );
        $result = $this->run($databaseExistsCommand);
        $lines = explode("\n", trim($result));
        $count = isset($lines[1]) ? (int)trim($lines[1]) : 0;
        return $count > 0;
    }

    public function getDatabaseName(?string $feature = null): string
    {
        $feature = $feature ?: input()->getOption('feature');
        $databaseAssignment = $this->assignmentManager->getAssignment($feature);

        if (!$databaseAssignment) {
            return '';
        }
        return $this->getDatabaseConfiguration($databaseAssignment)['database_name'] ?? '';
    }


    private function getFreeAssignment(): ?string
    {
        $used = $this->assignmentManager->getUsedDatabases();
        $pool = get('database_pool');
        $free = array_diff(array_keys($pool), $used);
        return $free ? array_shift($free) : null;
    }

    private function hasFreeAssignments(): bool
    {
        return !empty(array_diff(array_keys(get('database_pool')), $this->assignmentManager->getUsedDatabases()));
    }

    private function getDatabaseConfiguration(string $database): array
    {
        $pool = get('database_pool');
        if (!isset($pool[$database])) {
            throw DatabaseException::configurationMissing("Database '{$database}' in pool");
        }

        return $pool[$database];
    }

    private function initDatabaseConfiguration(?string $database = null, ?string $feature = null): void
    {
        $pool = get('database_pool');
        if (!$database) {
            $database = $this->assignmentManager->getAssignment($feature ?: $this->getFeatureName());
        }

        if (!isset($pool[$database])) {
            throw DatabaseException::configurationMissing("Database '{$database}' in pool");
        }

        $config = $pool[$database];
        foreach ($config as $key => $value) {
            if (str_starts_with((string) $key, 'DEPLOYER_CONFIG_')) {
                set($key, getenv($value) ?: $value);
                continue;
            }
            set($key, $value);
        }
    }

    private function checkAssignmentConfiguration(array $assignment): void
    {
        $requiredKeys = ['database_user', 'database_password', 'database_name'];
        $isValid = !array_diff_key(array_flip($requiredKeys), array_filter($assignment));

        if (!$isValid) {
            throw new \RuntimeException(sprintf(
                'Invalid database assignment configuration. Required keys: %s',
                implode(', ', $requiredKeys)
            ));
        }
    }

    private function ensureDatabasePoolExists(): void
    {
        if (!has('database_pool')) {
            throw DatabaseException::poolNotConfigured();
        }
    }


    private function generateDropTablesQuery(string $database): string
    {
        return preg_replace('/\s*\R\s*/', ' ', trim(sprintf(<<<'EOT'
        SET FOREIGN_KEY_CHECKS = 0;
        SET GROUP_CONCAT_MAX_LEN = 32768;

        SELECT concat('DROP TABLE IF EXISTS `', table_name, '`;')
        FROM information_schema.tables
        WHERE table_schema = '%s';

        SET FOREIGN_KEY_CHECKS = 1;
        EOT, $database)));
    }
}
