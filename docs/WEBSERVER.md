# Web server

The feature branch deployment supports both **Apache** and **nginx** as web server. The deployment tooling itself is web server agnostic — it uses filesystem symlinks for URL shortening and does not generate or depend on any web server configuration files.

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

## User group

The web server user group might differ between Apache (`www-data`) and nginx (`nginx` or `www-data` depending on distribution). Adjust the deployer configuration accordingly:

```php
set('requirements_user_group', 'nginx');
```
