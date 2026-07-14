---
name: "Laravel Sail vs Laravel Herd: Local Dev Compared"
slug: laravel-sail-vs-herd
short_description: "Laravel Sail vs Herd for local development: speed, Docker parity, databases, and which one fits your daily workflow. A practical, hands-on comparison."
language: en
published_at: 2027-02-26 09:00:00
is_published: true
tags: [laravel, docker, herd, sail, local-development]
---

Pick the wrong local setup and you feel it every single day: a five-second wait before a page loads, a mystery MySQL container that won't boot, a fan spinning up because Docker is reindexing your `vendor` folder for the hundredth time. The **laravel sail vs herd** question isn't really about which tool is "better" in the abstract. It's about what your machine, your team, and your production stack actually need.

I've run both as a primary environment on real projects. This post is the comparison I wish I'd had before I switched back and forth twice.

## The short version

Laravel Herd is a native app that bundles PHP and nginx (and, on the Pro tier, databases and other services) so your sites just work with almost no setup. Laravel Sail is a thin Docker wrapper that gives you containers matching a production-like stack.

Herd is faster and simpler for everyday coding. Sail gives you reproducible, Docker-based parity with your servers. Most teams end up using one for daily work and the other where it earns its keep. More on that at the end.

## What Laravel Sail actually is

Sail is Laravel's official light wrapper around Docker Compose, aimed at local development. You add it to an existing project and it generates a `docker-compose.yml` plus a `sail` script that proxies commands into your containers.

```bash
composer require laravel/sail --dev
php artisan sail:install
./vendor/bin/sail up
```

That last command boots your containers. By default you get PHP running your app, and you can opt into MySQL, PostgreSQL, Redis, Meilisearch, Mailpit, and a few others during `sail:install`.

Keep the mental model straight: `sail artisan migrate` doesn't run Artisan on your host machine. It runs *inside* the PHP container:

```bash
./vendor/bin/sail artisan migrate
./vendor/bin/sail composer require guzzlehttp/guzzle
./vendor/bin/sail npm run dev
```

Most people alias `sail` in their shell so they don't type `./vendor/bin/` fifty times a day.

**Why this is genuinely nice:** everyone on the team gets the same PHP version, the same MySQL version, the same everything, defined in a file that's committed to the repo. New hire clones the project, runs `sail up`, and has a working environment in the time it takes Docker to pull images. That reproducibility is the whole pitch, and it delivers.

**Where it hurts:** Sail needs Docker Desktop running, and on macOS and Windows Docker runs inside a VM. File syncing between your host and that VM is the classic bottleneck. On a big Laravel app with a heavy `vendor` directory, page loads that should be instant can drift into the hundreds of milliseconds, sometimes worse. It's a known trade-off of the Docker-on-a-non-Linux-host model, not a Sail bug specifically. On native Linux the overhead mostly disappears.

## What Laravel Herd actually is

Herd is a native application for macOS and Windows. It ships a bundled PHP, nginx, and a DNS layer that maps your project folders to `.test` domains automatically. There's no Docker, no VM, no compose file. You drop a Laravel project into a parked directory and it's served at `myapp.test` right away.

The free tier covers PHP and nginx and lets you switch PHP versions per site. The Pro tier adds managed services: database engines like MySQL and PostgreSQL, Redis, and a mail-catching tool, plus some conveniences around logs and profiling. I'm keeping that list deliberately loose because the exact Pro feature set moves over time; check Herd's own site for the current details before you buy.

The experience is hard to oversell if you've been fighting Docker. Sites are served natively, so requests hit PHP directly with no filesystem-sync tax. Cold starts feel instant. Switching a project from PHP 8.2 to 8.3 is a dropdown, not a container rebuild.

**Where Herd gets awkward:** because it isn't Docker, it isn't your production environment. If your app depends on a specific Linux extension, a queue worker topology, or a service that only exists in your container stack, Herd can quietly diverge from what ships. "Works on Herd" is not the same guarantee as "works in the container we deploy."

## Head to head

Here's how the two stack up on the things that actually change your day:

| Factor | Laravel Herd | Laravel Sail |
| --- | --- | --- |
| Underlying tech | Native PHP + nginx | Docker Compose containers |
| Requires Docker Desktop | No | Yes |
| Setup effort | Install app, drop in folder | Add package, install, boot containers |
| Everyday speed (macOS/Windows) | Near-instant | Slower due to Docker file sync |
| Everyday speed (Linux) | Fast | Fast |
| Production parity | Approximate | Strong (same images/services) |
| Team reproducibility | Per-machine install | Config committed to repo |
| Databases/services | Bundled (Pro) | Opt-in containers (free) |
| Cost | Free tier + paid Pro | Free (Docker Desktop licensing may apply) |
| Best fit | Daily local dev | CI and prod-like environments |

A couple of these deserve a footnote. Docker Desktop is free for personal use and small businesses but requires a paid subscription for larger organizations, so "Sail is free" comes with an asterisk depending on your company size. And "production parity: approximate" for Herd isn't a dig; for a huge number of standard Laravel apps, approximate is completely fine.

## Speed, honestly

This is where the debate usually lives, so let me be concrete about my own experience rather than quoting synthetic benchmarks.

On a Mac, working in Herd, page loads on a mid-sized app felt effectively instant. The same app under Sail with the default bind-mount setup was noticeably laggy during heavy request cycles, enough that I started tuning volume caching and eventually reached for tools that sync files more aggressively. It was fixable, but it was work I had to think about.

On a Linux workstation the gap basically vanished, because there's no VM boundary for files to cross. If you develop on Linux, the speed argument for Herd mostly evaporates and the decision becomes about workflow preference and parity.

So the speed story is really a *platform* story. If you're on macOS or Windows and latency annoys you, Herd's native approach is the more comfortable path.

## Parity, and why it still matters

The reason not to just declare Herd the winner and go home: your production servers almost certainly run Linux in containers. Sail lets you develop against something shaped like that. Same base image, same PHP extensions, same service versions, all pinned in a file your teammates share.

If you've ever shipped code that passed locally and then fell over in production because of a missing extension or a version mismatch, you understand why people tolerate Docker's overhead. That class of bug is exactly what container parity is designed to prevent.

If you want to go deeper on the containerized workflow itself, we've covered [running a Docker Compose local dev setup](/blog/docker-compose-local-dev) and [dockerizing Laravel for production](/blog/dockerize-laravel-production) separately. And if your images are ballooning, [reducing Docker image size for PHP](/blog/reduce-docker-image-size-php) is worth a read before you blame Sail for being slow.

## Can you use both?

Yes, and honestly a lot of people do. There's no rule that says you must commit to one environment.

A pattern I like:

- Use **Herd** for day-to-day coding, where fast feedback loops matter most and you're mostly writing feature code against a database.
- Keep a **Sail** (or plain Docker Compose) definition in the repo for CI and for the moments you need to reproduce a production-shaped bug or verify behavior against the real service versions.

The two aren't mutually exclusive because they solve different problems. Herd optimizes your inner loop. Docker optimizes confidence that what you built runs where it'll live.

The one caveat: don't let the two drift. If your Herd PHP version wanders away from your container's PHP version, you reintroduce exactly the mismatch parity was supposed to kill. Keep them aligned.

## FAQ

### Is Laravel Herd free?

Herd has a free tier that covers PHP and nginx and per-site PHP version switching, which is enough for a lot of local development. The Pro tier is a paid upgrade that adds managed databases and other services. Check Herd's site for current Pro pricing and the exact feature list.

### Does Laravel Sail work without Docker?

No. Sail is a wrapper around Docker Compose, so you need Docker installed and running (Docker Desktop on macOS and Windows, or Docker Engine on Linux). If avoiding Docker is your goal, Herd is the tool that fits.

### Why is Laravel Sail slow on my Mac?

The usual culprit is file syncing between your host and the Docker VM, not Sail itself. Bind-mounting a large project across that boundary adds latency to every filesystem read. Tuning volume caching helps; switching to a native environment like Herd sidesteps the issue entirely on macOS and Windows.

### Which is better for a team, Sail or Herd?

For guaranteed-identical environments across a team and CI, Sail's committed Compose config is the stronger choice. For raw daily speed on individual machines, Herd wins. Many teams run Herd locally and Sail/Docker in CI, which gets you both.

## Conclusion

If you develop on macOS or Windows and you mostly write standard Laravel apps, install Herd and enjoy the near-instant feedback. It removes a whole category of Docker friction from your day, and for most projects the parity gap never bites.

If your app leans on specific services, unusual extensions, or a team that needs byte-for-byte identical setups, keep Docker in the loop through Sail, at minimum for CI and prod-shaped debugging. On native Linux, where Docker's overhead is small, Sail as a primary environment is very reasonable.

My actual recommendation for most Laravel developers on a laptop: **Herd for the inner loop, Docker/Sail for parity and CI.** You get the speed where you feel it and the safety where it counts, and the laravel sail vs herd question stops being either/or.