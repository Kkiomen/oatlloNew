---
slug: php-autoload-carousel
type: carousel
language: en
title: "Fixing Class Not Found autoload errors in PHP"
topic: php
source_type: article
source: php-class-not-found-autoload
link: https://oatllo.com/php-class-not-found-autoload
publish_at: 2026-07-20 19:00
status: ready
formats: [post, reel]
hashtags: [php, composer, autoloading, debugging, webdev]
caption: |
  The class is right there on disk and PHP still swears it doesn't exist.

  "Class not found" almost never means the file is missing. It means Composer
  couldn't turn the class name into a path it's allowed to load. Read the FQCN
  in the error. It's exactly what the autoloader searched for.

  Full write-up linked in bio.

  Which one got you last: a stale map, or a namespace that didn't match the folder?
verified:
  verdict: issues
  at: 2026-07-16 07:15
  fingerprint: 4dadc1900f9f55c0cdb57ffbfa4d2a049a7b71a3
  notes: |
    Slide 4 (The map doesnt know the file yet) is wrong in the context the post builds. It says Composer caches the class to file map and a class you just wrote stays invisible until you regenerate it - but slides 2 and 3 establish PSR-4, and under PSR-4 a brand-new class in an already-mapped namespace loads immediately, with no dump-autoload. I verified this by hand: registered a PSR-4 prefix, created a class file seconds later that exists in no classmap, and class_exists() returned true (classMapAuthoritative false). The claim is true for CLASSMAP dirs (Laravel database/seeders, database/factories), for a NEW namespace prefix or an edited autoload block, and for production --optimize / --classmap-authoritative. It is not true for the plain PSR-4 case the slide is sitting next to. The source article says both things - the dump-autoload section states it broadly, then the classmap section and the FAQ correct it (PSR-4 can find new files on the fly in development) - and the post inherited the broad version without the correction. This is a PHP audience; the reply this earns is PSR-4 does not need dump-autoload for that. Suggested fix: reframe the slide around what actually goes stale - a classmapped dir, a changed autoload block, or an optimized production autoloader - rather than any newly written class.
---

## The class is right there. PHP says it doesn't exist.

You can open the file. PHP still throws Class not found.

<!-- slide -->

## PSR-4 is just string surgery

```json
{
  "autoload": {
    "psr-4": { "App\\": "app/" }
  }
}
```

`App\Services\InvoiceMailer` becomes `app/Services/InvoiceMailer.php`. Off by
one character and the class stops existing.

<!-- slide -->

## The namespace lies about the folder

```php
// file: app/Services/InvoiceMailer.php
namespace App\Service;

class InvoiceMailer {}
```

Singular. PSR-4 looks in `app/Service/`, finds nothing, gives up.

<!-- slide -->

## The map doesn't know the file yet

Composer caches the class to file map. A class you just wrote stays invisible
until you regenerate it.

```bash
composer dump-autoload
```

<!-- slide -->

## Works local. Dead on the server.

Your Mac or Windows filesystem is case insensitive, so `invoicemailer.php`
loads `InvoiceMailer` just fine. Linux isn't. Same code, first deploy, fatal.

<!-- slide role="cta" -->

## Laravel? One more cache to drop

```bash
composer dump-autoload
php artisan optimize:clear
```

A compiled cache can still point at the old FQCN long after the code is right.
