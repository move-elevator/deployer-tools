<?php

namespace Deployer;

task('feature:scheduler', function () {

    checkVerbosity();

    $deployPath = get('deploy_path');
    $featureDirectoryPath = rtrim(get('feature_directory_path'), '/');
    $schedulerPath = $deployPath . '/' . $featureDirectoryPath . '/scheduler.sh';
    $logPath = $deployPath . '/' . $featureDirectoryPath . '/scheduler.log';

    $upload = true;
    if (test("[[ -f $schedulerPath ]]")) {
        $upload = askConfirmation("A scheduler.sh file already exists: " . $schedulerPath . " \n Do you really want to override the file?", true);
    }

    if (!$upload) return;

    // without url shortener, feature instances are direct subdirectories of deploy_path
    // with url shortener, they reside in the instances/ subdirectory
    $instancesDir = isUrlShortener()
        ? $deployPath . '/' . get('feature_url_shortener_path')
        : $deployPath;

    $phpBin = has('bin/php') ? get('bin/php') : 'php';

    $arguments = [
        'SCHEDULER_PATH' => $schedulerPath,
        'LOG_PATH' => $logPath,
        'INSTANCES_DIR' => $instancesDir,
        'TYPO3_BIN' => get('bin/typo3cms'),
        'PHP_BIN' => $phpBin,
    ];

    uploadTemplate(
        __DIR__ . '/../dist/scheduler.sh.dist',
        '/' . $featureDirectoryPath . '/scheduler.sh',
        $arguments
    );

    runExtended("chmod +x $schedulerPath");

    info("Scheduler script uploaded to <fg=magenta;options=bold>$schedulerPath</>");
    info("Crontab: <fg=cyan>*/5 * * * * $schedulerPath >> $logPath 2>&1</>");

})
    ->select('type=feature-branch-deployment')
    ->desc('Upload TYPO3 scheduler script for all feature branch instances')
;
