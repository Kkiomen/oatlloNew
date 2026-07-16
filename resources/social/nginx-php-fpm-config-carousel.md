---
slug: nginx-php-fpm-config-carousel
type: carousel
language: en
title: "Nginx root at public"
topic: nginx
source_type: article
source: nginx-php-fpm-config
link: https://oatllo.com/nginx-php-fpm-config
publish_at: 2026-11-24 19:00
status: ready
formats: [post, reel]
hashtags: [nginx, php, devops, laravel, security]
caption: |
  Point root at the project folder instead of public/ and you just published your source tree. Bots find /.env fast.

  The config works either way. That is the problem - nothing warns you. Open /.env in a browser right now and confirm you get a 403.

  Full production block linked in bio.

  What did your last 502 turn out to be?
---

## root at the project folder, not public/. Your .env is now live.

Nothing breaks. The site renders. Your app code, vendor and `.env` all sit
inside the web root now, and anyone who asks for them gets them.

<!-- slide -->

## Everything sits above the web root

```nginx
root /var/www/example.com/public;

location / {
  try_files $uri $uri/
    /index.php?$query_string;
}
```

Drop `?$query_string` and your route params silently vanish. Everything looks
fine until you notice controllers never see them.

<!-- slide -->

## try_files =404 is a security line

```nginx
location ~ \.php$ {
  try_files $uri =404;  # security line
  fastcgi_pass unix:/run/php/php8.3-fpm.sock;
}
```

Without it, `/uploads/evil.jpg/x.php` can trick nginx into handing a non-PHP
file to FPM to execute.

<!-- slide -->

## Users can write there. Deny PHP there.

```nginx
location ~* /(?:uploads|files)/.*\.php$ {
  deny all;
}
```

Put it **before** your `location ~ \.php$` block. Regex locations match in
order and the first hit wins.

<!-- slide -->

## Size max_children, don't guess it

```ini
pm = dynamic
pm.max_children = 20
pm.max_requests = 500
```

Measure real worker RSS, then divide your RAM budget by it. 60 MB workers and
2.5 GB for PHP lands near 40. Guess high and a spike swaps the box.

<!-- slide role="cta" -->

## Then request /.env in a browser

You want a 403. If you get your actual env contents, stop everything and fix
it now. That one check saves the embarrassing week. Full block in bio.
