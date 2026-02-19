<?php

declare(strict_types=1);

namespace Deployer;

use Deployer\Exception\RunException;
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
    'pcre.jit', // Treated as numeric (>= 1) to ensure enabled
];

/**
 * Required MySQL/MariaDB grants on global level (*.*) for Root mode.
 */
const REQUIREMENT_DATABASE_GRANTS = [
    'SELECT',
    'INSERT',
    'UPDATE',
    'DELETE',
    'CREATE',
    'DROP',
    'INDEX',
    'ALTER',
    'CREATE TEMPORARY TABLES',
    'LOCK TABLES',
    'EXECUTE',
    'CREATE VIEW',
    'SHOW VIEW',
    'CREATE ROUTINE',
    'ALTER ROUTINE',
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
 * Try to detect the version of a CLI tool on the remote server.
 */
function detectPackageVersion(string $command): ?string
{
    $versionCmd = match ($command) {
        'exiftool' => 'exiftool -ver 2>&1 | head -1',
        default => "$command --version 2>&1 | head -1",
    };

    try {
        $output = trim(run($versionCmd));

        if ($output !== '' && preg_match('/(\d+[\d.]*)/', $output, $matches)) {
            return $matches[1];
        }
    } catch (RunException) {
        // Command doesn't support version flag
    }

    return null;
}

/**
 * Detect the database product and version from the remote server.
 *
 * @return array{product: string, label: string, version: string, cycle: string}|null
 */
function detectDatabaseProduct(): ?array
{
    foreach (['mariadb', 'mysql'] as $command) {
        try {
            $versionOutput = trim(run("$command --version 2>/dev/null"));

            if ($versionOutput === '') {
                continue;
            }

            if (str_contains($versionOutput, 'MariaDB') && (
                preg_match('/Distrib\s+((\d+\.\d+)[\d.]*)/', $versionOutput, $matches)
                || preg_match('/((\d+\.\d+)[\d.]*)-MariaDB/', $versionOutput, $matches)
            )) {
                return ['product' => 'mariadb', 'label' => 'MariaDB', 'version' => $matches[1], 'cycle' => $matches[2]];
            }

            if (preg_match('/Distrib\s+((\d+\.\d+)[\d.]*)/', $versionOutput, $matches)
                || preg_match('/Ver\s+((\d+\.\d+)[\d.]*)/', $versionOutput, $matches)
            ) {
                return ['product' => 'mysql', 'label' => 'MySQL', 'version' => $matches[1], 'cycle' => $matches[2]];
            }
        } catch (RunException) {
            continue;
        }
    }

    return null;
}

/**
 * Fetch release cycles from endoflife.date API.
 *
 * @return list<array{name: string, isEol: bool, eolFrom: ?string, isEoas: ?bool, eoasFrom: ?string, isMaintained: bool}>|null
 */
function fetchEolCycles(string $product, int $timeout = 5): ?array
{
    $url = sprintf('https://endoflife.date/api/v1/products/%s/', urlencode($product));

    $context = stream_context_create([
        'http' => [
            'timeout' => $timeout,
            'header' => "Accept: application/json\r\nUser-Agent: move-elevator/deployer-tools\r\n",
        ],
    ]);

    $response = @file_get_contents($url, false, $context);

    if ($response === false) {
        return null;
    }

    $data = json_decode($response, true);

    if (!is_array($data) || !isset($data['result']['releases'])) {
        return null;
    }

    return $data['result']['releases'];
}

/**
 * Find the matching release cycle for a major.minor version.
 *
 * @param list<array{name: string}> $cycles
 * @return array{name: string, isEol: bool, eolFrom: ?string, isEoas: ?bool, eoasFrom: ?string, isMaintained: bool}|null
 */
function findEolCycle(array $cycles, string $majorMinor): ?array
{
    foreach ($cycles as $cycle) {
        if ($cycle['name'] === $majorMinor) {
            return $cycle;
        }
    }

    return null;
}

/**
 * Evaluate EOL status and add a requirement row.
 */
function evaluateEolStatus(string $label, array $cycle, int $warnMonths): void
{
    $now = new \DateTimeImmutable();

    if ($cycle['isEol'] ?? false) {
        $eolDate = $cycle['eolFrom'] ?? 'unknown';
        addRequirementRow("EOL: $label", REQUIREMENT_FAIL, "End of Life since $eolDate");

        return;
    }

    $eolFrom = $cycle['eolFrom'] ?? null;

    if ($eolFrom !== null) {
        try {
            $eolDate = new \DateTimeImmutable($eolFrom);
        } catch (\Exception) {
            addRequirementRow("EOL: $label", REQUIREMENT_SKIP, "Invalid EOL date from API: $eolFrom");

            return;
        }

        $warnDate = $eolDate->modify("-{$warnMonths} months");

        if ($now >= $warnDate) {
            $interval = $now->diff($eolDate);

            if ($interval->invert) {
                addRequirementRow("EOL: $label", REQUIREMENT_FAIL, "End of Life since $eolFrom");

                return;
            }

            $months = $interval->y * 12 + $interval->m;
            $remaining = $months > 0 ? "in $months month(s)" : 'imminent';
            addRequirementRow("EOL: $label", REQUIREMENT_WARN, "EOL $remaining ($eolFrom)");

            return;
        }
    }

    $isEoas = $cycle['isEoas'] ?? false;

    if ($isEoas) {
        $info = 'Security support only';
        $info .= $eolFrom !== null ? ", EOL $eolFrom" : '';
        addRequirementRow("EOL: $label", REQUIREMENT_WARN, $info);

        return;
    }

    $info = 'Maintained';
    $info .= $eolFrom !== null ? " until $eolFrom" : '';
    addRequirementRow("EOL: $label", REQUIREMENT_OK, $info);
}

/**
 * Check a single product against the endoflife.date API.
 */
function checkEolForProduct(string $label, string $product, string $cycle, int $warnMonths, int $timeout): void
{
    $cycles = fetchEolCycles($product, $timeout);

    if ($cycles === null) {
        addRequirementRow("EOL: $label", REQUIREMENT_SKIP, 'Could not reach endoflife.date API');

        return;
    }

    $match = findEolCycle($cycles, $cycle);

    if ($match === null) {
        addRequirementRow("EOL: $label", REQUIREMENT_SKIP, "Cycle $cycle not found in API");

        return;
    }

    evaluateEolStatus("$label $cycle", $match, $warnMonths);
}

/**
 * Check if a service is active via systemctl, with pgrep fallback.
 *
 * Returns the matched service/process name or null if none found.
 */
function isServiceActive(string ...$names): ?string
{
    $hasSystemctl = test('command -v systemctl > /dev/null 2>&1');

    foreach ($names as $name) {
        try {
            if ($hasSystemctl) {
                $status = trim(run("systemctl is-active $name 2>/dev/null || true"));

                if ($status === 'active') {
                    return $name;
                }
            } elseif (test("pgrep -x $name > /dev/null 2>&1")) {
                return $name;
            }
        } catch (RunException) {
            continue;
        }
    }

    return null;
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

        $key = trim(preg_replace('/^export\s+/', '', trim($parts[0])));
        $vars[$key] = $value;
    }

    return $vars;
}

/**
 * Resolve database credentials without triggering an interactive prompt.
 *
 * Resolution chain:
 * 1. Deployer config (database_user, database_password)
 * 2. Environment variable DEPLOYER_CONFIG_DATABASE_PASSWORD
 * 3. Shared .env file (TYPO3/Symfony-specific parsing)
 *
 * @return array{user: string, password: string, host: string, port: int}|null
 */
function resolveDatabaseCredentials(): ?array
{
    $user = has('database_user') ? (string) get('database_user') : '';
    $password = has('database_password') ? (string) get('database_password') : '';
    $host = has('database_host') ? (string) get('database_host') : '127.0.0.1';
    $port = has('database_port') ? (int) get('database_port') : 3306;

    if ($password === '') {
        $envPassword = getenv('DEPLOYER_CONFIG_DATABASE_PASSWORD');

        if (is_string($envPassword) && $envPassword !== '') {
            $password = $envPassword;
        }
    }

    if ($user === '' || $password === '') {
        $envVars = getSharedEnvVars();

        if (has('app_type') && get('app_type') === 'typo3') {
            if ($user === '') {
                $user = $envVars['TYPO3_CONF_VARS__DB__Connections__Default__user'] ?? '';
            }

            if ($password === '') {
                $key = has('env_key_database_passwort')
                    ? get('env_key_database_passwort')
                    : 'TYPO3_CONF_VARS__DB__Connections__Default__password';
                $password = $envVars[$key] ?? '';
            }

            if ($host === '127.0.0.1') {
                $envHost = $envVars['TYPO3_CONF_VARS__DB__Connections__Default__host'] ?? '';

                if ($envHost !== '') {
                    $host = $envHost;
                }
            }
        } elseif (has('app_type') && get('app_type') === 'symfony') {
            $databaseUrl = $envVars['DATABASE_URL'] ?? '';

            if ($databaseUrl !== '') {
                if ($user === '') {
                    $parsed = parse_url($databaseUrl, PHP_URL_USER);
                    $user = is_string($parsed) ? urldecode($parsed) : '';
                }

                if ($password === '') {
                    $parsed = parse_url($databaseUrl, PHP_URL_PASS);
                    $password = is_string($parsed) ? urldecode($parsed) : '';
                }

                if ($host === '127.0.0.1') {
                    $parsed = parse_url($databaseUrl, PHP_URL_HOST);

                    if (is_string($parsed) && $parsed !== '') {
                        $host = $parsed;
                    }
                }

                if ($port === 3306) {
                    $parsed = parse_url($databaseUrl, PHP_URL_PORT);

                    if (is_int($parsed)) {
                        $port = $parsed;
                    }
                }
            }
        }
    }

    if ($user === '' || $password === '') {
        return null;
    }

    return [
        'user' => $user,
        'password' => $password,
        'host' => $host,
        'port' => $port,
    ];
}

/**
 * Parse SHOW GRANTS output and check required grants on global level (*.*).
 *
 * @param string $grantsOutput Raw output from SHOW GRANTS FOR CURRENT_USER()
 * @return array{ok: bool, missing: list<string>}
 */
function parseGlobalGrants(string $grantsOutput): array
{
    $globalGrants = [];

    foreach (explode("\n", $grantsOutput) as $line) {
        $line = trim($line);

        if (!preg_match('/^GRANT\s+(.+?)\s+ON\s+\*\.\*\s+TO\s+/i', $line, $matches)) {
            continue;
        }

        $grantsStr = strtoupper(trim($matches[1]));

        if ($grantsStr === 'ALL PRIVILEGES' || $grantsStr === 'ALL') {
            return ['ok' => true, 'missing' => []];
        }

        foreach (explode(', ', $grantsStr) as $grant) {
            $globalGrants[] = trim($grant);
        }
    }

    $missing = array_values(array_diff(REQUIREMENT_DATABASE_GRANTS, $globalGrants));

    return ['ok' => empty($missing), 'missing' => $missing];
}
