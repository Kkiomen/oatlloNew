---
name: "Understanding PHP Fibers and Concurrency"
slug: php-fibers-concurrency
short_description: "A practical guide to PHP fibers: what they do, how cooperative concurrency works, a runnable example, and why you rarely call them directly."
language: en
published_at: 2026-12-04 09:00:00
is_published: true
tags: [php, concurrency, async]
---

The first time I read the RFC for PHP fibers I assumed I'd found a way to run two things at once in plain PHP. I was wrong, and the misunderstanding cost me an afternoon. PHP fibers do not give you parallelism, they don't spin up threads, and nothing in your script suddenly runs simultaneously. What they give you is something narrower and, once it clicks, genuinely useful: a call stack you can pause in the middle and pick up again later.

This guide walks through what a fiber actually is, shows a minimal example you can run, and is honest about the part most tutorials skip: you probably won't type `new Fiber(...)` in your own application code. Let's get into why that's fine.

## What a PHP fiber actually is

A fiber is a low-level primitive for **cooperative concurrency**. That phrase does a lot of work, so let me unpack both halves.

*Concurrency* is about structure, not speed. It means several tasks are in progress and interleaved, taking turns on a single thread. *Parallelism* is when tasks literally execute at the same instant on different cores. Fibers give you the first, never the second.

*Cooperative* means nothing gets interrupted against its will. A fiber runs until it decides to hand control back by calling `Fiber::suspend()`. There's no scheduler forcibly slicing time away from it. The fiber cooperates by yielding voluntarily, and whoever started it decides when to resume.

So think of a fiber as a function whose execution you can freeze mid-way, walk away from, and thaw later exactly where it stopped, with all its local variables intact. That's the whole idea. Added in PHP 8.1, it's the missing building block that userland async libraries had been faking for years with generators.

### Fibers versus generators

If you've used `yield`, this will feel familiar, and that's not a coincidence. Generators can also suspend and resume. The catch with generators is that the suspension point has to be visible in the function signature — a generator is a special kind of function, and the `yield` has to bubble all the way up through every caller.

Fibers remove that constraint. You can suspend from deep inside a nested function call, several frames down, without every intermediate function needing to know it's participating in async code. That single difference is why async runtimes moved to fibers.

## The core API, and nothing more

The whole surface is small. Here are the pieces you need:

- `new Fiber(callable)` wraps a callable in a fiber. Creating it does not run it.
- `$fiber->start(...$args)` begins execution and runs until the first `suspend` or until the callable returns.
- `Fiber::suspend($value)` is called *from inside* the fiber. It hands control back to whoever started or resumed it, optionally passing a value out.
- `$fiber->resume($value)` continues a suspended fiber. The value you pass becomes the return value of the `suspend()` call inside.
- `$fiber->getReturn()` gives you the value the callable returned, once it has actually finished.

There are a few helpers too, like `$fiber->isSuspended()` and `Fiber::getCurrent()`, but the five above are the heart of it.

## A minimal, runnable example

Here's a complete script. It creates a fiber, lets it run partway, and then resumes it. Save it and run it with PHP 8.1 or later.

```php
<?php

$fiber = new Fiber(function (): void {
    echo "A: fiber started\n";

    // Hand control back to the main script, passing a value out.
    $received = Fiber::suspend('paused halfway');

    echo "C: fiber resumed with: {$received}\n";
});

// start() runs the callable up to the first suspend().
$valueFromFiber = $fiber->start();
echo "B: main got: {$valueFromFiber}\n";

// The fiber is frozen right now. We do other work here if we want.

// resume() thaws it; 'the answer' becomes the return of suspend() inside.
$fiber->resume('the answer');
```

Run it and the output is:

```
A: fiber started
B: main got: paused halfway
C: fiber resumed with: the answer
```

Trace the letters and you can see the control flow bouncing between the two contexts. `start()` runs line A, then `Fiber::suspend('paused halfway')` freezes the fiber and returns that string to the main script, which prints line B. The main script is now fully in charge; the fiber is parked. When we call `resume('the answer')`, the `suspend()` call inside the fiber evaluates to `'the answer'`, execution continues from that exact point, and line C prints.

Notice there is no second thread, no `pcntl_fork`, no extension. It's one script, one stack of execution that we're pausing and resuming. That's cooperative concurrency in its purest form.

### Getting a return value

`suspend()` passes intermediate values; `getReturn()` collects the final one. A quick variation:

```php
<?php

$fiber = new Fiber(function (): int {
    $x = Fiber::suspend();   // wait for input
    return $x * 2;
});

$fiber->start();             // runs to the suspend
$fiber->resume(21);          // feeds 21 into $x, fiber finishes

echo $fiber->getReturn();    // 42
```

Calling `getReturn()` before the fiber has finished throws a `FiberError`, so only reach for it once the callable has actually run to completion.

## The part most articles skip: you won't use this directly

Here's the honest framing. A fiber on its own does not make anything asynchronous. If you call `file_get_contents()` on a slow URL inside a fiber, the whole process still blocks on that call. Suspending is manual and explicit — the fiber only pauses when *your code* calls `suspend()`. Nothing pauses it when I/O would block.

So what makes fibers valuable? They're the engine that async frameworks hide behind a clean API. Libraries like [Revolt](https://revolt.run) with AMPHP, and newer versions of ReactPHP, run an **event loop**. When your code awaits a network response, the library:

1. Kicks off the non-blocking I/O operation.
2. Calls `Fiber::suspend()` for you, parking your task.
3. Lets the event loop run other parked tasks while the socket is busy.
4. Calls `resume()` with the result once the data has arrived.

You write what looks like ordinary top-to-bottom code, and the runtime does the suspend/resume dance underneath. That's the payoff: async code without callback pyramids or explicit `yield` everywhere. The fiber is the plumbing, not the faucet.

Concretely, in AMPHP v3 you'd write something closer to this:

```php
<?php

use function Amp\async;
use function Amp\Future\await;

// Each async() call schedules work on the event loop, backed by a fiber.
$responses = await([
    async(fn () => fetch('https://api.example.com/users')),
    async(fn () => fetch('https://api.example.com/orders')),
]);
```

Both requests are in flight concurrently on one thread. While one waits on the network, the other makes progress. Under the hood each `async()` task lives in a fiber, and `await` is what triggers the suspends. You never touch the `Fiber` class yourself.

## Pitfalls and when to use fibers

Things that trip people up:

- **Fibers are not parallelism.** CPU-bound work won't speed up. If you need true parallel execution, look at `ext-parallel`, process forking, or a job queue like [Laravel job batching](/blog/laravel-job-batching), not fibers.
- **A blocking call blocks everything.** Mixing a plain blocking database driver into an async event loop stalls the whole loop. You need non-blocking drivers for the concurrency to mean anything.
- **`getReturn()` before completion throws.** Guard it with `isTerminated()` if you're not certain.
- **You can't suspend the `{main}` fiber.** Calling `Fiber::suspend()` outside any fiber is a fatal error. Suspension only makes sense inside one.
- **Debugging is less obvious.** Stack traces jump around as execution moves between fibers, which takes some getting used to.

When fibers genuinely earn their place:

- You're the author or maintainer of an async runtime, an event loop, or a coroutine library.
- You're building something that juggles many concurrent I/O operations (hundreds of open sockets, a web-scraper, a chat server) and a framework built on fibers fits the shape of the problem.
- You want to understand what tools like AMPHP are doing so you can debug them with confidence.

For the vast majority of Laravel or Symfony apps doing request-response work, you can go your whole career without instantiating a fiber. And that's the intended design.

## FAQ

### Are PHP fibers the same as threads?

No. Threads (via extensions like `parallel`) run code in parallel across cores with their own scheduling. Fibers run on a single thread and only switch when your code explicitly suspends. Fibers are about interleaving tasks cooperatively, not running them at the same time.

### Do I need to install an extension to use fibers?

No. `Fiber` is part of core PHP from 8.1 onward. You just need PHP 8.1 or newer. The async *libraries* built on top of fibers are separate Composer packages, but the primitive itself ships with the language.

### Can fibers make my existing blocking code asynchronous automatically?

Unfortunately not. A fiber only pauses when you call `Fiber::suspend()`. A blocking function like a synchronous cURL call will still block the entire process. Real async behaviour requires non-blocking I/O plus an event loop, which is exactly what libraries like Revolt provide.

### Should I use fibers directly in my application?

Almost never. Treat them like the language internals they resemble — powerful primitives meant for library authors. If you want concurrency in your app, reach for a battle-tested runtime like AMPHP or ReactPHP and let it manage the fibers for you.

## Conclusion

PHP fibers are a small, precise addition: a callable whose stack you can suspend and resume at will, giving you cooperative concurrency on a single thread. They are not threads, not parallelism, and not a magic wand that makes blocking I/O async. Their real job is to be the foundation that async runtimes stand on, which is why you'll benefit from them mostly without ever writing `new Fiber` yourself.

If you want to go deeper, do two things. Run the minimal example above and step through the letters until the control flow feels obvious. Then install AMPHP v3 on a throwaway project and fire off a handful of concurrent HTTP requests. Seeing your own code stay responsive while several requests are in flight is the moment fibers stop being abstract. When you're comfortable, you'll know exactly which layer is doing the work, and that understanding is worth far more than memorizing the API. For more modern PHP features worth knowing, the guides on [PHP enums](/blog/php-enums-complete-guide) and [readonly properties](/blog/php-readonly-properties) pair well with this one.