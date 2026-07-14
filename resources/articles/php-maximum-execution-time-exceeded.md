---
name: "Fixing \"Maximum Execution Time Exceeded\" in PHP"
slug: php-maximum-execution-time-exceeded
short_description: "Why the PHP maximum execution time exceeded error happens, how max_execution_time, set_time_limit and ini_set differ, and the real fix."
language: en
published_at: 2026-07-13 09:00:00
is_published: true
tags: [php, php-fpm, debugging, performance]
---

If you have ever refreshed a page and been greeted by `Fatal error: Maximum execution time of 30 seconds exceeded`, you have met one of PHP's oldest safety valves. The **php maximum execution time exceeded** error is not a bug in your framework or your server. It is PHP doing exactly what it was told: kill any script that runs longer than a fixed number of seconds so a single request can't hog the worker forever.

The message looks scary, but it's actually helpful. It's a smoke alarm. And the mistake almost everyone makes on the first encounter is to run over and rip the alarm off the ceiling instead of checking why there's smoke.

Let me walk through what the error really means, the three different knobs that control it, the layers above PHP that can *also* kill your request, and why bumping the limit is usually the wrong move.

## What "maximum execution time exceeded" actually means

The full message looks like one of these:

```
Fatal error: Maximum execution time of 30 seconds exceeded in /var/www/app/Foo.php on line 42
Fatal error: Maximum execution time of 60 seconds exceeded
```

PHP tracks how long your script has been *executing* and, once it crosses `max_execution_time`, it throws a fatal error and stops. The number in the message is whatever that limit is set to on the current SAPI (more on SAPIs below).

One detail that trips people up: on most platforms the timer counts **CPU/execution time inside PHP**, not real wall-clock time. Time spent waiting on external calls behaves differently depending on the OS. On Windows, for historical reasons, the timer has counted real elapsed time. On Linux, time blocked in a system call — a slow database query, a `sleep()`, an external HTTP request — has traditionally *not* counted against `max_execution_time`. That's why a script stuck on a slow query can hang far longer than the limit suggests, while a tight `for` loop trips it right on schedule.

Keep that distinction handy: it decides *which* timer you'll actually hit when a request hangs.

## The three knobs: max_execution_time vs set_time_limit vs ini_set

There are three ways to control the limit, and they are not interchangeable.

**1. `max_execution_time` in `php.ini`**: the global default.

```ini
; php.ini
max_execution_time = 30
```

This is the baseline applied to every request for that SAPI. The classic default for the web SAPI is `30`. Change it here and restart PHP-FPM (or Apache) for it to take effect. This is the value you'll see quoted in the error message when nothing else has overridden it.

**2. `ini_set()`**: change the directive at runtime, for the current script only.

```php
ini_set('max_execution_time', '120');
```

This sets the same directive from inside your code. It affects only the running request and does not persist. `max_execution_time` is `PHP_INI_ALL`, so `ini_set()` is normally allowed to change it, but plenty of shared hosts lock the value or list `ini_set`/`set_time_limit` in `disable_functions`, in which case the call returns `false` (or throws) and the limit stays put. Check the return value if it matters; treat it as a request, not a guarantee.

**3. `set_time_limit()`**: the one most people actually want.

```php
set_time_limit(120);   // allow 120 seconds from this point
set_time_limit(0);     // no limit
```

Here's the part that surprises people: **`set_time_limit()` resets the counter to zero and starts counting again from the moment you call it.** It does not "add 120 seconds to the budget you've already used." Call it at the top of a long import loop, or better, inside the loop per batch, and each call gives the script a fresh window.

```php
foreach ($chunks as $chunk) {
    set_time_limit(60);   // fresh 60s budget for each chunk
    processChunk($chunk);
}
```

Calling `set_time_limit(0)` removes the limit entirely for that script. Powerful, and dangerous. An infinite loop with no limit will run until *something else* kills it (see the FPM and web-server sections below).

Quick mental model:

- **`php.ini`** = the default for everyone.
- **`ini_set('max_execution_time', ...)`** = override the directive for this request.
- **`set_time_limit(N)`** = reset the clock to zero and grant N more seconds from *now*.

## CLI is unlimited by default

If you run the same script from the command line and it *doesn't* time out, you're not imagining it. The **CLI SAPI ships with `max_execution_time = 0`** — unlimited — regardless of what your web `php.ini` says. This is deliberate: long-running workers, migrations, queue consumers and cron jobs shouldn't be killed by a web-oriented timeout.

You can confirm it:

```bash
php -i | grep max_execution_time
# max_execution_time => 0 => 0
```

So a batch job that dies at 30 seconds through the browser but finishes happily via `php artisan ...` or `php script.php` is behaving exactly as designed. This is also the single best reason to move heavy work out of the request cycle: run it on the CLI or in a queue where the timeout simply doesn't apply.

## It's not just PHP: PHP-FPM and the web server have their own timeouts

This is where hours get lost. You raise `max_execution_time` to 300, reload, and the request *still* dies around 30 or 60 seconds, often with a `504 Gateway Timeout` instead of the PHP fatal error. That's because in a typical stack there are **three independent timers**, and the shortest one wins.

### PHP-FPM: `request_terminate_timeout`

PHP-FPM has its own hard kill switch, set per pool:

```ini
; /etc/php/8.3/fpm/pool.d/www.conf
request_terminate_timeout = 120
```

When set, FPM forcibly terminates any worker whose request runs longer than this, *independently* of `max_execution_time`, and even if your script called `set_time_limit(0)`. It exists to recover a worker that's genuinely wedged. If this is lower than your PHP limit, FPM wins and you'll see the worker killed (look for it in the FPM log) without a clean PHP fatal error. By default it is off (`0`), inheriting PHP's own limit.

### The web server timeout

Nginx and Apache each have a timeout for how long they'll wait on the backend.

Nginx, talking to FPM over FastCGI:

```nginx
location ~ \.php$ {
    fastcgi_pass unix:/run/php/php8.3-fpm.sock;
    fastcgi_read_timeout 120s;   # how long nginx waits for FPM to respond
    include fastcgi_params;
}
```

If `fastcgi_read_timeout` (default 60s) expires first, nginx returns **504 Gateway Timeout** to the browser and closes the connection, even though PHP may still be running in the background. For Apache with `mod_proxy_fcgi`, the equivalent knobs are the proxy `timeout` parameter and `ProxyTimeout`.

So the effective ceiling on a web request is:

```
min(max_execution_time, request_terminate_timeout, fastcgi_read_timeout)
```

If you're going to raise a limit, you often have to raise **all three in lockstep**, and then restart/reload each service. Miss one and you'll swear the setting "isn't working."

## Why raising the limit is usually the wrong fix

Here's the uncomfortable truth from years of chasing these: a script that legitimately needs more than 30 seconds inside a web request is almost always doing something in the wrong place.

I once inherited a "reports" page that timed out. The previous fix in the codebase was a lonely `set_time_limit(0)` at the top of the controller. It "worked": the page loaded in 90 seconds. Profiling took ten minutes and showed the real problem: a query running inside a `foreach` over 4,000 rows. Classic N+1. One eager-load turned 4,000 queries into two, and the page rendered in under a second. The timeout was never the disease; it was the fever.

Before touching any timeout, look for the usual suspects:

- **N+1 queries**: a query fired inside a loop. Enable your ORM's query log or Debugbar and count them.
- **Missing database indexes**: a full table scan that was fine at 1,000 rows and lethal at 1,000,000.
- **Unbounded loops**: pulling an entire table into memory instead of paginating or chunking.
- **Slow external calls**: a third-party API with no timeout of its own, blocking your request.
- **Genuinely long jobs in the request**: CSV exports, image processing, PDF generation, bulk imports.

For that last category, the fix isn't a bigger timeout; it's **getting the work out of the request entirely**: push it to a queue worker or a scheduled CLI command where `max_execution_time` is already `0`. The user gets an instant "we're processing it" response, and nothing racing a 60-second clock.

Raise the limit only when you've confirmed the work is inherently long *and* can't be moved, and even then, prefer a scoped `set_time_limit()` around the specific slow section over a global bump.

## Common pitfalls

- **Editing the wrong `php.ini`.** CLI, FPM and Apache each load a different file. Run `php --ini` (CLI) and check `phpinfo()` on the web side to see the *actual* loaded path. Editing the CLI ini and expecting the browser to change is a top time-waster.
- **Forgetting to restart.** `php.ini` and FPM pool changes need a `systemctl restart php8.3-fpm` (or Apache reload). The old value stays in memory until you do.
- **Assuming `set_time_limit()` adds time.** It resets to zero and starts over. Re-read that if you're relying on it.
- **Ignoring the 504.** A gateway timeout means the web server gave up, not PHP. Bumping only `max_execution_time` won't help. Check `fastcgi_read_timeout` and `request_terminate_timeout`.
- **`set_time_limit(0)` as a band-aid.** It hides runaway loops and lets a wedged request tie up an FPM worker indefinitely, starving other traffic.
- **Blaming the timer for a blocked call.** On Linux, time stuck in a slow query may not count toward `max_execution_time`, so the real limit hit is often the web server's, not PHP's.

## FAQ

**Where do I permanently change PHP's max execution time?**
Set `max_execution_time` in the `php.ini` that your web SAPI actually loads (verify the path via `phpinfo()`), then restart PHP-FPM or Apache. For a single script, use `set_time_limit()` or `ini_set('max_execution_time', ...)` instead; no restart needed.

**Why does my script still time out after I increased `max_execution_time`?**
Almost always another timer is shorter: PHP-FPM's `request_terminate_timeout` or the web server's `fastcgi_read_timeout` (nginx) / proxy timeout (Apache). Raise all of them together, or you'll keep hitting the lowest one, usually surfacing as a 504.

**Does the timeout apply to command-line scripts?**
No. The CLI SAPI defaults to `max_execution_time = 0` (unlimited), which is why the same code runs fine from the terminal or a queue worker but dies in the browser. Moving heavy work to the CLI/queue is often the cleanest fix.

**Is `set_time_limit(0)` safe to use?**
Sparingly, and ideally only for CLI/queue jobs. In a web request it can let a stuck script hold an FPM worker forever. Prefer finding the slow query or loop, or a scoped `set_time_limit(N)` around the known-long section.

## Conclusion

The **php maximum execution time exceeded** error is a guardrail, not a defect. Treat it as a signal: something took longer than expected, and PHP stopped it before it ate a worker. Reach for the profiler before the config file. Nine times out of ten you'll find an N+1 query, a missing index, or a loop that should have been a queued job — and fixing that is faster and more permanent than any timeout you could set.

When you genuinely do need more time, remember the full picture: `max_execution_time` sets PHP's clock, `set_time_limit()` resets it, CLI ignores it, and PHP-FPM plus your web server have their own timers that the shortest one always wins. Change the limit deliberately, in the right file, at every layer, and restart the service. Then get back to fixing the actual slow code.