<?php

namespace MoveElevator\DeployerTools\Database\Manager;

use GuzzleHttp\Exception\GuzzleException;
use Mittwald\ApiClient\Client\EmptyResponse;
use Mittwald\ApiClient\Error\UnexpectedResponseException;
use Mittwald\ApiClient\Generated\V2\Clients\Database\CreateMysqlDatabase\CreateMysqlDatabaseCreatedResponse;
use Mittwald\ApiClient\Generated\V2\Clients\Database\DeleteMysqlDatabase\DeleteMysqlDatabaseRequest;
use Mittwald\ApiClient\Generated\V2\Clients\Database\GetMysqlDatabase\GetMysqlDatabaseOKResponse;
use Mittwald\ApiClient\Generated\V2\Clients\Database\GetMysqlUser\GetMysqlUserOKResponse;
use Mittwald\ApiClient\Generated\V2\Clients\Database\GetMysqlUser\GetMysqlUserRequest;
use Mittwald\ApiClient\Generated\V2\Schemas\Database\MySqlUser;
use Mittwald\ApiClient\MittwaldAPIV2Client;
use Mittwald\ApiClient\Generated\V2\Clients\Database\ListMysqlDatabases\ListMysqlDatabasesRequest;
use Mittwald\ApiClient\Generated\V2\Clients\Database\ListMysqlDatabases\ListMysqlDatabasesOKResponse;
use Mittwald\ApiClient\Generated\V2\Schemas\Database\CreateMySqlDatabase;
use Mittwald\ApiClient\Generated\V2\Schemas\Database\CreateMySqlUserWithDatabase;
use Mittwald\ApiClient\Generated\V2\Schemas\Database\CreateMySqlUserWithDatabaseAccessLevel;
use Mittwald\ApiClient\Generated\V2\Clients\Database\CreateMysqlDatabase\CreateMysqlDatabaseRequest;
use Mittwald\ApiClient\Generated\V2\Clients\Database\CreateMysqlDatabase\CreateMysqlDatabaseRequestBody;
use Mittwald\ApiClient\Generated\V2\Schemas\Database\MySqlDatabase;
use Mittwald\ApiClient\Generated\V2\Clients\Database\GetMysqlDatabase\GetMysqlDatabaseRequest;
use MoveElevator\DeployerTools\Utility\VarUtility;

use function Deployer\debug;
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

    public function create(): void
    {
        debug('Creating database');
        $response = $this->initClient()
            ->database()
            ->createMysqlDatabase(
                new CreateMysqlDatabaseRequest(
                    projectId: get('mittwald_project_id'),
                    body: new CreateMysqlDatabaseRequestBody(
                        database: new CreateMySqlDatabase(
                            $this->getFeatureName(),
                            get('mittwald_project_id'),
                            get('mittwald_database_version', '8.4')
                        ),
                        user: new CreateMySqlUserWithDatabase(
                            CreateMySqlUserWithDatabaseAccessLevel::full,
                            VarUtility::getDatabasePassword()
                        )
                    )
                )
            );


        $responseBody = $response->getBody();
        $database = $this->getDatabase($responseBody->getId());
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
        set('DEPLOYER_CONFIG_DATABASE_USER', $user->getName());
        set('DEPLOYER_CONFIG_DATABASE_NAME', $database->getName());
        set('DEPLOYER_CONFIG_DATABASE_HOST', $database->getHostName());
        set('DEPLOYER_CONFIG_DATABASE_PASSWORD', VarUtility::getDatabasePassword());
    }
}
