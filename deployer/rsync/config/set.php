<?php

namespace Deployer;

require 'contrib/rsync.php';

set('repository', '');
set('default_timeout', 900);

set('rsync-exclude-file', '.deployment/rsync/exclude.txt');

set('rsync_default_excludes', [
    '.Build',
    '.git',
    '.gitlab',
    '.ddev',
    '.deployer',
    '.idea',
    '.DS_Store',
    '.gitlab-ci.yml',
    '.npm',
    'package.json',
    'package-lock.json',
    'node_modules/'
]);

/**
 * rsync
 */
set('rsync_src', './{{app_path}}');
set('rsync', [
    'exclude' => array_merge(get('shared_dirs'), get('shared_files'), get('rsync_default_excludes')),
    'exclude-file' => '{{rsync-exclude-file}}',
    'include' => [],
    'include-file' => false,
    'filter' => [],
    'filter-file' => false,
    'filter-perdir' => false,
    'flags' => 'rzltp',
    'options' => ['delete'],
    'timeout' => 600,
]);
