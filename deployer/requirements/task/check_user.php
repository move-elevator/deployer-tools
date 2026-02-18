<?php

declare(strict_types=1);

namespace Deployer;

use Deployer\Exception\RunException;

task('requirements:check:user', function (): void {
    if (!get('requirements_check_user_enabled')) {
        return;
    }

    $expectedGroup = get('requirements_user_group');

    // Check primary group
    try {
        $actualGroup = trim(run('id -gn'));
        $meets = ($actualGroup === $expectedGroup);
        addRequirementRow(
            'User group',
            $meets ? REQUIREMENT_OK : REQUIREMENT_WARN,
            $meets ? $actualGroup : "$actualGroup (expected: $expectedGroup)"
        );
    } catch (RunException) {
        addRequirementRow('User group', REQUIREMENT_SKIP, 'Could not determine user group');
    }

    // Check deploy path existence
    if (!test('[ -d {{deploy_path}} ]')) {
        addRequirementRow('Deploy path', REQUIREMENT_FAIL, '{{deploy_path}} does not exist');

        return;
    }

    // Check deploy path owner
    try {
        $remoteUser = get('remote_user');
        $pathOwner = trim(run('stat -c "%U" {{deploy_path}}'));
        $meets = ($pathOwner === $remoteUser);
        addRequirementRow(
            'Deploy path owner',
            $meets ? REQUIREMENT_OK : REQUIREMENT_FAIL,
            $meets ? "Owned by $pathOwner" : "Owned by $pathOwner (expected: $remoteUser)"
        );
    } catch (RunException) {
        addRequirementRow('Deploy path owner', REQUIREMENT_SKIP, 'Could not check ownership');
    }

    // Check deploy path permissions
    $expectedPerms = get('requirements_deploy_path_permissions');

    try {
        $actualPerms = trim(run('stat -c "%a" {{deploy_path}}'));
        $meets = ($actualPerms === $expectedPerms);
        addRequirementRow(
            'Deploy path permissions',
            $meets ? REQUIREMENT_OK : REQUIREMENT_FAIL,
            $meets ? $actualPerms : "$actualPerms (expected: $expectedPerms)"
        );
    } catch (RunException) {
        addRequirementRow('Deploy path permissions', REQUIREMENT_SKIP, 'Could not check permissions');
    }

    // Check deploy path group
    try {
        $pathGroup = trim(run('stat -c "%G" {{deploy_path}}'));
        $meets = ($pathGroup === $expectedGroup);
        addRequirementRow(
            'Deploy path group',
            $meets ? REQUIREMENT_OK : REQUIREMENT_FAIL,
            $meets ? $pathGroup : "$pathGroup (expected: $expectedGroup)"
        );
    } catch (RunException) {
        addRequirementRow('Deploy path group', REQUIREMENT_SKIP, 'Could not check group');
    }
})->hidden();
