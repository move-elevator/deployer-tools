# Dev Tools

The following tasks are part of the development tools, to simplify the local development and release process.

- [dev:sync](#sync)
- [dev:tr](#tabula-rasa)
- [dev:dump](#dump)
- [dev:import](#import)
- [dev:release](#release)

<a name="sync"></a>
## dev:sync

Sync local application with the remote instance.
```bash
$ dep dev:sync local
```

<a name="tabula-rasa"></a>
## dev:tr

Reset local branch for further development.
```bash
$ dep dev:tr local [--no-db-sync] [--cache-db]
```

Use `cache-db` to cache the database for 1 day after syncing it. Useful for large databases and to reduce load in the source system.

<a name="dump"></a>
## dev:dump

Dump the local database to an sql file. This task is used by `dev:tr` with `--cache-db` option set to create a once-per-day sql dump.
```bash
$ dep dev:dump local
```

<a name="import"></a>
## dev:import

Import the local database by an sql dump. This task is used by `dev:tr` with `--cache-db` option import the once-per-day sql dump.
```bash
$ dep dev:import local
```

<a name="release"></a>
## dev:release

This task combines multiple steps to automate the release preparation.

*Start* the new release task.

```bash
$ dep dev:release local
```

Define the new release number within the prompt or use `--new-version=1.2.3`.

This includes the following steps:

- [Tabula Rasa](#tabula-rasa) (reset local state)
- [Start new release](../deployer/dev/task/start_new_release.php)
- [Custom steps](../deployer/dev/task/release.php#L17)
  - [Composer updates](../deployer/dev/task/composer_update.php) (App & CI)
  - [NPM updates](../deployer/dev/task/npm_update.php)
  - [QS](../deployer/dev/task/qa.php) (PHP & NPM)
  - [Tests](../deployer/dev/task/test.php) (Acceptance)

You can extend or overwrite or disabled these steps, e.g.

```php
task('dev:release:test:acceptance')->addAfter(function() {
    runLocally('echo hello world')
});

task('dev:release:qa:npm')->disable();
```


Use the following [command](../deployer/dev/task/release_reset.php) to *reset* the new release branch (in case of an occurring error):

```bash
$ dep dev:release:reset local
```

Use the following [command](../deployer/dev/task/release_finish.php) to *finish* the new release branch by checking out the default branch and creating the new release version:

```bash
$ dep dev:release:finish local
```

### General advices

> *Always* check the automated created git commits before pushing them.

See the default config [set.php](../deployer/dev/config/set.php) to adjust the release process to your needs.

Hint: Use the following command to link your git user into the ddev container.

```bash
ln -s ~/.gitconfig ~/.ddev/homeadditions/.gitconfig
```
