<?php

namespace Deployer;

use Deployer\Exception\Exception;
use Deployer\Task\GroupTask;

function getNewVersion(): string {
    if (is_null(input()->getOption('new-version'))) {
        $version = ask("New release version?", guessNextMinorVersion());
    } else {
        $version = input()->getOption('new-version');
    }

    if (!preg_match(get('dev_semver_regex'), $version)) {
        throw new Exception("Given version '$version' not matching semantic version constraints", 1711458465);
    }

    set('new_version', $version);
    return $version;
}

function guessNextMinorVersion(): string|bool
{
    try {
        $lastTag = preg_replace('/^v/', '', runLocally('git describe --tags --abbrev=0'));
        $lastTagParts = explode('.', $lastTag);
        if (!preg_match(get('dev_semver_regex'), $lastTag)) {
            return false;
        }
        $lastTagParts[1] = (int)$lastTagParts[1] + 1;
        $lastTagParts[2] = 0;
        return implode('.', $lastTagParts);
    } catch (Exception) {
        return false;
    }

}

function getSubTask(string $task): array
{
    $task = Deployer::get()->tasks->get($task);
    if ($task && $task->isEnabled()) {
        if ($task instanceof GroupTask) {
            $subTasks = [];
            foreach ($task->getGroup() as $subtask) {
                $subTasks = array_merge($subTasks, getSubTask($subtask));
            }
            return $subTasks;
        } else {
            return [$task->getName()];
        }
    }
    return [];
}


function checkStepIsEnabled(string $step): bool
{
    if (is_array(get('dev_disabled_tasks')) && in_array($step, get('dev_disabled_tasks'))) {
        warning("Skip step \"$step\"");
        return false;
    }
    return true;
}

function commit(string $message, array $files = []): void
{
    $index = '.';
    if (!empty($files)) {
        $index = implode(' ', $files);
    }
    runLocally("git add $index");
    runLocally("git commit " . get('dev_git_commit_options') . " -m \"$message\"");
}
