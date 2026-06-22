<?php

declare(strict_types=1);

use ComposerUnused\ComposerUnused\Configuration\Configuration;
use ComposerUnused\ComposerUnused\Configuration\NamedFilter;

return static function (Configuration $config): Configuration {
    // These packages are consumed via Deployer's recipe loading mechanism,
    // not through PHP `use`/autoload, so composer-unused cannot detect them.
    return $config
        ->addNamedFilter(NamedFilter::fromString('deployer/deployer'))
        ->addNamedFilter(NamedFilter::fromString('sourcebroker/deployer-extended'));
};
