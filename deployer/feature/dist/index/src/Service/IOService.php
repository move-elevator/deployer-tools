<?php

namespace MoveElevator\FeatureIndex\Service;

use MoveElevator\FeatureIndex\Api\JiraApi;
use MoveElevator\FeatureIndex\Model\Entry;
use MoveElevator\FeatureIndex\Utility\EntryUtility;

class IOService
{
    private const BYTES_PER_GB = 1000 ** 3;
    private const DATABASE_ASSIGNMENTS_PATH = __DIR__ . '/../../../database_assignments.json';

    public string $basePath;

    /**
     * @param string $path
     * @return array
     */
    public function getDirectoryEntries(string $path): array
    {
        $this->basePath = $path;
        $baseDirectory = opendir($path);

        $entryUtility = new EntryUtility();
        $directoryEntries = [];
        while ($pathEntry = readdir($baseDirectory)) {
            if (($pathEntry[0] != '.') && ($pathEntry != '..') && is_dir($pathEntry)) {
                $directoryEntries[] = $entryUtility->generateEntry($pathEntry, $this->basePath);
            }
        }

        $config = (new ConfigReader())->initConfig();
        (new JiraApi($config['jira']['api'], $config['jira']['auth']))->loadIssues($directoryEntries);

        return $directoryEntries;
    }

    public function getEntryAppPath(Entry $entry): string {
        $configReader = new ConfigReader();
        $config = $configReader->initConfig();
        $urlPattern = $config['featureUrlPattern'] ?? '';

        if ('' !== $urlPattern) {
            // subdomain mode: an absolute url instead of a path relative to the index page
            return str_replace('<feature>', $entry->getName(), $urlPattern) . ltrim($config['defaultApplicationPath'], '/');
        }

        return $entry->getName() . $config['defaultApplicationPath'];
    }

    private function getDiskTotalSpace(): float {
        return round(disk_total_space('.') / self::BYTES_PER_GB, 2);
    }

    public function getDiskTotalFree(): float {
        return round(disk_free_space('.') / self::BYTES_PER_GB, 2);
    }

    public function getDiskFullSpacePercent(): float {
        $freeSpacePercent = round($this->getDiskTotalFree() * 100 / $this->getDiskTotalSpace(), 2);
        return round(100 - $freeSpacePercent, 2);
    }

    /**
     * Mirrors the warn/fail thresholds of the requirements recipe's disk space check
     * (requirements_disk_space_warn_percent/requirements_disk_space_fail_percent, 80/95),
     * ordered by descending threshold so the first matching level wins.
     */
    private const DISK_SPACE_LEVELS = [
        'red' => ['threshold' => 95, 'color' => '#D84315'],
        'yellow' => ['threshold' => 80, 'color' => '#F9A825'],
        'green' => ['threshold' => 0, 'color' => '#33691E'],
    ];

    public function getDiskSpaceStatus(): string {
        $percent = $this->getDiskFullSpacePercent();
        foreach (self::DISK_SPACE_LEVELS as $status => $level) {
            if ($percent >= $level['threshold']) {
                return $status;
            }
        }
        return 'green';
    }

    public function getDiskSpaceColor(): string {
        return self::DISK_SPACE_LEVELS[$this->getDiskSpaceStatus()]['color'];
    }

    /**
     * Number of pool databases (see Simple database manager) currently assigned to a feature
     * instance, read from the same database_assignments.json the deploy tasks maintain.
     */
    public function getUsedDatabaseCount(): int
    {
        if (!file_exists(self::DATABASE_ASSIGNMENTS_PATH)) {
            return 0;
        }

        $assignments = json_decode(file_get_contents(self::DATABASE_ASSIGNMENTS_PATH), true);
        return is_array($assignments) ? count($assignments) : 0;
    }

    public function directoryExists(string $path, bool $forceCreate = false): bool
    {
        if (!is_dir($path)) {
            if ($forceCreate) {
                return mkdir($path, 0775, true);
            }
            return false;
        }
        return true;
    }

}
