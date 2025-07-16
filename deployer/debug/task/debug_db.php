<?php

namespace Deployer;

use MoveElevator\DeployerTools\Utility\VarUtility;

task('debug:db', function () {
    $databaseUser = get('database_user');
    $databasePassword = VarUtility::getDatabasePassword();
    $databaseName = VarUtility::getDatabaseVarByType('name');
    $ssh_command = "ssh -t " . get('remote_user') . "@" . get('hostname') . " \"mysql -u$databaseUser -p$databasePassword $databaseName\"";
    output()->writeln($ssh_command);
})
    ->desc('Get the ssh command to open the shell for a remote app');
