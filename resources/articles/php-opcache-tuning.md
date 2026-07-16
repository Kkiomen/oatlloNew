---
name: "Tuning PHP OPcache for Production"
slug: php-opcache-tuning
short_description: "How to configure OPcache for a production PHP app: the settings that matter, why validate_timestamps=0 changes your deploy, and how to monitor it."
language: en
published_at: 2027-05-12 09:00:00
is_published: true
tags: [php, performance, devops, laravel]
---

The first time OPcache bit me, the app had been running fine for three weeks. Then a hotfix went out, we cleared nothing, and half the servers kept serving the old code while the other half served the new one. Same commit deployed everywhere. The difference was that some workers had recompiled and some hadn't. That afternoon taught me more about OPcache than any config guide, and it's the reason this article spends as much time on deploys as it does on `php.ini`.

OPcache is on by default in modern PHP and it already does the heavy lifting. But the defaults are tuned to be *safe on a laptop*, not fast on a box that serves the same 4,000 files ten thousand times a minute. This is about closing that gap without setting a trap for your next release.

## What OPcache actually caches

Every time PHP runs a script, it reads the file, tokenizes it, parses it into an AST, and compiles that into opcodes — the low-level instructions the Zend engine executes. Without a cache, that whole pipeline runs on *every single request*, for every file the request touches. On a Laravel app that's easily hundreds of files per request, all recompiled from scratch each time.

OPcache does the compile once and stores the resulting opcodes in shared memory. Every subsequent request for that file skips straight to execution. That's it — it caches the *compiled* form, not the *result*. It won't cache your database queries or your rendered HTML. It removes the compilation tax, which on a real app is a large slice of your CPU time.

The important word is **shared** memory. The opcode cache lives in a segment shared across all PHP-FPM workers, so worker #1 compiling a controller means workers #2 through #40 get it for free. That's also why the deploy story gets tricky, which we'll get to.

## The settings that matter

You can ignore most of the `opcache.*` knobs. These are the ones that move the needle:

```ini
; php.ini — production baseline
opcache.enable=1
opcache.enable_cli=0

; how much shared memory for compiled opcodes (MB)
opcache.memory_consumption=256

; how many files OPcache can hold — set ABOVE your real file count
opcache.max_accelerated_files=20000

; dedup memory for repeated strings (class names, etc.), MB
opcache.interned_strings_buffer=32

; the deploy-critical pair — see below
opcache.validate_timestamps=0
opcache.revalidate_freq=0
```

**`memory_consumption`** is the size of the opcode cache in megabytes. Too small and OPcache starts evicting files it already compiled, so they get recompiled on the next hit — you pay the compile tax you were trying to avoid, plus you thrash. 128 MB is a reasonable floor for a framework app; 256 MB gives headroom. Don't just crank it to 1 GB "to be safe" — that memory is reserved, not borrowed, and it's gone from everything else on the box. Size it from real numbers, which the monitoring section shows you how to read.

**`max_accelerated_files`** is a *slot count*, not a byte count, and it's the setting people get wrong most often. If you have 12,000 PHP files and this is set to 10,000, roughly 2,000 files never get cached — they recompile forever and you'll never notice unless you look. PHP rounds this up to the next value in a set of primes (it uses the number as a hash table size), so setting 20000 actually gives you 24917 slots. Set it comfortably above `find . -name '*.php' | wc -l` for your whole deployment, vendor included.

**`interned_strings_buffer`** deduplicates strings that appear over and over — fully-qualified class names, method names, common literals. In a large codebase the same `App\Http\Controllers\...` string exists in memory once instead of hundreds of times. The default is 8 MB, which a big Laravel or Symfony app blows through; bump it to 16 or 32. When this buffer fills, dedup silently stops and your effective memory usage climbs.

## validate_timestamps and the deploy problem

Here's the setting that separates a dev box from a tuned production box, and the one that caused my three-server split-brain.

By default, **`opcache.validate_timestamps=1`**. That means OPcache checks each cached file's modification time to decide whether the cache is stale. `opcache.revalidate_freq` controls how often — `revalidate_freq=2` means "at most check every 2 seconds." This is what lets you edit a file in development and see the change without clearing anything.

In production, that `stat()` check on every file is pure overhead, because your code doesn't change between deploys. So you set:

```ini
opcache.validate_timestamps=0
```

Now OPcache never checks timestamps. It compiles a file once and trusts that copy *forever*. This is faster — no filesystem checks at all. But it means something dangerous: **when you deploy new code, OPcache keeps serving the old opcodes.** Nothing about a `git pull` or a `rsync` invalidates the cache. The files on disk are new; the cache is old; PHP serves the old one until you force it otherwise.

So `validate_timestamps=0` is not a free win — it's a trade. You gain speed and you take on the responsibility of clearing the cache yourself on every release. Get that half wrong and you get exactly my afternoon: some workers cleared, some not, code from two commits live at once.

### Clearing the cache on deploy

You have to reset OPcache after new code lands. The blunt way is to restart or reload PHP-FPM:

```bash
# graceful reload — finishes in-flight requests, then restarts workers
sudo systemctl reload php8.4-fpm
```

A reload throws away the shared memory segment, so every worker starts cold. The first requests after a reload are slow because everything recompiles, but it's clean and it's atomic across all workers. For most teams this is the right answer — it's simple and it can't leave you half-cleared.

The other option is `opcache_reset()`, but be careful: it only affects the process that runs it. Calling it from a web request resets the cache for the FPM pool, but calling it from the CLI (`php -r 'opcache_reset();'`) resets a *separate* CLI cache and does nothing to FPM. If you script a reset, hit an FPM endpoint, don't run it from cron on the CLI and assume it worked. This is the exact mistake that produced my split-brain — the reset ran, it just ran in the wrong process.

The most robust pattern is **atomic symlink deploys**: build the new release in a fresh directory, then flip a `current` symlink. Because the path changes, OPcache treats the new files as genuinely different entries and there's no stale-cache window — as long as your FPM `pm` isn't pinned to the old inode. Deployer, Envoyer, and Capistrano-style tools all lean on this.

## Monitoring: don't fly blind

You cannot tune what you can't see, and OPcache tells you everything through `opcache_get_status()`. Drop this on an internal, auth-gated route and read it after a day of real traffic:

```php
<?php
// GET /internal/opcache — protect this behind admin auth
$status = opcache_get_status(false); // false = skip per-file list

$mem  = $status['memory_usage'];
$stats = $status['opcache_statistics'];

$usedMb   = round($mem['used_memory'] / 1048576, 1);
$freeMb   = round($mem['free_memory'] / 1048576, 1);
$wastedMb = round($mem['wasted_memory'] / 1048576, 1);

$hits   = $stats['hits'];
$misses = $stats['misses'];
$hitRate = $hits + $misses > 0
    ? round($hits / ($hits + $misses) * 100, 2)
    : 0.0;

echo json_encode([
    'hit_rate_pct'      => $hitRate,
    'used_mb'           => $usedMb,
    'free_mb'           => $freeMb,
    'wasted_pct'        => round($mem['current_wasted_percentage'], 2),
    'cached_scripts'    => $stats['num_cached_scripts'],
    'max_scripts'       => $stats['max_cached_keys'],
    'restart_oom'       => $stats['oom_restarts'],      // out-of-memory restarts
    'restart_hash'      => $stats['hash_restarts'],     // slot table full
], JSON_PRETTY_PRINT);
```

Read these numbers like a checklist:

- **`hit_rate_pct`** should sit above ~99% on a warm production box. If it's low, either the cache is too small (files get evicted and re-missed) or you're accidentally caching CLI runs into the mix. A hit rate that *drops over time* means eviction — you're out of room.
- **`oom_restarts`** greater than zero is the loudest alarm here. It means OPcache ran out of memory and did a full restart, blowing the whole cache away. Every OOM restart is a compile storm. If you see these climbing, raise `memory_consumption`.
- **`restart_hash`** greater than zero means you ran out of *slots*, not bytes — raise `max_accelerated_files`. This is the symptom of the miscount I described earlier.
- **`wasted_pct`** creeping up is normal on a box with `validate_timestamps=1` (old versions of files pile up as wasted memory); on a `validate_timestamps=0` box it should stay near zero between deploys. High wasted memory eventually forces a restart.

If you'd rather not write your own, `cachetool` gives you the same numbers from the CLI (`cachetool opcache:status`), and the classic `opcache-gui` script renders it in a browser. Either way, the point is the same: look at the real numbers before you touch a single setting.

## The JIT: mostly not your win

PHP 8 added a JIT compiler, exposed through `opcache.jit` and `opcache.jit_buffer_size`. It's the feature I get asked about most, and the one that pays back least.

```ini
; JIT — measure before you assume this helps
opcache.jit=tracing
opcache.jit_buffer_size=128M
```

Here's the honest version. The JIT compiles hot opcodes down to native machine code, and for **CPU-bound** work — Mandelbrot, tight numeric loops, image processing, that kind of thing — it's a real, sometimes dramatic speedup. That's what the impressive benchmarks measure.

But a typical web request isn't CPU-bound. It waits on the database, waits on Redis, waits on an HTTP call, then renders some templates. The bottleneck is I/O, and the JIT does nothing for waiting. On most Laravel/Symfony apps the JIT delivers low-single-digit gains at best, and I've seen it produce *no measurable difference* on request latency while adding a buffer of memory and a class of harder-to-debug crashes. My default on standard web apps is to leave it off and spend the effort on query optimization, which is where the time actually goes. Turn it on only if you profiled a genuinely CPU-heavy path and can measure the win.

## Preloading: worth it for frameworks

`opcache.preload` is the more interesting PHP 8 feature for web apps. It loads a set of files into OPcache *once at server startup* and keeps them permanently linked in memory — resolved, wired together, ready — so they're never even looked up per request.

```ini
opcache.preload=/var/www/app/preload.php
opcache.preload_user=www-data
```

The preload script itself decides what to warm. Laravel ships a package (`laravel-preload` style setups) and Symfony generates a `var/cache/prod/App_KernelProdContainer.preload.php` for you — point `opcache.preload` at that. Preloading the framework's core classes shaves the per-request linking cost and can give a real latency improvement on hot paths.

The catch that surprises people: **preloaded files are frozen until FPM restarts.** There's no timestamp check for them at all. So preloading tightens the same knot as `validate_timestamps=0` — after a deploy you *must* restart FPM to reload preloaded classes, no exceptions. If your deploy already reloads FPM (it should), you're fine. If it doesn't, preloading will hand you stale-code bugs that look impossible.

## A pragmatic production checklist

- Set `memory_consumption` from real usage, not vibes — start at 256 MB and watch `oom_restarts`.
- Set `max_accelerated_files` above your true file count; confirm no `hash_restarts`.
- Turn `validate_timestamps=0` **only if** your deploy reliably reloads FPM.
- Reload FPM on every deploy; prefer atomic symlink releases so there's no stale window.
- Reset from FPM, never assume a CLI `opcache_reset()` cleared the pool.
- Bump `interned_strings_buffer` to 16–32 MB on large codebases.
- Add preloading for your framework, and remember it hard-requires an FPM restart to update.
- Leave the JIT off unless you measured a CPU-bound path that needs it.
- Expose `opcache_get_status()` behind auth and actually look at it.

## FAQ

**Do I need to clear OPcache after every deploy?**
If `validate_timestamps=0`, yes — always. OPcache will keep serving old opcodes until you reload FPM or reset the pool from within FPM. If `validate_timestamps=1`, the cache self-heals within `revalidate_freq` seconds, at the cost of a `stat()` on every file on every request. Production wants the first setup plus a disciplined reload.

**Why is my `opcache_reset()` from a cron job not working?**
Because CLI and FPM have separate OPcache instances. Running `php -r 'opcache_reset();'` clears the CLI cache and leaves your web workers untouched. Reset by hitting an internal FPM endpoint, or just reload PHP-FPM.

**Is the JIT worth enabling for a Laravel or WordPress site?**
Usually not. Those apps spend their time waiting on the database and rendering templates, not in tight numeric loops, so the JIT's native-code speedup has little to bite on. Measure first; if request latency doesn't move, leave it off.

**How big should `memory_consumption` be?**
Big enough that `used_memory` never approaches the limit and `oom_restarts` stays at zero. Start at 256 MB for a framework app, check `opcache_get_status()` after a day of real traffic, and adjust. It's reserved memory, so don't oversize it for no reason.

**What's the difference between `max_accelerated_files` and `memory_consumption`?**
One is a count of files (slots in a hash table), the other is bytes of storage. You can run out of either independently — `hash_restarts` means you're out of slots, `oom_restarts` means you're out of bytes. Watch both.

OPcache rewards a few minutes of attention more than almost any other PHP knob, because you're removing work the engine does on every request. But the win comes with a string attached: the faster you make it, the more deliberate your deploys have to be. Set `validate_timestamps=0`, wire an FPM reload into your release step, put `opcache_get_status()` somewhere you'll actually see it, and the split-brain afternoon never comes for you.
