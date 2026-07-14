---
name: "Laravel Task Scheduling and Cron Jobs Explained"
slug: laravel-task-scheduling-and-cron-jobs
short_description: "A practical guide to Laravel task scheduling: one cron entry, Schedule facade syntax in Laravel 11+, frequencies, locks, and the traps."
language: en
published_at: 2026-11-02 09:00:00
is_published: true
tags: [laravel, cron, scheduling, devops]
---

The first time I set up Laravel task scheduling on a production box, I did the dumb thing: I added five separate crontab lines, one per job. It worked for about a week, until I needed to change a run time and had to SSH in and edit raw cron syntax at 11pm. That is exactly the pain Laravel's scheduler exists to remove. You define everything in PHP, in version control, and the server only ever needs one cron entry.

This guide walks through how it actually works, the syntax change that landed in Laravel 11, the frequency methods you'll reach for, and the handful of things that quietly break in production.

## The one cron entry you actually need

Here's the part that trips people up. The operating system's cron still runs; Laravel doesn't replace it. But instead of registering one crontab line per task, you register a single line that wakes Laravel up every minute:

```cron
* * * * * cd /path-to-your-project && php artisan schedule:run >> /dev/null 2>&1
```

Every minute, cron calls `schedule:run`. Laravel then looks at everything you've defined, works out what is *due* right now, and runs only those tasks. A job set to `->daily()` will be evaluated 1,440 times a day but actually fire once. That evaluation is cheap.

To install it, run `crontab -e` on the server as the user your app runs as (often `www-data` or a deploy user, **not** root) and paste that line. Swap `/path-to-your-project` for the absolute path to your app root, the directory that contains `artisan`.

The `>> /dev/null 2>&1` tail just discards stdout and stderr so cron doesn't email you every minute. Once things are stable that's fine. While you're debugging, point it at a real file instead:

```bash
* * * * * cd /var/www/app && php artisan schedule:run >> /var/www/app/storage/logs/schedule.log 2>&1
```

## Where schedules live: the Laravel 11 change

This is the single biggest source of confusion right now, because half the tutorials online are pre-Laravel 11 and half aren't.

**Laravel 10 and earlier:** you defined tasks inside `app/Console/Kernel.php`, in a `schedule()` method:

```php
// Laravel <= 10, in app/Console/Kernel.php
protected function schedule(Schedule $schedule): void
{
    $schedule->command('reports:send')->daily();
}
```

**Laravel 11 and 12:** the `Console\Kernel` class is gone. Scheduled tasks now live in `routes/console.php` and you use the `Schedule` facade directly:

```php
// Laravel 11+, in routes/console.php
use Illuminate\Support\Facades\Schedule;

Schedule::command('reports:send')->daily();
```

Same fluent API, different home. If you're upgrading an older app and can't find `Kernel.php`, that's why: it wasn't lost, the whole file was removed and its scheduling responsibility moved into `routes/console.php`. Everything below uses the 11+ style, but the frequency and constraint methods are identical across versions.

You can schedule three kinds of things:

- Artisan commands, via `Schedule::command('reports:send')`
- Queued jobs, via `Schedule::job(new HeavyReport)`
- Shell commands, via `Schedule::exec('node /scripts/import.js')`
- Closures, via `Schedule::call(fn () => Cache::forget('stale'))`

## Frequencies: saying when things run

The fluent methods cover almost every schedule you'll ever write, and they read like plain English:

```php
use Illuminate\Support\Facades\Schedule;

Schedule::command('cache:prune')->everyMinute();
Schedule::command('feeds:refresh')->hourly();
Schedule::command('reports:send')->dailyAt('13:00');
Schedule::command('backups:run')->weeklyOn(1, '02:00'); // Monday 02:00
```

When the built-in helpers don't fit, say you want every five minutes or some odd interval, drop down to raw cron expressions with `->cron()`:

```php
Schedule::command('metrics:collect')->cron('*/5 * * * *'); // every 5 minutes
```

A couple of things worth internalizing:

- Times use your app's configured timezone by default. You can override per-task with `->timezone('America/New_York')`, which matters a lot around daylight-saving changes.
- You can chain day constraints: `->weekdays()`, `->sundays()`, `->between('9:00', '17:00')`. They compose, so `->hourly()->weekdays()->between('9:00', '17:00')` runs on the hour during office hours only.

To see the whole picture without guessing, run:

```bash
php artisan schedule:list
```

It prints every registered task with its next due time. I run this after every schedule change, and it has caught more than one typo'd cron expression for me before it ever hit production.

## Developing locally without cron

You don't want to install a crontab on your laptop. Laravel ships a long-running command for exactly this:

```bash
php artisan schedule:work
```

It stays in the foreground and invokes the scheduler every minute, just like the production cron would. Leave it running in a terminal tab while you develop. Kill it with Ctrl+C when you're done. It's the local equivalent of the cron line, nothing more. Don't ship it to production as a daemon and expect it to behave like a real scheduler under load.

## The constraints that keep production sane

The fluent API is nice, but these four methods are the ones that separate a toy schedule from something you trust on real infrastructure.

**Preventing overlaps.** If a task occasionally runs longer than its interval, the next tick can start a second copy on top of the first. `->withoutOverlapping()` acquires a lock so the second run is skipped:

```php
Schedule::command('import:large-feed')
    ->everyMinute()
    ->withoutOverlapping();
```

By default the lock expires after 24 hours in case a process dies without releasing it. You can shorten that: `->withoutOverlapping(10)` for a 10-minute lock.

**Running on one server only.** If you run the scheduler on multiple app servers (common behind a load balancer), every server's cron fires `schedule:run` and every server tries to run every task. For a nightly report, that means duplicate emails. `->onOneServer()` fixes it: the first server to grab the lock runs the task, the rest skip it:

```php
Schedule::command('reports:send')
    ->daily()
    ->onOneServer();
```

The catch: this needs a shared, atomic cache driver such as Redis, Memcached, or DynamoDB. The `file` or `database` locking has to be reachable by all servers, so a per-server file cache **will not** coordinate anything. If your cache is local to each box, `onOneServer()` silently does nothing useful.

**Backgrounding long tasks.** Normally tasks run sequentially, so a slow one delays everything queued behind it in that minute. `->runInBackground()` lets it run in a separate process:

```php
Schedule::command('analytics:crunch')
    ->hourly()
    ->runInBackground();
```

Use it for genuinely slow, independent tasks. For anything heavy, honestly, prefer pushing work onto a queue instead, as the next section explains.

**Conditional runs.** `->when()` and `->skip()` take a closure and decide at runtime whether the task should fire:

```php
Schedule::command('promo:activate')
    ->daily()
    ->when(fn () => now()->month === 12); // December only
```

## Scheduler vs. queues: a distinction people blur

A scheduled task and a queued job are not the same thing, and conflating them causes grief.

- The **scheduler** answers *when* something should start.
- A **queue** answers *how* heavy work gets processed without blocking.

A clean pattern is to schedule a thin command that just dispatches jobs onto the queue. The scheduler fires once, returns in milliseconds, and your queue workers do the actual lifting. If those jobs are large or numerous, batching them keeps the whole thing observable, and I've written up that approach in [Laravel job batching](/blog/laravel-job-batching). And because scheduled work fails eventually (it always does), pair it with a sane [retry strategy for failed jobs](/blog/laravel-retry-failed-jobs) rather than hoping the next tick cleans up the mess. If your scheduled command is really a reaction to something that happened, [events and listeners](/blog/laravel-events-listeners) may be a better fit than a polling schedule.

## Common pitfalls

The mistakes I've either made or watched others make, roughly in order of how often they bite:

- **Forgetting the cron entry entirely.** The code is perfect, `schedule:list` looks great, and nothing ever runs, because no cron is calling `schedule:run`. Always check the crontab first when a task "isn't firing."
- **Wrong user or wrong path.** Cron runs as whatever user owns the crontab. If that user can't write to `storage/` or read your `.env`, tasks fail with permission errors that never surface. Use an absolute path and the app's real user.
- **`onOneServer()` without a shared lock.** Covered above; with a per-server cache it does nothing, and you get duplicate runs anyway.
- **Assuming the server timezone.** Tasks use the app timezone, not necessarily the OS timezone. `dailyAt('02:00')` at 2am *for whom?* Check `config/app.php`.
- **Doing heavy work inline.** A 40-second job in `->everyMinute()` without `->withoutOverlapping()` stacks copies until the box falls over. Add the lock, or move it to a queue.
- **Silencing output too early.** Keep `2>&1` pointed at a log file until you've seen the schedule run cleanly a few times. `/dev/null` hides the exact error you need.

## FAQ

**Do I still need to write cron expressions at all?**
Only one, and only the `schedule:run` line. Everything else is expressed in PHP through the fluent API. You *can* use `->cron('*/5 * * * *')` for intervals the helpers don't cover, but that's a convenience, not a requirement.

**Why isn't my scheduled task running?**
Walk it in order: is the system cron entry present and pointing at the right path? Does `php artisan schedule:list` show the task with a sensible next-due time? Can the cron user read `.env` and write to `storage/logs`? Run `php artisan schedule:run` manually and read the output. Nine times out of ten the answer is right there.

**How do I run a task every 5 minutes?**
`Schedule::command('...')->cron('*/5 * * * *')`. There's no `->everyFiveMinutes()`… actually there is: `->everyFiveMinutes()` exists too, along with `everyTenMinutes()`, `everyFifteenMinutes()`, and `everyThirtyMinutes()`. Use whichever reads clearer to you.

**Can I test the scheduler without a real cron on my machine?**
Yes. Run `php artisan schedule:work` in a terminal. It behaves like the production cron, ticking every minute, until you stop it.

## Wrapping up

The mental model that makes all of this click: cron's only job is to nudge Laravel once a minute, and Laravel decides everything else in code you can read, diff, and review. Put the single `schedule:run` entry on the server, define tasks in `routes/console.php` with the `Schedule` facade (that's the Laravel 11+ location, not `Kernel.php` anymore), reach for `withoutOverlapping()` and `onOneServer()` before you go multi-server, and verify with `schedule:list` every time you change something.

Start with one scheduled command today, confirm it shows up in `schedule:list`, and watch it fire. Once you trust the loop, moving the rest of your cron sprawl into version-controlled PHP is the easy part.