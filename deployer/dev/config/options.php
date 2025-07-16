<?php

namespace Deployer;

use Symfony\Component\Console\Input\InputOption;

option('new-version', null, InputOption::VALUE_OPTIONAL, 'Version for new release');
option('no-db-sync', null, InputOption::VALUE_NONE, 'Add to avoid database sync in tabula rasa task');
option('cache-db', null, InputOption::VALUE_NONE, 'Add to cache the database for 1 day after syncing it');
