# Feature Branch Deployment

The feature branch deployment describes the deployment and initialization process of multiple application instances on the same host.

+ [Prerequirements](#prerequirements)
+ [Initialization](#initialization)
+ [Deletion](#deletion)
+ [Notification](#notification)
+ [Synchronization](#synchronization)
+ [Information](#information)
+ [Pathing](#pathing)
+ [Cleanup](#cleanup)
+ [Further more](#further-more)
+ [FAQ](#faq)

If you want to use the feature branch deployment functionalities add the belonging autoloader to your project `deploy.php`:

```php
require_once(__DIR__ . '/vendor/move-elevator/deployer-tools/deployer/feature/autoload.php');
```

See an example configuration here: [feature example](../deployer/feature/example/).

> Important: if you are using the deployer recipe for a specific application (e.g. symfony), the autoload for the feature branch deployments needs to be placed _after_ the application autoload.

The following steps are necessary to successfully setup the deployment workflow:

### Prerequirements

Choose an according [database management](DATABASE.md) type for your application.

Add the following line to your deployer host entry, to enable the feature branch deployment for this stage:

```yaml
labels:
   type: feature-branch-deployment
```

For using the [synchronization](#synchronization), you need to have the following pip packages installed:

```bash
$ pip3 install db-sync-tool-kmi file-sync-tool-kmi
```

### Initialization

The `feature:setup` command represent the initialization of a new feature branch. It creates the necessary folder structure and database for the application. Also it extends the given host information with the necessary dynamic feature instance information.

*Important:* Note, that you initially need to import the database of the reference state manually (which all other feature branches sync the database from).

```bash
$ vendor/bin/dep feature:setup stage --feature=TEST-01
```

This task should be declared to run at first within your deploy task:

```php
before('deploy:info', 'feature:setup');
```

If the application needs to setup additional configuration files for e.g. storing the database credentials, use the feature templates to provide this kind of dynamic setup. For example the TYPO3 setup with a `.env` file:

```php
set('feature_templates', [
    __DIR__ . '/.deployment/deployer/templates/.env.dist' => '/shared/.env'
]);
```

This configuration defines the local template file as well as the remote target destination. Within the template file a bunch of predefined variables are available and can be placed with `{{attribute}}`.

| attribute                       | description                                                                          |
|---------------------------------|--------------------------------------------------------------------------------------|
| `DEPLOYER_CONFIG_DATABASE_HOST` | default is `127.0.0.1`, overwrite with deployer `set('database_host', '127.0.0.1');` |
| `DEPLOYER_CONFIG_DATABASE_PORT` | default is `3306`, overwrite with deployer `set('database_port', '3306');`           |
| `DEPLOYER_CONFIG_DATABASE_USER` | should be defined with `database_user` in the host configuration                     |
| `DEPLOYER_CONFIG_DATABASE_NAME` | will be dynamically generated                                                        |
| `DEPLOYER_CONFIG_FEATURE_NAME`  | will be provide with the `--feature=` command line argument                          |
| `DEPLOYER_CONFIG_FEATURE_URL`   | will be dynamically generated                                                        |
| `DEPLOYER_CONFIG_FEATURE_PATH`  | will be dynamically generated                                                        |

You can extend these list be providing more environment variables starting with `DEPLOYER_CONFIG_*`.

> Hint: If you're using other deployer commands within the feature branch deployment context, you should use the `feature:init` task to extend the host definition with the necessary feature instance configuration:
>
> ```php
> before('deploy:rollback', 'feature:init');
> ```

### Deletion

The `feature:stop` command deletes the feature branch instance, including the folder structure and the database.

```bash
$ vendor/bin/dep feature:stop stage --feature=TEST-01
```

### Notification

The `feature:notify` command notifies about a successful initial feature branch deployment and can be configured with the following settings:

```php
set('feature_msteams_color', '#0097A7');
set('feature_msteams_text', 'Der Branch **[{{feature}}]({{public_url}})** wurde bereitgestellt.');
```

This task should be placed right before finishing the deployment task.
```php
before('deploy:success', 'feature:notify');
```

> Hint: it is possible to mute the notification by providing the environment variable `DEPLOYER_CONFIG_NOTIFICATION_MUTE`.

### Synchronization
The `feature:sync` command synchronizes the feature branch instance with data from a different instance using the [db-sync-tool](https://github.com/jackd248/db-sync-tool).

You need to configure the paths within your `deploy.php` to the db-sync-tool configuration files:

```php
# general sync configuration file for feature branches
set('feature_sync_config', __DIR__ . '/.deployment/db-sync-tool/sync-stage-to-feature.yaml');
# where to find the sync target file
set('feature_sync_target_path', '{{deploy_path}}/shared/.env.local');
# where to find the sync file directory
set('feature_sync_target_path_files', '{{deploy_path}}/shared/public/upload/media/');
```

Having an optionally entry `files` in your sync configuration (e.g. [sync-stage-to-feature.yaml](../deployer/feature/example/.deployment/db-sync-tool/sync-stage-to-feature.yaml#17)) enables the [file-sync-tool](https://github.com/jackd248/file-sync-tool) for a simple file synchronisation feature.

It's possible to override db_sync or file_sync command (setting it to false disables the feature):
```php
# disables db_sync feature entirely
set('db_sync_tool', false);
# disables file_sync feature entirely
set('file_sync_tool', false);
```

#### Periodic sync

This function can be used as periodic sync between instances (e.g. nightly sync between the production system and a specific feature branch instance on the stage system).

Therefore you should use the `feature:sync:standalone` task, which includes the `feature:sync` command and may also include additional (application specific) commands like clearing the cache.

For overwriting the default `feature_sync_config` setting, add an additional environment variable:

```bash
DEPLOYER_CONFIG_FEATURE_SYNC_CONFIG=./.deployment/db-sync-tool/sync-prod-to-feature.yaml
```
```bash
$ vendor/bin/dep feature:sync:full stage --feature=test
```

### Information

The `feature:list` command lists all available feature branches:

```bash
$ vendor/bin/dep feature:list

+----------------+------------------ stage ------------------------------------+
| Feature Branch | Modification Date | Public URL                              |
+----------------+-------------------+-----------------------------------------+
| feature-start  | 20.01.2023 15:07  | https://test.example.local/feature-start   |
| test           | 20.01.2023 17:45  | https://test.example.local/test            |
+----------------+-------------------+-----------------------------------------+
```

Also a simple feature branch overview index application can be placed on the hosts with the `feature:index` command:

```bash
$ vendor/bin/dep feature:index stage
```

This uploads the index application to the host and creates a symbolic link to the web root. The folder structure on the server will look like this:
```bash
├── .fbd/
│   ├── index/
│   ├── index.php
│   ├── index.json
│   ├── logo.png (optionally)
│   └── background.png (optionally)
└── index.php -> .fbd/index.php
```

### Pathing

Normally the feature instances are deployed in the web root of the host, e.g. `/var/www/html/`. But the entry point of an application could lie in a nested folder structure, e.g. `/var/www/html/app/current/public/`, so the url would look like this: `https://test.local/app/current/public/`.

To make this a bit easier, you can use the `feature:urlshortener` command. Initially activate the function via:

```php
set('feature_url_shortener', true);
```

```php
after('deploy:symlink', 'feature:urlshortener');
```

The folder structure on the server will look like this:

```bash
├── .fbd
│   └── instances
│       ├── app
│       │   └── current
│       │       └── public/
│       └── ...
└── app -> .fbd/instances/app/current/public
```

So the resulting url will look like: `https://test.local/app`.

### Cleanup

The `feature:cleanup` command helps to cleanup feature branch instances. It compares remote git branches with remote feature instances and provides a cleanup for all untracked feature instances on the remote server.

```bash
$  vendor/bin/dep feature:cleanup
task feature:cleanup
+------------------- stage -------------------+
| Remote Git Branch | Remote Feature Instance |
+-------------------+-------------------------+
| main              | main                    |
| stage             | stage                   |
|                   | test                    |
+-------------------+-------------------------+
[stage] Do you want to cleanup all remote feature instances which haven't an according git branch anymore? (marked as red) [y/N] y
[stage] info feature branch test deleted
```

### Further more

Additional configurations regarding the feature branch deployment are available here: [set.php](../deployer/feature/config/set.php)

A bunch of helpful global deployer functions can be found here: [set.php](../deployer/functions.php)

### FAQ

1. __Debugging__

   For extended debugging information use the environment variable `DEPLOYER_CONFIG_VERBOSE`.

   Disable the notifications with the environment variable `DEPLOYER_CONFIG_NOTIFICATION_MUTE`.
