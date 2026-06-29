<?php

declare(strict_types=1);

use ComposerUnused\ComposerUnused\Configuration\Configuration;
use ComposerUnused\ComposerUnused\Configuration\NamedFilter;

return static function (Configuration $config): Configuration {
    // deployer-extended is consumed via Deployer's recipe loading mechanism,
    // not through PHP `use`/autoload, so composer-unused cannot detect it.
    return $config
        ->addNamedFilter(NamedFilter::fromString('sourcebroker/deployer-extended'));
};
