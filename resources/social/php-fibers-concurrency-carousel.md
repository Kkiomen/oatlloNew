---
slug: php-fibers-concurrency-carousel
type: carousel
language: en
title: "PHP fibers"
topic: php
source_type: article
source: php-fibers-concurrency
link: https://oatllo.com/php-fibers-concurrency
publish_at: 2026-11-18 19:00
status: ready
formats: [post, reel]
hashtags: [php, concurrency, async, amphp, backend]
caption: |
  I read the fibers RFC and assumed I had found a way to run two things at once in plain PHP. I was wrong.

  A fiber is one thread, one stack, paused and resumed on purpose. No threads,
  no parallelism, and no async unless something calls suspend() for you.

  Full guide linked in bio.

  Have you ever written `new Fiber` in app code?
---

## PHP fibers give you concurrency, but never actual parallelism

Concurrency is about structure: several tasks interleaved, taking turns on one
thread. Parallelism is tasks running at the same instant on different cores.

<!-- slide -->

## Cooperative means nothing gets interrupted

A fiber runs until it decides to hand control back with `Fiber::suspend()`.
There is no scheduler slicing time away from it. It yields voluntarily, and
whoever started it decides when to resume.

<!-- slide -->

## A stack you can freeze and thaw

```php
$fiber = new Fiber(function (): void {
    echo "A\n";
    $got = Fiber::suspend('paused halfway');
    echo "C: {$got}\n";
});
$fiber->start(); // runs to the suspend
$fiber->resume('the answer'); // prints C
```

Locals intact, execution continues from that exact line. Added in PHP 8.1.

<!-- slide -->

## Generators could do this. The yield had to bubble up.

A generator's suspension point lives in its signature, so every caller must
know it is part of async code. A fiber suspends several frames down without
anyone in between noticing.

<!-- slide -->

## A blocking call inside a fiber blocks everything

`file_get_contents()` on a slow URL still stops the whole process. A fiber only
pauses when your code calls `suspend()`. Nothing pauses it when I/O would
block.

<!-- slide role="cta" -->

## You will benefit from fibers without ever writing one

```php
$responses = await([
    async(fn () => fetch($usersUrl)),
    async(fn () => fetch($ordersUrl)),
]);
```

AMPHP kicks off the non-blocking I/O, suspends for you, and resumes on arrival.
Each task lives in a fiber. You never touch the class.
