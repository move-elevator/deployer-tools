<?php

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

        // The database creation is not immediately available, we need to wait for it.
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

        $user = $this->getDatabaseUser($responseBody->getUserId());

        $this->initDatabaseConfiguration($database, $user);
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

    private function initDatabaseConfiguration(MySqlDatabase $database, MySqlUser $user): void
    {
        set('database_user', $user->getName());
        set('database_name', $database->getName());
        set('database_host', $database->getHostName());
        set('database_password', VarUtility::getDatabasePassword());
    }

    private function checkDatabaseHostReachable(string $hostname, int $waitingTime, int $maxRetries): bool
    {
        while ($maxRetries > 0) {
            try {
                info("Checking DNS resolution for database host {$hostname}, remaining attempts: {$maxRetries}");
                $result = run("php -r \"echo checkdnsrr(" . escapeshellarg($hostname) . ", 'A') ? '1' : '';\"");
                if (trim($result) === '1') {
                    info("Database host {$hostname} is reachable.");
                    return true;
                }
            } catch (\Throwable $e) {
                debug("DNS resolution check failed: " . $e->getMessage());
            }

            if ($maxRetries > 1) {
                sleep($waitingTime);
            }
            $maxRetries--;
        }
        debug("Database host {$hostname} is not reachable after all attempts.");
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
