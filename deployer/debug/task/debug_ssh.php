<?php

namespace Deployer;

task('debug:ssh', function () {
    $activePath = get('deploy_path') . '/' . (test('[ -L {{deploy_path}}/release ]') ? 'release' : 'current');
    $ssh_command = "ssh -t " . get('remote_user') . "@" . get('hostname') . " \"cd $activePath ; bash --login\"";
    output()->writeln($ssh_command);
})
    ->desc('Get the ssh command to open the shell for a remote app');
