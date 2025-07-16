<?php

namespace Deployer;

function getDebugLogApp(array $logLines): array
{
    $log = [
        get('debug_log_default_header')
    ];
    foreach ($logLines as $key => $logLine) {
        if (preg_match(get('debug_log_regex_pattern'), $logLine, $matches)) {
            $log[] = [
                $key,
                $matches[1],
                $matches[2],
                $matches[3]
            ];
        }
    }
    return $log;
}
