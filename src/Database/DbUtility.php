<?php

namespace MoveElevator\DeployerTools\Database;

use MoveElevator\DeployerTools\Database\Exception\DatabaseException;
use MoveElevator\DeployerTools\Database\Manager\Api;
use MoveElevator\DeployerTools\Database\Manager\ManagerInterface;
use MoveElevator\DeployerTools\Database\Manager\MittwaldApi;
use MoveElevator\DeployerTools\Database\Manager\Root;
use MoveElevator\DeployerTools\Database\Manager\Simple;

use function Deployer\get;
use function Deployer\has;
use function Deployer\run;
use function Deployer\test;

class DbUtility
{
    public const DATABASE_MANAGEMENT_TYPE_ROOT = 'root';
    public const DATABASE_MANAGEMENT_TYPE_SIMPLE = 'simple';
    public const DATABASE_MANAGEMENT_TYPE_API = 'api';

    public const DATABASE_MANAGEMENT_TYPE_MITTWALD_API = 'mittwald_api';

    protected static array $databaseManagers = [
        'default' => Root::class,
        self::DATABASE_MANAGEMENT_TYPE_ROOT => Root::class,
        self::DATABASE_MANAGEMENT_TYPE_SIMPLE => Simple::class,
        self::DATABASE_MANAGEMENT_TYPE_API => Api::class,
        self::DATABASE_MANAGEMENT_TYPE_MITTWALD_API => MittwaldApi::class,
    ];

    public static function getDatabaseManager(): ManagerInterface
    {
        $type = has('database_manager_type') ? get('database_manager_type') : 'default';

        if (array_key_exists($type, self::$databaseManagers)) {
            $managerClass = self::$databaseManagers[$type];
            if (class_exists($managerClass)) {
                return new $managerClass();
            } else {
                throw DatabaseException::managerNotFound($type);
            }
        } else {
            throw DatabaseException::managerNotFound($type);
        }
    }
}
