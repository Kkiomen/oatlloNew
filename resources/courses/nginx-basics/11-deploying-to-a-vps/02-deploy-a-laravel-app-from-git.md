---
title: "Deploy a Laravel app from git"
slug: deploy-a-laravel-app-from-git
seo_title: "Deploy Laravel to a VPS with Nginx (Ubuntu 24.04)"
seo_description: "Deploy a Laravel app from git to a fresh Ubuntu 24.04 VPS: nginx, PHP 8.4-FPM, Composer, MySQL, correct permissions and a production server block, step by step."
---

## The goal

This is the lesson where you deploy a Laravel app to a VPS from start to finish. You have a clean Ubuntu 24.04 box and a Laravel project in a git repository. By the end the app runs live on that server, served by nginx and PHP-FPM, backed by MySQL.

This is the long one. It pulls together the whole course: the [Laravel server block](/course/nginx-basics/nginx-and-php/a-laravel-server-block), [root and index](/course/nginx-basics/serving-static-content/root-and-index), [how nginx runs PHP](/course/nginx-basics/nginx-and-php/how-nginx-runs-php), and the safe [test and reload](/course/nginx-basics/configuration-basics/testing-config-safely) habit. We'll go in order, one command at a time. Assume you have already pointed a domain at the server in [point a domain at your VPS](/course/nginx-basics/deploying-to-a-vps/point-a-domain-at-your-vps).

Every command below is run **on the server** unless it says otherwise. Most need root, so either log in as root or prefix with `sudo`.

## Step 1: SSH in and update the system

Connect to the server with its IP or your new domain:

```bash
ssh root@yourdomain.com
```

First thing on any fresh box, update the package lists and installed packages:

```bash
apt update && apt upgrade -y
```

`apt update` refreshes the list of available packages. `apt upgrade` installs newer versions of what you already have. Do this before installing anything else so you build on current packages.

## Step 2: Install nginx and git

```bash
apt install -y nginx git
```

This is the same nginx you [installed](/course/nginx-basics/getting-started/installing-nginx) back in Chapter 1. `git` is how the code gets onto the server. Confirm nginx is up:

```bash
systemctl status nginx
```

You should see `active (running)`. Visiting the server's IP now shows the default nginx page, which we'll replace with your app.

## Step 3: Install PHP 8.4 and the extensions Laravel needs

Ubuntu's default repositories may not carry the newest PHP, so add Ondřej Surý's well-known PHP PPA first:

```bash
apt install -y software-properties-common
add-apt-repository -y ppa:ondrej/php
apt update
```

Now install PHP 8.4 FPM plus the extensions a typical Laravel app uses:

```bash
apt install -y php8.4-fpm php8.4-mbstring php8.4-xml php8.4-bcmath \
  php8.4-curl php8.4-zip php8.4-mysql php8.4-gd
```

Here is what each one is for:

- `php8.4-fpm` is the FastCGI process manager nginx talks to. This is the piece from [how nginx runs PHP](/course/nginx-basics/nginx-and-php/how-nginx-runs-php).
- `php8.4-mbstring`, `php8.4-xml`, `php8.4-bcmath` are required by Laravel and many packages.
- `php8.4-curl`, `php8.4-zip` are used by Composer and HTTP clients.
- `php8.4-mysql` lets Laravel talk to MySQL.
- `php8.4-gd` handles images.

Check FPM is running, and note the socket path, you'll need it in the nginx config:

```bash
systemctl status php8.4-fpm
```

The socket lives at `/run/php/php8.4-fpm.sock`.

## Step 4: Install Composer

Composer installs your PHP dependencies. Download and install it globally:

```bash
cd /tmp
curl -sS https://getcomposer.org/installer -o composer-setup.php
php composer-setup.php --install-dir=/usr/local/bin --filename=composer
rm composer-setup.php
```

Verify it:

```bash
composer --version
```

## Step 5: Install MySQL and create a database

Install the server:

```bash
apt install -y mysql-server
```

Open a MySQL shell as root:

```bash
mysql
```

Create a database and a dedicated user for the app (change the password to something strong):

```sql
CREATE DATABASE myapp CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'myapp'@'localhost' IDENTIFIED BY 'a-strong-password-here';
GRANT ALL PRIVILEGES ON myapp.* TO 'myapp'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

You now have a database `myapp` and a user `myapp` that can use it. Keep these three values handy: database name, username, password. They go into `.env` in a moment.

## Step 6: Clone the repository

Put web apps under `/var/www`. Clone your project there:

```bash
cd /var/www
git clone https://github.com/you/myapp.git myapp
cd myapp
```

You now have the code at `/var/www/myapp`, and Laravel's public web root is `/var/www/myapp/public`, exactly the path the server block will point at.

## Step 7: Install PHP dependencies

Install the app's dependencies, tuned for production:

```bash
composer install --no-dev --optimize-autoloader
```

- `--no-dev` skips development-only packages you do not need on a live server.
- `--optimize-autoloader` builds a faster class map so requests boot quicker.

A note on **who** runs Composer: run it as the same user that owns the files, not as root, to avoid creating root-owned files inside `vendor/` that PHP-FPM later cannot manage. If you cloned as root, the simplest fix is to hand ownership to the web user in Step 10 and re-run heavy commands from there, or use `sudo -u www-data composer install ...`. There is a second reason to care: `www-data` has no home directory Composer can write to, so a bare `sudo -u www-data composer install` may complain it cannot create its cache. Passing `COMPOSER_HOME=/tmp/composer` in front of the command sidesteps it.

## Step 8: Create and edit .env

Laravel reads its configuration and secrets from a `.env` file, which is not in git. Start from the example:

```bash
cp .env.example .env
```

Open it and set the important values:

```bash
nano .env
```

```ini
APP_NAME=MyApp
APP_ENV=production
APP_DEBUG=false
APP_URL=https://yourdomain.com

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=myapp
DB_USERNAME=myapp
DB_PASSWORD=a-strong-password-here
```

The database values must match what you created in Step 5. Set `APP_ENV=production` and `APP_DEBUG=false` so errors are not shown to visitors. `APP_URL` uses `https://` because you'll add the certificate in the [next lesson](/course/nginx-basics/deploying-to-a-vps/https-with-lets-encrypt); it is fine to set it now.

## Step 9: Generate the app key and run migrations

Laravel needs a unique encryption key:

```bash
php artisan key:generate
```

Then create the database tables:

```bash
php artisan migrate --force
```

`--force` is required because migrations refuse to run in production without it, as a safety check. A clean run means the app can reach the database, which also confirms your `.env` credentials are correct.

If `migrate` stops with `SQLSTATE[HY000] [1045] Access denied`, the fix is almost never in Laravel. Re-read the three values from Step 5 against `.env`: a mistyped password or a database name off by one character is the usual cause. Note also that MySQL distinguishes the account host, so `DB_USERNAME` must be the exact user you granted, and it will not silently fall back to another account.

## Step 10: Fix permissions

PHP-FPM runs as the user `www-data`. That user must be able to **write** to two folders: `storage` (logs, cache, uploads) and `bootstrap/cache` (compiled config). Nothing else needs to be writable.

Give ownership of the whole app to `www-data`, which also settles any root-owned files from an earlier clone:

```bash
chown -R www-data:www-data /var/www/myapp
```

At minimum, `storage` and `bootstrap/cache` must be owned by `www-data`:

```bash
chown -R www-data:www-data /var/www/myapp/storage /var/www/myapp/bootstrap/cache
```

If you skip this, you'll get a "failed to open stream: Permission denied" or "The stream or file could not be opened" error the first time the app tries to write a log. That is the single most common post-deploy error, and it is always permissions.

## Step 11: Link storage

Laravel serves user-uploaded files through a symlink from `public/storage` to `storage/app/public`:

```bash
php artisan storage:link
```

Without this link, uploaded files live outside the web root and nginx cannot see them, which is the "my images 404" problem. This is the same point made in the [Laravel server block](/course/nginx-basics/nginx-and-php/a-laravel-server-block) FAQ.

## Step 12: Cache config, routes, and views

On a production server, cache Laravel's configuration so it does not rebuild on every request:

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

This reads your `.env` once and compiles everything into fast cache files. Important: **run these after editing `.env`**, not before. If you change `.env` later, re-run `php artisan config:cache` (or `php artisan optimize:clear` to wipe all caches) or the app keeps using the old values.

## Step 13: The nginx server block

Now nginx. Create a config for the site (the pattern from [includes and sites](/course/nginx-basics/configuration-basics/includes-and-sites)):

```bash
nano /etc/nginx/sites-available/myapp
```

Paste the production Laravel server block. This is the canonical block from [a Laravel server block](/course/nginx-basics/nginx-and-php/a-laravel-server-block), with the real domain in `server_name`:

```nginx
server {
    listen 80;
    server_name yourdomain.com www.yourdomain.com;

    root /var/www/myapp/public;
    index index.php;

    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        include fastcgi_params;
        fastcgi_pass unix:/run/php/php8.4-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
    }

    location ~ /\. {
        deny all;
    }
}
```

Every line here was covered earlier in the course:

- `root /var/www/myapp/public;` points at Laravel's public folder, keeping your code and `.env` out of reach. See [root and index](/course/nginx-basics/serving-static-content/root-and-index).
- `try_files $uri $uri/ /index.php?$query_string;` serves real files directly and routes everything else through Laravel's front controller. See [try files](/course/nginx-basics/location-matching/try-files).
- The `\.php$` location hands PHP to PHP-FPM over the socket. See [fastcgi pass](/course/nginx-basics/nginx-and-php/fastcgi-pass). The socket path `unix:/run/php/php8.4-fpm.sock` must match your installed PHP version.
- `location ~ /\. { deny all; }` blocks requests for dotfiles like `/.env`.

## Step 14: Enable the site and reload

Nginx only serves configs that are symlinked into `sites-enabled`. Link yours, and remove the default site so it does not answer for unmatched names:

```bash
ln -s /etc/nginx/sites-available/myapp /etc/nginx/sites-enabled/
rm /etc/nginx/sites-enabled/default
```

Now the safe two-step: test, then reload:

```bash
nginx -t && systemctl reload nginx
```

`nginx -t` checks the config before it goes live, so a typo cannot take the server down. If it prints `syntax is ok` and `test is successful`, `systemctl reload nginx` applies it with no downtime. This is the habit from [testing config safely](/course/nginx-basics/configuration-basics/testing-config-safely).

## Step 15: Test it

From your own machine:

```bash
curl -I http://yourdomain.com
```

A `200 OK` on the homepage means the whole chain works: DNS to nginx to PHP-FPM to Laravel to MySQL. Open the domain in a browser and click through a few routes to confirm the front controller is routing correctly.

If something is off, the [troubleshooting checklist](/course/nginx-basics/real-world/troubleshooting-checklist) is your friend. The quick reads:

- **502 Bad Gateway**: PHP-FPM is not running or the socket path in `fastcgi_pass` is wrong. Check `systemctl status php8.4-fpm`.
- **Every route except `/` is 404**: `try_files` is wrong, re-check Step 13.
- **"Permission denied" writing logs**: permissions, re-run Step 10.
- **The default nginx page shows**: `server_name` does not match, or you did not remove the default site.

The [full Laravel stack](/course/nginx-basics/real-world/full-laravel-stack) lesson walks the same pieces from a real-world angle if you want a second pass.

## Common mistake: caching config before editing .env

If you run `php artisan config:cache` and *then* edit `.env`, the app keeps serving the old, cached values, so your database password change (or `APP_URL`) seems to have no effect. Order matters: edit `.env` first, cache second. When in doubt, `php artisan optimize:clear` wipes every cache and you start clean.

## Common mistake: wrong ownership on storage

Running `composer install` or `php artisan` as root creates root-owned files that PHP-FPM (running as `www-data`) cannot write. The app then throws permission errors at runtime even though everything "installed fine". Always finish by handing `storage` and `bootstrap/cache` to `www-data`, and prefer running artisan/composer as the web user.

## FAQ

### Do I need Node or npm on the server?

Only if you compile front-end assets on the server, and most teams do not. Build your CSS and JS **locally** (or in CI), commit the compiled files, and the server just serves them. That keeps the VPS simple and the deploy fast. It is the same reasoning behind not building CSS on the server for this very site.

### How do I deploy an update later?

Pull the new code and re-run the production steps: `git pull`, then `composer install --no-dev --optimize-autoloader`, `php artisan migrate --force`, and refresh caches with `php artisan optimize:clear` followed by `config:cache`, `route:cache`, `view:cache`. Reload nginx only if you changed the server block.

### Why MySQL and not SQLite?

SQLite works and needs no separate service, which is great for small sites. MySQL is shown here because it is the common production choice and matches most tutorials. The nginx and Laravel setup is identical either way; only the `DB_*` values in `.env` change.

### Where does HTTPS come in?

This lesson leaves the site on plain HTTP (port 80) on purpose, so you can confirm it works before adding TLS. The [next lesson](/course/nginx-basics/deploying-to-a-vps/https-with-lets-encrypt) adds a free Let's Encrypt certificate and redirects HTTP to HTTPS in a couple of commands.
