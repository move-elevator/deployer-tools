<?php

namespace Deployer;

use Symfony\Component\Console\Input\InputOption;

option('feature', null, InputOption::VALUE_OPTIONAL, 'Enable feature branch deployment');
option('force-cleanup', null, InputOption::VALUE_NONE, 'Delete untracked feature instances in feature:cleanup without asking for confirmation');
