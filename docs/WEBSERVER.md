# Web server

The feature branch deployment supports both **Apache** and **nginx** as web server. The deployment tooling itself is web server agnostic — it uses filesystem symlinks for URL shortening and does not generate or depend on any web server configuration files. This also holds for [subdomain mode](#subdomain-mode): the tooling only ever produces a symlink and a hostname-safe directory name, the vhost that routes a subdomain to it is set up once, outside the tooling.

## Apache

Apache works with minimal effort because TYPO3 and Symfony ship with `.htaccess` files that handle URL rewriting per directory. When a new feature branch is deployed, the application's `.htaccess` is available immediately without any web server restart or configuration change.

The only requirement is that `AllowOverride All` is set for the document root in the vhost configuration.

## nginx

Since nginx does not support per-directory configuration files like `.htaccess`, the URL rewriting and PHP routing must be defined in the server block configuration. This requires a one-time setup that covers all current and future feature branch instances.

### Prerequisites

1. **Symlinks** must be followed (this is the nginx default). Ensure `disable_symlinks` is **not** set to `on`.

2. **PHP-FPM** must be configured to process `.php` files in subdirectories, not just the document root.

3. **URL rewriting** for the application (TYPO3 or Symfony) must be handled in the server block, since there is no `.htaccess` to fall back on.

### Example for TYPO3

```nginx
server {
    listen 443 ssl;
    server_name demo.local;
    root /var/www/html;
    index index.php index.html;

    # Feature branch instances and main application
    location / {
        try_files $uri $uri/ @rewrite;
    }

    # Rewrite all non-file requests to the nearest index.php.
    # Supports both root-level and feature branch subdirectory requests.
    location @rewrite {
        rewrite ^/([^/]+)/(.*)$ /$1/index.php last;
        rewrite ^(.*)$ /index.php last;
    }

    # Deny access to protected directories across all instances
    location ~ /(typo3conf|var|config)/ {
        return 403;
    }

    # PHP-FPM for all .php files including subdirectories
    location ~ \.php$ {
        include fastcgi_params;
        fastcgi_pass unix:/run/php/php-fpm.sock; # adjust to match your PHP-FPM pool socket
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        try_files $uri =404;
    }
}
```

See also the official [TYPO3 nginx configuration guide](https://docs.typo3.org/m/typo3/reference-coreapi/main/en-us/Configuration/WebServer/Nginx.html) for application-specific details.

### Example for Symfony

```nginx
server {
    listen 443 ssl;
    server_name demo.local;
    root /var/www/html;
    index index.php index.html;

    location / {
        try_files $uri $uri/ @rewrite;
    }

    location @rewrite {
        rewrite ^/([^/]+)/(.*)$ /$1/index.php last;
        rewrite ^(.*)$ /index.php last;
    }

    location ~ \.php$ {
        include fastcgi_params;
        fastcgi_pass unix:/run/php/php-fpm.sock; # adjust to match your PHP-FPM pool socket
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        try_files $uri =404;
    }
}
```

## Subdomain mode

With [`feature_url_pattern`](FEATURE.md#subdomain-mode) set, each feature instance is served from its own subdomain instead of a subpath, e.g. `https://test-01.stage.example.com/`. This needs three things beyond the plain path setup above:

1. **Wildcard DNS**: a `*.stage.example.com` record pointing at the host.
2. **Wildcard certificate**: a certificate for `*.stage.example.com` covers exactly one subdomain label, no nested dots, which matches the hostname-safe instance names described in [FEATURE.md](FEATURE.md#initialization). With Let's Encrypt, a wildcard can only be issued via the DNS-01 challenge, not HTTP-01.
3. **A wildcard vhost** that maps the requested subdomain to that instance's directory.

### nginx

```nginx
server {
    listen 443 ssl;
    server_name ~^(?<feature>[a-z0-9-]+)\.stage\.example\.com$;
    root /var/www/html/$feature;
    index index.php index.html;

    location / {
        try_files $uri $uri/ /index.php$is_args$args;
    }

    location ~ \.php$ {
        include fastcgi_params;
        fastcgi_pass unix:/run/php/php-fpm.sock; # adjust to match your PHP-FPM pool socket
        # $realpath_root resolves the "current" symlink at request time, unlike
        # $document_root, which nginx caches for the life of the worker process and would
        # otherwise keep pointing at a release a deploy already replaced
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        fastcgi_param DOCUMENT_ROOT $realpath_root;
        try_files $uri =404;
    }
}
```

`root /var/www/html/$feature` matches the url shortener's flat symlink layout (`/var/www/html/<feature>` → `.fbd/instances/<feature>/current/public`). Without the shortener, point at the nested path instead: `root /var/www/html/$feature/current/public;`. There is no `@rewrite` location here, unlike the subpath examples above: subdomain mode has no path prefix to strip, so requests already arrive at the instance's own root.

The base instance (no `--feature`) and the [feature index page](FEATURE.md#information) keep their own `server_name stage.example.com { ... }` block, unaffected by the wildcard block above.

### Apache

```apache
<VirtualHost *:443>
    ServerAlias *.stage.example.com
    VirtualDocumentRoot /var/www/html/%1/current/public
    <Directory /var/www/html>
        AllowOverride All
        Options +FollowSymLinks
    </Directory>
</VirtualHost>
```

`mod_vhost_alias`'s `%1` is the first wildcard label, i.e. the feature name. `+FollowSymLinks` is required for `VirtualDocumentRoot` to resolve the url shortener's symlink; without the shortener, point `%1` directly at the nested path as shown above. Note that `mod_vhost_alias` does not set `DOCUMENT_ROOT` the way a plain vhost does, check your PHP-FPM/mod_php setup if the application relies on that variable.

## User group

The web server user group might differ between Apache (`www-data`) and nginx (`nginx` or `www-data` depending on distribution). Adjust the deployer configuration accordingly:

```php
set('requirements_user_group', 'nginx');
```
