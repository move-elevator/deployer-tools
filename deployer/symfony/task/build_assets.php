<?php

namespace Deployer;

task('build:assets', function () {
    runLocally("{{npm_variables}}{{npm}} ci --prefix {{app_path}} --cache .npm-cache --prefer-offline");
    runLocally("{{npm_variables}}{{npm}} run build --prefix {{app_path}} --cache .npm-cache --prefer-offline");
});
