---
slug: profile-slow-php-application-carousel
type: carousel
language: en
title: "Stop guessing, measure"
topic: php
source_type: article
source: profile-slow-php-application
link: https://oatllo.com/profile-slow-php-application
publish_at: 2026-10-21 19:00
status: ready
formats: [post, reel]
hashtags: [php, performance, laravel, xdebug, debugging]
caption: |
  I read the controller for two hours and "fixed" the wrong thing. It was one query, 400 times.

  Check wall time against CPU time before you open any tool. Low CPU with
  high wall time means the request is waiting, and no PHP refactor saves
  a request that is stuck in traffic.

  Full guide linked in bio.

  What was your worst hotspot, honestly?
---

## One database query fired 400 times, not the loop I rewrote.

Two hours of reading the controller. The page was exactly as slow, and I
had introduced a bug on the way.

<!-- slide -->

## 900ms wall. 40ms CPU. Close the editor.

The request spent 860ms waiting on the disk, the database or a socket.
Optimising PHP there is tuning the engine while the car sits in traffic.

<!-- slide -->

## Two built-ins, a two minute sanity check

```php
$start = microtime(true);
$rows = $repo->buildMonthlyReport($id);
printf("%.4f s\n", microtime(true) - $start);
```

It will not say which function inside is slow. It will say whether you are
looking at I/O or computation, and that is the fork in the road.

<!-- slide -->

## Profile one request, not every request

```ini
xdebug.mode=profile
xdebug.output_dir=/tmp/xdebug
xdebug.start_with_request=trigger
```

`trigger` means only requests carrying `XDEBUG_TRIGGER` get profiled. Sort
the cachegrind file by inclusive cost, then drill until self cost names
the offender.

<!-- slide -->

## 412 queries, 780ms. No call graph needed.

Debugbar shows the same query repeated with different IDs. That is N+1,
the most common cause of a slow Laravel page I have met. The profiler was
never the right tool for it.

<!-- slide role="cta" -->

## Fix one thing. Measure the same way.

The 400-query checkout ended as one `with()` call and one index. Eight
hundred milliseconds gone in two lines, because I stopped guessing. Full
guide linked in bio.
