<?php

namespace MoveElevator\FeatureIndex\Service;

use MoveElevator\FeatureIndex\Model\Entry;
use MoveElevator\FeatureIndex\Utility\EntryUtility;

class IOService
{
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


        return $entryUtility->sortDirectoryEntries($directoryEntries);
    }

    public function getEntryAppPath(Entry $entry): string {
        $configReader = new ConfigReader();
        $config = $configReader->initConfig();
        return $entry->getName() . $config['defaultApplicationPath'];
    }

    public function getDiskTotalSpace(): float {
        return round(disk_total_space('.') / (1024 * (pow(10, 6))), 2);
    }

    public function getDiskTotalFree(): float {
        return round(disk_free_space('.') / (1024 * (pow(10, 6))), 2);
    }

    public function getDiskFullSpacePercent(): float {
        $freeSpacePercent = round($this->getDiskTotalFree() * 100 / $this->getDiskTotalSpace(), 2);
        return round(100 - $freeSpacePercent, 2);
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
