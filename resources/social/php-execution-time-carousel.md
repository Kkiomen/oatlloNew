---
slug: php-execution-time-carousel
type: carousel
language: en
title: "Fixing Maximum Execution Time Exceeded in PHP"
topic: php
source_type: article
source: php-maximum-execution-time-exceeded
link: https://oatllo.com/php-maximum-execution-time-exceeded
publish_at: 2026-07-28 19:00
status: ready
hashtags: [php, performance, debugging, laravel, backend]
caption: |
  On Linux, a script stuck on a slow query can hang far past max_execution_time.

  The timer does not count time blocked in a system call. So the tight loop
  trips at exactly 30 seconds, and the thing you actually need to fix sails
  right through the limit that was supposed to catch it.

  Full write-up linked in bio.

  Be honest: do you raise the limit, or go looking for why?
---

## Maximum execution time exceeded

PHP did exactly what you told it to. It killed the script at 30 seconds.

<!-- slide -->

## It is a smoke alarm

Raising `max_execution_time` is running over and ripping it off the ceiling.
The script still does something expensive. It just gets longer to do it.

<!-- slide -->

## The part nobody expects

On Linux, time blocked in a system call has traditionally not counted against
the timer. A slow query, a `sleep()`, an external HTTP call.

<!-- slide -->

## So the limit catches the wrong thing

A tight `for` loop trips right on schedule. A request hanging on a slow
database call can run far past the limit that was meant to stop it.

<!-- slide -->

## Three knobs, not one

```ini
; php.ini - the global default
max_execution_time = 30
```

```php
ini_set('max_execution_time', '120');
```

The ini file sets the baseline. `ini_set()` changes one script.

<!-- slide role="cta" -->

## Sometimes raising it is correct

A CLI import is not a web request. When the work is genuinely long, raise it
on purpose, in that one place. Just never as the first move.
