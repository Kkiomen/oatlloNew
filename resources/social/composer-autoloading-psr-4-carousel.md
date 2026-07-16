---
slug: composer-autoloading-psr-4-carousel
type: carousel
language: en
title: "PSR-4 autoloading"
topic: php
source_type: article
source: composer-autoloading-psr-4
link: https://oatllo.com/composer-autoloading-psr-4
publish_at: 2026-09-04 19:00
status: ready
formats: [post]
hashtags: [php, composer, psr4, backend, webdev]
caption: |
  "Class not found" tells you nothing about why. PSR-4 is mechanical, so the answer is short.

  Prefix in, file path out. No scanning, no guessing. Once you can trace it in
  your head it stops being a mystery.

  Full walkthrough in bio.

  Which one of these ate your afternoon?
---

## Your class loads on Windows, then explodes when you deploy to Linux.

PSR-4 resolution is case-sensitive on a case-sensitive filesystem. Your machine
does not care. The container does. It never shows up locally.

<!-- slide -->

## The whole algorithm, no scanning

```php
// map: "App\\" => "src/"
App\Services\InvoiceMailer
// strip prefix, \ becomes /, add .php
src/Services/InvoiceMailer.php
```

Longest prefix wins, so `App\Tests\UserTest` matches `App\Tests\` before `App\`.

<!-- slide -->

## This is why it only breaks on the server

```php
class:  App\Services\InvoiceMailer
loads:  src/services/invoicemailer.php
```

macOS and Windows happily load that. Linux does not forgive it. Capital S, I
and M, in the directory and the filename.

<!-- slide -->

## Stop re-dumping for every new class

`composer dump-autoload` is for two things: you edited the `autoload` block, or
you added a class to a **classmapped** directory. PSR-4 computes the path at
runtime, so a new file just loads.

<!-- slide -->

## classmap does not know about new files

The map is built once by scanning. Drop a class into a classmapped directory
and it stays invisible until you re-dump. That is the answer to "the file is
right there".

<!-- slide role="cta" -->

## Production wants the opposite of dev

```bash
composer install --no-dev \
  --optimize-autoloader \
  --classmap-authoritative
```

Authoritative skips the filesystem entirely - fast, and fatal to anything
generated at runtime.
