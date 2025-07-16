# Security check

The security tasks list and notify about security issues in the installed composer packages and npm modules.

## General

The security check task is checking for vulnerabilities in the installed [composer](#composer-dependencies) packages and [npm](#npm-dependencies) modules.

The default settings can be found within the [set.php](../deployer/security/config/set.php) file.

```bash
$ dep security:check [host]
```

## Composer dependencies

Checking the composer dependencies with `composer audit` (or `symfony security:check`).

```bash
$ dep security:check:composer [host]
```

## Npm dependencies

Checking the npm dependencies with `npm audit`.

```bash
$ dep security:check:npm [host]
```

## Notification

Use the `--notify` option to notify about the security issues via MS Teams.

To avoid multiple notifications for the same issue, the notification is only sent if the issue is not already cached (when the issue notification was sent before). You can turn off the caching with setting the `security_use_cache` configuration to `false`.
