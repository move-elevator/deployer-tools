<?php

namespace MoveElevator\DeployerTools\Utility;

use function Deployer\askHiddenResponse;
use function Deployer\get;
use function Deployer\has;
use function Deployer\set;

class VarUtility
{
    /**
     * Get the database variable by app type
     *
     * @param string $var
     * @return string|bool
     */
    public static function getDatabaseVarByType(string $var = 'password'): string|bool
    {
        $var = ucfirst($var);
        $type = get('app_type');
        $functionName = "\Deployer\getDatabase{$var}For" . ucfirst($type);
        require_once(__DIR__ . "/../../deployer/$type/task/deploy_database.php");
        if (function_exists($functionName)) {
            return call_user_func($functionName);
        }
        return false;
    }


    /**
     * Get the database password for the feature branch
     *
     * @return string
     * @throws \Exception
     */
    public static function getDatabasePassword(): string
    {
        $databaseUser = get('database_user');
        $databaseHost = get('database_host');
        $databasePassword = has('database_password') ? get('database_password') : getenv('DEPLOYER_CONFIG_DATABASE_PASSWORD');

        if (!$databasePassword) {
            $databasePassword = VarUtility::getDatabaseVarByType();
        }

        if (!$databasePassword) {
            $databasePassword = askHiddenResponse("Enter the database password for $databaseUser@$databaseHost:");
            set('database_password', $databasePassword);
        }
        return $databasePassword;
    }
}
