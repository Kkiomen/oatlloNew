---
name: "Deploying Laravel to a VPS Step by Step"
slug: deploy-laravel-to-vps
short_description: "A from-scratch Ubuntu VPS deploy for Laravel: nginx, php-fpm, permissions, caching, queues via systemd, TLS with certbot, and a safe pull-based script."
language: en
published_at: 2027-05-31 09:00:00
is_published: true
tags: [laravel, devops, nginx, php]
---

The first time I put a Laravel app on a bare VPS, everything worked until I opened a page that wrote to a log. `The stream or file "/var/www/app/storage/logs/laravel.log" could not be opened: failed to open stream: Permission denied`. The app ran fine as my user during setup, then php-fpm ran it as `www-data` and couldn't write anything. That one mismatch is the single most common reason a fresh deploy 500s, and it's the thread that runs through this whole guide.

This walks through a real deploy on a fresh Ubuntu 24.04 box: PHP 8.4, nginx + php-fpm, a git-based deploy, queues and the scheduler as real services, HTTPS with Let's Encrypt, and a deploy script you can run without praying. No panels, no magic. If you'd rather pay someone to do this for you, I'll tell you exactly when that's the smarter call at the end.

## Before you SSH in

You need a domain with an A record pointing at the server's IP, and a non-root sudo user. Rolling with `root` for everything works right up until a stray command deletes something it shouldn't. Create a user, give it sudo, and log in as that from here on.

```bash
adduser deploy
usermod -aG sudo deploy
```

Point your DNS `A` record at the server now, before touching certbot. TLS issuance fails if the domain doesn't resolve to this machine yet, and DNS propagation can lag by minutes.

## Install PHP 8.4 and the extensions Laravel actually needs

Ubuntu's default repos lag behind on PHP, so use Ondřej Surý's PPA — it's the de facto source for current PHP on Debian/Ubuntu.

```bash
sudo add-apt-repository ppa:ondrej/php
sudo apt update
sudo apt install -y php8.4-fpm php8.4-cli php8.4-mbstring php8.4-xml \
  php8.4-bcmath php8.4-curl php8.4-zip php8.4-mysql php8.4-intl php8.4-gd
```

The extensions matter more than people expect. `mbstring`, `xml`, `bcmath`, `curl`, `zip` are Laravel baseline. `mysql` (really pdo_mysql) is your database driver — swap for `pgsql` if you're on Postgres. `intl` gets pulled in by a lot of formatting and validation code, and `gd` (or `imagick`) is for image work. Miss one and `composer install` will refuse the lock file with a `requires ext-...` line, which is at least an honest error.

Grab Composer too:

```bash
sudo apt install -y composer git unzip
```

## Get the code onto the server with a deploy key

Don't paste your personal SSH key onto a server. Generate a key *on the server* that only has read access to this one repo — a deploy key. Then nothing on the box can push to anything but this repo, and revoking it is a one-click affair.

```bash
ssh-keygen -t ed25519 -C "deploy@myapp" -f ~/.ssh/id_ed25519_deploy
cat ~/.ssh/id_ed25519_deploy.pub
```

Add that public key as a **read-only deploy key** in your repo settings (GitHub: Settings → Deploy keys). Tell SSH to use it for this host:

```bash
cat >> ~/.ssh/config <<'EOF'
Host github.com
  IdentityFile ~/.ssh/id_ed25519_deploy
  IdentitiesOnly yes
EOF
```

Now clone into the web root:

```bash
sudo mkdir -p /var/www/myapp
sudo chown deploy:deploy /var/www/myapp
git clone git@github.com:you/myapp.git /var/www/myapp
cd /var/www/myapp
```

## Install dependencies for production, not development

This is the line that separates a dev checkout from a deploy:

```bash
composer install --no-dev --optimize-autoloader --no-interaction
```

`--no-dev` skips PHPUnit, Faker, and friends — you don't test on the production box, and shipping those is dead weight and extra attack surface. `--optimize-autoloader` builds a class map so PHP resolves classes by array lookup instead of stat-ing the filesystem on every autoload. On a real app the difference is measurable per request. If your app never runs `artisan` at build time, add `--classmap-authoritative` to skip filesystem checks entirely, but only if you're sure nothing loads classes generated at runtime.

## Environment and the app key

Laravel ships an `.env.example`, never a real `.env` — the actual file is gitignored, so you create it on the server.

```bash
cp .env.example .env
php artisan key:generate
```

`key:generate` writes `APP_KEY`, the secret behind every encrypted cookie and `Crypt::` call. If you deploy across multiple servers behind a load balancer, generate it once and copy the *same* key everywhere — a mismatched `APP_KEY` invalidates every session and encrypted value the moment a request lands on a different box. Set `APP_ENV=production`, `APP_DEBUG=false`, your database credentials, and the real `APP_URL`. Leaving `APP_DEBUG=true` in production is how stack traces with your DB password end up on a public error page.

## The permission gotcha, done right

Here's the file-ownership problem from the intro, solved properly. php-fpm runs your app as `www-data`. Laravel writes to exactly two trees: `storage/` and `bootstrap/cache/`. Everything else can stay owned by your deploy user and be read-only to the web server. That's the safe posture — the web server can't rewrite your application code, only its scratch directories.

```bash
sudo chown -R deploy:www-data /var/www/myapp
sudo find /var/www/myapp -type f -exec chmod 644 {} \;
sudo find /var/www/myapp -type d -exec chmod 755 {} \;
sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache
```

The tempting shortcut is `chmod -R 777 storage`. Don't. `777` means any user on the box can rewrite your cached views and compiled config, and on a shared or compromised host that's a code-execution path. `775` with `www-data` owning the tree gives the web server what it needs and nothing more.

If you deploy as one user but php-fpm runs as another, the cleanest fix is the group: own `storage` and `bootstrap/cache` by `www-data`, and make sure your deploy user is in the `www-data` group so it can still write during deploys.

## nginx server block with php-fpm

Point nginx at Laravel's `public/` directory — never the project root, or you'd expose `.env` and `storage` over HTTP.

```nginx
server {
    listen 80;
    server_name myapp.com www.myapp.com;
    root /var/www/myapp/public;

    index index.php;
    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.4-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

The `try_files ... /index.php?$query_string` line is the whole point of a front-controller framework: any URL that isn't a real file gets routed through `index.php`, where Laravel's router takes over. The `fastcgi_pass` socket path must match your PHP version — `php8.4-fpm.sock` here. The final `location` block hides dotfiles while still letting `.well-known` through, which certbot needs in a second.

Save it as `/etc/nginx/sites-available/myapp`, then enable and reload:

```bash
sudo ln -s /etc/nginx/sites-available/myapp /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl reload nginx
```

`nginx -t` before every reload. It catches the typo before it takes the site down instead of after.

## Cache config, routes, and views

Laravel can compile its config, routes, and Blade views into flat PHP files so it skips parsing them on every request. In production you always do this:

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

One trap that bites people: once you run `config:cache`, calls to `env()` outside of config files return `null`. The cached config no longer reads `.env` at runtime. So if you have `env('SOME_KEY')` scattered through your app code, move those into `config/` files and reference `config('...')` instead. This is a Laravel rule anyway, but caching is where ignoring it turns into a silent bug.

Any time you change `.env` on the server, you must rebuild the cache or your change does nothing:

```bash
php artisan config:clear && php artisan config:cache
```

## Run migrations

```bash
php artisan migrate --force
```

`--force` is required because migrations are a destructive operation and Laravel refuses to run them in production without you saying so explicitly. That prompt exists for a reason — read what the migration does before you force it on live data.

## Queues and the scheduler as real services

Two things people forget on their first deploy, then wonder why emails never send and nothing runs "every day at midnight."

The **scheduler** needs a single cron entry — one line that ticks Laravel's scheduler every minute:

```bash
* * * * * cd /var/www/myapp && php artisan schedule:run >> /dev/null 2>&1
```

The **queue worker** is a long-running process, and `php artisan queue:work` in a terminal dies the moment you close SSH. You want it supervised so it restarts on crash and on boot. systemd handles this natively — no extra package:

```ini
# /etc/systemd/system/myapp-worker.service
[Unit]
Description=Laravel queue worker for myapp
After=network.target

[Service]
User=www-data
Group=www-data
Restart=always
RestartSec=3
ExecStart=/usr/bin/php /var/www/myapp/artisan queue:work \
  --sleep=3 --tries=3 --max-time=3600

[Install]
WantedBy=multi-user.target
```

```bash
sudo systemctl daemon-reload
sudo systemctl enable --now myapp-worker
```

The `--max-time=3600` is deliberate: a queue worker holds your app in memory and won't pick up new code until it restarts. Capping its lifetime means it recycles hourly and picks up your latest deploy without you having to remember. If you want to run several workers in parallel, use systemd template units (`myapp-worker@1`, `@2`) or Supervisor if that's what your team already knows — both work, systemd just means one less thing to install.

After every deploy, restart the worker so it runs your new code:

```bash
sudo systemctl restart myapp-worker
```

Or use `php artisan queue:restart`, which tells running workers to gracefully quit after their current job — systemd then brings them straight back.

## HTTPS with Let's Encrypt

certbot reads your nginx config, adds the TLS block, and reloads — no hand-editing:

```bash
sudo apt install -y certbot python3-certbot-nginx
sudo certbot --nginx -d myapp.com -d www.myapp.com
```

It'll offer to redirect HTTP to HTTPS; say yes. Certificates last 90 days, and certbot installs a systemd timer that renews them automatically. Confirm the renewal path actually works with a dry run:

```bash
sudo certbot renew --dry-run
```

If that passes, you never think about certificates again. If it fails, it's almost always because the `.well-known` path is blocked — check that dotfile `deny` block in your nginx config lets `.well-known` through, like the one above does.

## A safe-ish, pull-based deploy script

For a single box, a git-pull deploy is honest and good enough. It is *not* truly zero-downtime — there's a sub-second window during `migrate` and cache rebuild where a request could hit half-updated state. For most apps that's fine. If it isn't, that's your signal to move to atomic releases (Envoyer, Deployer, or symlinked release folders), which is a different article.

```bash
#!/usr/bin/env bash
set -euo pipefail
cd /var/www/myapp

php artisan down --retry=15

git pull --ff-only origin main
composer install --no-dev --optimize-autoloader --no-interaction

php artisan migrate --force

php artisan config:cache
php artisan route:cache
php artisan view:cache

php artisan queue:restart
php artisan up
```

`set -euo pipefail` is the load-bearing line: the script aborts on the first failed command instead of blindly continuing past a broken `composer install` and putting a half-deployed app live. `php artisan down` throws up the maintenance page so users see a clean "be right back" instead of errors mid-migration, and `--retry` sets the `Retry-After` header. `git pull --ff-only` refuses to create a merge commit — if the branch has diverged, you want it to stop and let you look, not paper over it.

One warning about `artisan down`: by default it also blocks *you*. Pass `--secret=some-token` and visit `/some-token` to bypass the maintenance page so you can smoke-test before letting traffic back in.

## When you should just use Forge, Ploi, or Envoyer

I've walked you through the manual path because you should understand what's actually happening on the box. But be honest about your time. **Laravel Forge** and **Ploi** provision exactly this stack — nginx, php-fpm, the deploy user, Let's Encrypt, queue workers — from a web UI in a few minutes, and they keep it patched. **Envoyer** adds true zero-downtime atomic deploys with instant rollback.

Reach for a panel when: you're managing more than one or two servers, you don't want to be the person who owns OS security updates at 2am, or downtime genuinely costs you money. The ~$12–20/month is cheaper than an afternoon of your time, every month, forever.

Do it by hand when: it's a side project or a learning exercise, you have exactly one server, or you have a specific reason the managed stack doesn't fit. Knowing how the pieces connect also means that when Forge's deploy fails, you can actually read the nginx and php-fpm logs and fix it — instead of filing a support ticket and waiting.

## FAQ

**Why do I get "Permission denied" on storage/logs even though the app worked during setup?**
Because you ran it as your user during setup, and php-fpm runs it as `www-data`. Give `storage/` and `bootstrap/cache/` to `www-data` with `775`. Don't reach for `777` — that hands write access to every user on the box.

**Do I need Nginx Unit or can I stick with nginx + php-fpm?**
nginx + php-fpm is the boring, battle-tested default and it's what Forge and Ploi provision too. Stick with it unless you have a measured reason to change. Alternatives like FrankenPHP or Octane are about keeping the app in memory for throughput, not about the base deploy.

**How do I get true zero-downtime deploys?**
The git-pull script here has a tiny window during migration and cache rebuild. For genuine zero downtime you need atomic releases — deploy into a fresh timestamped folder and flip a symlink once it's ready, so the switch is instant and rollback is just flipping the symlink back. Deployer and Envoyer do this for you.

**My scheduled tasks aren't running. What did I miss?**
Almost always the single cron entry. Laravel's scheduler needs `* * * * * php artisan schedule:run` in cron running every minute; without it, none of your `schedule()` definitions ever fire. Check `crontab -l` for the user php runs as.

## The one thing to remember

Most first Laravel deploys fail on the same two things: file permissions and forgetting that php-fpm runs as a different user than you did during setup. Get `storage` and `bootstrap/cache` owned by `www-data` with sane permissions, cache your config and routes, and run your queue and scheduler as real supervised services — and the rest is just wiring you can copy-paste. Next step: put the deploy script in the repo, run it once by hand watching the output, and only then wire it to a webhook.
