<?php

declare(strict_types=1);

namespace Deployer;

use Symfony\Component\Console\Helper\Table;

const REQUIREMENT_OK = 'OK';
const REQUIREMENT_WARN = 'WARN';
const REQUIREMENT_FAIL = 'FAIL';
const REQUIREMENT_SKIP = 'SKIP';

/**
 * PHP byte-based settings compared with >= after converting to bytes.
 *
 * @see parsePhpBytes()
 */
const REQUIREMENT_BYTE_SETTINGS = [
    'memory_limit',
    'post_max_size',
    'upload_max_filesize',
];

/**
 * PHP numeric settings compared with >= as integers.
 */
const REQUIREMENT_NUMERIC_SETTINGS = [
    'max_execution_time',
    'max_input_vars',
    'opcache.memory_consumption',
];

function addRequirementRow(string $check, string $status, string $info = ''): void
{
    $rows = get('requirements_rows');
    $rows[] = [
        'check' => $check,
        'status' => $status,
        'info' => $info,
    ];
    set('requirements_rows', $rows);
}

function formatRequirementStatus(string $status): string
{
    return match ($status) {
        REQUIREMENT_OK => '<fg=green>[OK]</>',
        REQUIREMENT_WARN => '<fg=yellow>[WARN]</>',
        REQUIREMENT_FAIL => '<fg=red>[FAIL]</>',
        REQUIREMENT_SKIP => '<fg=gray>[SKIP]</>',
        default => "[$status]",
    };
}

function renderRequirementsTable(): void
{
    $rows = get('requirements_rows');

    $tableRows = array_map(
        static fn(array $row): array => [
            $row['check'],
            formatRequirementStatus($row['status']),
            $row['info'],
        ],
        $rows
    );

    $hostAlias = currentHost()->getAlias();

    (new Table(output()))
        ->setHeaderTitle("Server Requirements ($hostAlias)")
        ->setHeaders(['Check', 'Status', 'Info'])
        ->setRows($tableRows)
        ->render();

    $counts = array_count_values(array_column($rows, 'status'));
    $ok = $counts[REQUIREMENT_OK] ?? 0;
    $fail = $counts[REQUIREMENT_FAIL] ?? 0;
    $warn = $counts[REQUIREMENT_WARN] ?? 0;
    $skip = $counts[REQUIREMENT_SKIP] ?? 0;

    writeln('');
    writeln(sprintf(
        '<fg=green>%d passed</>, <fg=red>%d failed</>, <fg=yellow>%d warnings</>, <fg=gray>%d skipped</>',
        $ok,
        $fail,
        $warn,
        $skip
    ));
}

function parsePhpBytes(string $value): int
{
    $value = trim($value);

    if ($value === '-1') {
        return -1;
    }

    $unit = strtoupper(substr($value, -1));
    $numericValue = (int) $value;

    return match ($unit) {
        'G' => $numericValue * 1073741824,
        'M' => $numericValue * 1048576,
        'K' => $numericValue * 1024,
        default => $numericValue,
    };
}

function meetsPhpRequirement(string $actual, string $expected, string $setting): bool
{
    if (in_array($setting, REQUIREMENT_BYTE_SETTINGS, true)) {
        $actualBytes = parsePhpBytes($actual);
        $expectedBytes = parsePhpBytes($expected);

        if ($actualBytes === -1) {
            return true;
        }

        if ($expectedBytes === -1) {
            return false;
        }

        return $actualBytes >= $expectedBytes;
    }

    if (in_array($setting, REQUIREMENT_NUMERIC_SETTINGS, true)) {
        $actualInt = (int) $actual;
        $expectedInt = (int) $expected;

        if ($setting === 'max_execution_time' && $actualInt === 0) {
            return true;
        }

        return $actualInt >= $expectedInt;
    }

    return $actual === $expected;
}

/**
 * @return array<string, string>
 */
function getSharedEnvVars(): array
{
    $envFile = get('requirements_env_file');
    $envPath = get('deploy_path') . '/shared/' . $envFile;

    if (!test("[ -f $envPath ]")) {
        return [];
    }

    $content = run("cat $envPath");
    $lines = array_filter(explode("\n", $content));
    $vars = [];

    foreach ($lines as $line) {
        $line = trim($line);

        if ($line === '' || str_starts_with($line, '#')) {
            continue;
        }

        $parts = explode('=', $line, 2);

        if (count($parts) < 2) {
            continue;
        }

        $value = trim($parts[1]);

        if ((str_starts_with($value, '"') && str_ends_with($value, '"'))
            || (str_starts_with($value, "'") && str_ends_with($value, "'"))) {
            $value = substr($value, 1, -1);
        }

        $vars[trim($parts[0])] = $value;
    }

    return $vars;
}
