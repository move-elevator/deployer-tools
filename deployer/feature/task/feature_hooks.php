<?php

namespace Deployer;

/**
 * Extension points for provisioning and deprovisioning whatever external infrastructure a
 * feature instance needs beyond its deploy directory and database, most commonly
 * registering or removing a subdomain via a hosting provider's API when feature_url_pattern
 * is used.
 *
 * Both tasks are no-ops by default. Override them in your project's deploy.php:
 *
 *   task('feature:provision', function () {
 *       $host = parse_url(get('public_urls')[0], PHP_URL_HOST);
 *       // e.g. call your provider's API to create $host and wait until it resolves
 *   });
 *
 * feature:provision runs once per new instance, right after its database is created
 * (feature:setup) and before feature_templates are rendered - a template like .env.dist may
 * already depend on the infrastructure it provisions.
 *
 * feature:deprovision runs from deleteFeature(), before an instance's symlink, database and
 * directory are removed - for both feature:stop and feature:cleanup.
 *
 * Both must be idempotent: an aborted deploy can leave feature:provision applied without the
 * instance directory existing yet, so a retried deploy runs it again; deleteFeature() can
 * likewise be invoked more than once for the same instance.
 */
task('feature:provision', function () {
})
    ->select('type=feature-branch-deployment')
    ->desc('Provision external infrastructure for a new feature instance (no-op by default, override in your deploy.php)')
;

task('feature:deprovision', function () {
})
    ->select('type=feature-branch-deployment')
    ->desc('Remove external infrastructure of a deleted feature instance (no-op by default, override in your deploy.php)')
;
