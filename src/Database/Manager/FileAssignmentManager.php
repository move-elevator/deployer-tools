<?php

declare(strict_types=1);

namespace MoveElevator\DeployerTools\Database\Manager;

use function Deployer\get;
use function Deployer\test;
use function Deployer\upload;
use function Deployer\runExtended;

class FileAssignmentManager
{
    private readonly string $filePath;

    public function __construct()
    {
        $this->filePath = get('deploy_base_path') . '/' . get('feature_directory_path') . '/database_assignments.json';
    }

    public function readAssignments(): array
    {
        if (!test("[ -f {$this->filePath} ]")) {
            return [];
        }

        $content = runExtended("cat {$this->filePath}", real_time_output: false);
        $decoded = json_decode($content, true);

        if (JSON_ERROR_NONE !== json_last_error()) {
            throw new \RuntimeException('Failed to decode database assignments: ' . json_last_error_msg());
        }

        return $decoded ?: [];
    }

    public function writeAssignments(array $assignments): void
    {
        $content = json_encode($assignments, JSON_PRETTY_PRINT);
        if (false === $content) {
            throw new \RuntimeException('Failed to encode database assignments.');
        }

        $tempFile = '.deployer.database_assignments.tmp';
        file_put_contents($tempFile, $content);
        upload($tempFile, $this->filePath, ['progress_bar' => false, 'display_stats' => false]);
        unlink($tempFile);
    }

    public function getAssignment(string $feature): ?string
    {
        return $this->readAssignments()[$feature] ?? null;
    }

    public function updateAssignment(string $database, string $feature): void
    {
        $assignments = $this->readAssignments();
        $assignments[$feature] = $database;
        $this->writeAssignments($assignments);
    }

    public function removeAssignment(string $feature): void
    {
        $assignments = $this->readAssignments();
        unset($assignments[$feature]);
        $this->writeAssignments($assignments);
    }

    public function getUsedDatabases(): array
    {
        return array_values($this->readAssignments());
    }
}
