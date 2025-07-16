<?php

namespace MoveElevator\DeployerTools\Symfony;

use SourceBroker\DeployerLoader\Load;

class Loader
{
    public function __construct()
    {
        /** @noinspection PhpIncludeInspection */
        require_once 'recipe/common.php';

        new Load([
                ['path' => 'vendor/sourcebroker/deployer-instance/deployer'],
                ['path' => 'vendor/sourcebroker/deployer-extended/deployer'],
            ]
        );
    }
}
