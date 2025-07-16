<?php

namespace Deployer;

set('msteams_webhook', null);
set('msteams_text', 'Der Branch **[{{feature}}]({{public_url}})** wurde bereitgestellt.');
set('msteams_color', '#2E7D32');

set('msteams_webhook_version', 1);

set('deploy_prod_notify_text', 'Auf **[{{deploy_target}}]({{public_url}})** wurde die Version **{{deploy_version}}** veröffentlicht.');
set('deploy_prod_notify_color', '#B71C1C');
