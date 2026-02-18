<?php

declare(strict_types=1);

namespace MoveElevator\DeployerTools\Database\Manager;

use Deployer\Exception\Exception;
use GuzzleHttp\Exception\GuzzleException;
use Mittwald\ApiClient\Error\UnexpectedResponseException;
use Mittwald\ApiClient\Generated\V2\Clients\Database\DeleteMysqlDatabase\DeleteMysqlDatabaseRequest;
use Mittwald\ApiClient\Generated\V2\Clients\Database\GetMysqlUser\GetMysqlUserRequest;
use Mittwald\ApiClient\Generated\V2\Schemas\Database\CharacterSettings;
use Mittwald\ApiClient\Generated\V2\Schemas\Database\DatabaseUserStatus;
use Mittwald\ApiClient\Generated\V2\Schemas\Database\MySqlUser;
use Mittwald\ApiClient\MittwaldAPIV2Client;
use Mittwald\ApiClient\Generated\V2\Clients\Database\ListMysqlDatabases\ListMysqlDatabasesRequest;
use Mittwald\ApiClient\Generated\V2\Schemas\Database\CreateMySqlDatabase;
use Mittwald\ApiClient\Generated\V2\Schemas\Database\CreateMySqlUserWithDatabase;
use Mittwald\ApiClient\Generated\V2\Schemas\Database\CreateMySqlUserWithDatabaseAccessLevel;
use Mittwald\ApiClient\Generated\V2\Clients\Database\CreateMysqlDatabase\CreateMysqlDatabaseRequest;
use Mittwald\ApiClient\Generated\V2\Clients\Database\CreateMysqlDatabase\CreateMysqlDatabaseRequestBody;
use Mittwald\ApiClient\Generated\V2\Schemas\Database\MySqlDatabase;
use Mittwald\ApiClient\Generated\V2\Clients\Database\GetMysqlDatabase\GetMysqlDatabaseRequest;
use MoveElevator\DeployerTools\Utility\VarUtility;

use function Deployer\debug;
use function Deployer\info;
use function Deployer\get;
use function Deployer\set;
use function Deployer\has;
use function Deployer\run;
use function Deployer\input;
use function Deployer\upload;
use function Deployer\test;

/**
 * Database Management "MittwaldApi"
 *
 * This manager supports the database management via the Mittwald API.
 */
class MittwaldApi extends AbstractManager implements ManagerInterface
{
    private MittwaldAPIV2Client $client;

    /**
     * @throws GuzzleException
     * @throws UnexpectedResponseException
     * @throws \Exception
     */
    public function create(): void
    {
        debug('Creating database');
        $response = $this->initClient()
            ->database()
            ->createMysqlDatabase(
                new CreateMysqlDatabaseRequest(
                    projectId: get('mittwald_project_id'),
                    body: new CreateMysqlDatabaseRequestBody(
                        database: (new CreateMySqlDatabase(
                            $this->getFeatureName(),
                            get('mittwald_project_id'),
                            get('mittwald_database_version', '8.4')
                        ))->withCharacterSettings(
                            new CharacterSettings(
                                characterSet: get('mittwald_database_character_set', 'utf8mb4'),
                                collation: get('mittwald_database_collation', 'utf8mb4_unicode_ci')
                            )
                        ),
                        user: new CreateMySqlUserWithDatabase(
                            CreateMySqlUserWithDatabaseAccessLevel::full,
                            VarUtility::getDatabasePassword()
                        )
                    )
                )
            );

        $responseBody = $response->getBody();

        // Mittwald database creation is asynchronous. After the API returns 201, the database
        // and user are not immediately available. Mittwald support recommends polling the MySQL
        // user status via GetMysqlUser until it reports "ready" (Mittwald Support, 03.02.2026).
        //
        // Additionally, even after the user is "ready", the DNS entry for the database host
        // (e.g. mysql-xyz.pg-s-xxx.db.project.host) may not be resolvable yet and can flap
        // intermittently for several minutes. The TCP connectivity check below catches the
        // initial delay, and the immediate hostname-to-IP resolution eliminates DNS dependency
        // for all subsequent operations (template rendering, db_sync_tool, TYPO3 CLI, etc.).
        $ready = $this->checkForDatabaseReadyStatus(
            $responseBody->getUserId(),
            (int) get('mittwald_database_wait', 30),
            (int) get('mittwald_database_retries', 20),
        );
        if (!$ready) {
            throw new \RuntimeException('Database is not ready for feature ' . $this->getFeatureName() . ' after waiting period.');
        }

        $database = $this->getDatabase($responseBody->getId());

        $reachable = $this->checkDatabaseHostReachable(
            $database->getHostName(),
            (int) get('mittwald_database_wait', 30),
            (int) get('mittwald_database_retries', 20),
        );
        if (!$reachable) {
            throw new \RuntimeException(
                'Database host ' . $database->getHostName() . ' is not reachable for feature '
                . $this->getFeatureName() . ' after waiting period.'
            );
        }

        // Resolve hostname to IP immediately while DNS is known to be working.
        // Mittwald DNS entries flap intermittently for several minutes after database creation.
        // By resolving now, the .env template will contain the IP address, eliminating DNS
        // dependency for db_sync_tool, TYPO3 CLI, and all subsequent remote commands.
        $databaseHost = $this->resolveHostnameToIp($database->getHostName());

        $user = $this->getDatabaseUser($responseBody->getUserId());

        $this->initDatabaseConfiguration($database, $user, $databaseHost);
    }

    /**
     * @throws GuzzleException
     * @throws UnexpectedResponseException
     */
    public function delete(string $feature): void
    {
        debug('Deleting database');
        $database = $this->getDatabaseByFeature($this->getFeatureName($feature));
        if (null === $database) {
            throw new \RuntimeException('Database not found for feature: ' . $feature);
        }

        $response = $this->initClient()
            ->database()
            ->deleteMysqlDatabase(
                new DeleteMysqlDatabaseRequest(
                    mysqlDatabaseId: $database->getId()
                )
            );

    }

    /**
     * @throws GuzzleException
     * @throws UnexpectedResponseException
     */
    public function exists(?string $feature = null): bool
    {
        debug('Check database exists');
        $database = $this->getDatabaseByFeature($this->getFeatureName($feature));

        if (null === $database) {
            return false;
        }
        return true;
    }

    private function initClient(): MittwaldAPIV2Client
    {
        if (isset($this->client)) {
            return $this->client;
        }

        if (!class_exists(MittwaldAPIV2Client::class)) {
            throw new \RuntimeException('Mittwald API Client is not installed. Please install the Mittwald API Client package: composer require mittwald/api-client.');
        }

        if (!has('mittwald_api_client')) {
            throw new \RuntimeException('Deployer variable "mittwald_api_client" is not set or empty.');
        }
        if (!has('mittwald_project_id')) {
            throw new \RuntimeException('Deployer variable "mittwald_project_id" is not set or empty.');
        }

        $this->client = MittwaldAPIV2Client::newWithToken(get('mittwald_api_client'));
        return $this->client;
    }

    /**
     * @throws GuzzleException
     * @throws UnexpectedResponseException
     */
    private function getDatabaseByFeature(string $feature): ?MySqlDatabase
    {
        $response = $this->initClient()
            ->database()
            ->listMysqlDatabases(
                new ListMysqlDatabasesRequest(
                    projectId: get('mittwald_project_id')
                )
            );

        foreach ($response->getBody() as $database) {
            /* @var MySqlDatabase $database */
            if ($database->getDescription() === $feature) {
                return $database;
            }
        }
        return null;
    }

    /**
     * @throws GuzzleException
     * @throws UnexpectedResponseException
     */
    private function getDatabase(string $id): MySqlDatabase
    {
        $response = $this->initClient()
            ->database()
            ->getMysqlDatabase(
                new GetMysqlDatabaseRequest(
                    mysqlDatabaseId: $id
                )
            );


        return $response->getBody();
    }

    /**
     * @throws GuzzleException
     * @throws UnexpectedResponseException
     */
    private function getDatabaseUser(string $id): MysqlUser
    {
        $response = $this->initClient()
            ->database()
            ->getMysqlUser(
                new GetMysqlUserRequest(
                    mysqlUserId: $id
                )
            );

        return $response->getBody();
    }

    private function initDatabaseConfiguration(MySqlDatabase $database, MySqlUser $user, string $databaseHost): void
    {
        set('database_user', $user->getName());
        set('database_name', $database->getName());
        set('database_host', $databaseHost);
        set('database_password', VarUtility::getDatabasePassword());
    }

    /**
     * Resolves a database hostname to its IP address on the remote server.
     *
     * This is called immediately after the TCP connectivity check passes, when DNS is
     * known to be working. The resolved IP is used in the .env template instead of the
     * hostname, bypassing Mittwald's intermittent DNS flapping for all subsequent commands.
     *
     * Falls back to the original hostname if resolution fails.
     */
    private function resolveHostnameToIp(string $hostname): string
    {
        try {
            $resolveCmd = sprintf('echo gethostbyname("%s");', addslashes($hostname));
            $ip = trim(run("php -r " . escapeshellarg($resolveCmd)));

            if ($ip !== $hostname) {
                info("Resolved database host {$hostname} to {$ip}");
                return $ip;
            }
        } catch (\Throwable $e) {
            debug("Could not resolve {$hostname} to IP: " . $e->getMessage());
        }

        debug("Could not resolve {$hostname} to IP, using hostname as fallback.");
        return $hostname;
    }

    private function checkDatabaseHostReachable(string $hostname, int $waitingTime, int $maxRetries): bool
    {
        $port = (int) get('database_port', 3306);
        while ($maxRetries > 0) {
            try {
                info("Checking MySQL connectivity for database host {$hostname}:{$port}, remaining attempts: {$maxRetries}");
                $check = sprintf(
                    'echo @fsockopen("%s", %d, $errno, $errstr, 5) ? "1" : "";',
                    addslashes($hostname),
                    $port
                );
                $result = run("php -r " . escapeshellarg($check));
                if ('1' === trim($result)) {
                    info("Database host {$hostname}:{$port} is reachable.");
                    return true;
                }
            } catch (\Throwable $e) {
                debug("MySQL connectivity check failed: " . $e->getMessage());
            }

            if ($maxRetries > 1) {
                sleep($waitingTime);
            }
            $maxRetries--;
        }
        debug("Database host {$hostname}:{$port} is not reachable after all attempts.");
        return false;
    }

    private function checkForDatabaseReadyStatus(string $mysqlUserId, int $waitingTime, int $maxRetries): bool
    {

        while ($maxRetries > 0) {
            try {
                info("Checking status for MySQL user {$mysqlUserId}, remaining attempts: {$maxRetries}");
                $response = $this->initClient()
                    ->database()
                    ->getMysqlUser(
                        new GetMysqlUserRequest($mysqlUserId)
                    );

                if (DatabaseUserStatus::ready === $response->getBody()->getStatus()) {
                    info("MySQL user {$mysqlUserId} is ready.");
                    return true;
                }
            } catch (\Throwable $e) {
                debug("Error while checking status: " . $e->getMessage());
                // Optionally: break on certain errors
            }

            if ($maxRetries > 1) {
                sleep($waitingTime);
            }
            $maxRetries--;
        }
        debug("MySQL user {$mysqlUserId} is not ready after all attempts.");
        return false;
    }
}
