---
name: "Docker Healthchecks in Compose"
slug: docker-healthchecks-compose
short_description: "How to write Docker Compose healthchecks that fix the app-starts-before-db race, plus start_period, exit codes, and the slim-image gotcha."
language: en
published_at: 2027-06-11 09:00:00
is_published: true
tags: [docker, devops, compose, database]
---

The bug that finally made me care about healthchecks looked like flakiness. Our API container would come up, immediately try to connect to Postgres, get `Connection refused`, and crash. On CI it failed maybe one run in four. On a fast laptop it almost never failed. Same compose file, same images, different timing — which is the tell that you have a race, not a bug. The container was "up" the second Docker started the process. The database inside it wasn't ready to accept connections for another two or three seconds. Docker had no idea there was a difference, and neither did `depends_on`.

That gap — between a container running and the thing inside it being ready to do work — is the whole reason healthchecks exist. This walks through writing them properly in Compose, wiring `depends_on` so the app actually waits, and the handful of traps that cost me the most time.

## "Running" is not "ready"

By default Docker considers a container healthy the moment its main process has started. That's it. Postgres has forked but hasn't finished recovery. Your Laravel app has booted PHP-FPM but hasn't warmed the config cache. A JVM service is three seconds into a fifteen-second class-load. All of these report "up" instantly, and any container that `depends_on` them will barrel ahead into a service that isn't listening yet.

A healthcheck replaces "is the process alive" with "does the app answer a question correctly." You give Docker a command to run inside the container on a schedule. Exit code `0` means healthy, anything non-zero (conventionally `1`) means unhealthy. Docker tracks the result and exposes it as a container state you can actually depend on.

You can define the check in two places: baked into the image with the `HEALTHCHECK` instruction in a Dockerfile, or declared per-service in `docker-compose.yml`. They do the same job. I keep app checks in the Dockerfile so they travel with the image, and infrastructure checks (databases, caches) in Compose because that's where I'm already configuring those services.

## The five knobs

Every healthcheck is the same command plus four timing parameters. Here they are on a Postgres service:

```yaml
services:
  db:
    image: postgres:16
    environment:
      POSTGRES_USER: app
      POSTGRES_PASSWORD: secret
      POSTGRES_DB: app
    healthcheck:
      test: ["CMD", "pg_isready", "-U", "app", "-d", "app"]
      interval: 5s
      timeout: 3s
      retries: 5
      start_period: 10s
```

What each one does, because the names are less obvious than they look:

- **`test`** — the command. `["CMD", ...]` runs it directly (no shell). `["CMD-SHELL", "..."]` runs it through `/bin/sh -c`, which you need for pipes, `&&`, or environment-variable expansion.
- **`interval`** — how long between checks. Applies both before the first check and between subsequent ones.
- **`timeout`** — how long a single check may run before it's counted as a failure. A check that hangs is a failed check.
- **`retries`** — consecutive failures required before the container flips from `healthy` (or `starting`) to `unhealthy`. With `retries: 5` and `interval: 5s`, a service has to fail for ~25 seconds straight before it's declared unhealthy.
- **`start_period`** — a grace window at startup. Failures during this window don't count toward `retries` and don't mark the container unhealthy. The moment a check succeeds, the start period ends early.

`start_period` is the one people skip, and it's the one that matters most for slow boots. More on it below.

`pg_isready` is the right tool for Postgres specifically — it ships in the official image and checks whether the server is accepting connections, which is exactly the question you care about. For MySQL or MariaDB the equivalent is a ping:

```yaml
  db:
    image: mysql:8
    healthcheck:
      test: ["CMD", "mysqladmin", "ping", "-h", "localhost", "-psecret"]
      interval: 5s
      timeout: 3s
      retries: 5
      start_period: 20s
```

MySQL's first boot is slower than Postgres because it initializes the data directory, so I give it a longer `start_period`. Note there's no space after `-p` — that's mysql's quirk for passing the password inline, and it'll bite you if you add one.

## The killer feature: depends_on with a condition

Here's the part that actually fixes the race, and it's easy to miss because plain `depends_on` looks like it should already do this. It doesn't. The short-list form only controls **start order** — Docker starts `db` before `api`, then immediately starts `api` without waiting for `db` to be usable. That's the trap I opened with.

The long form takes a condition:

```yaml
services:
  db:
    image: postgres:16
    environment:
      POSTGRES_USER: app
      POSTGRES_PASSWORD: secret
      POSTGRES_DB: app
    healthcheck:
      test: ["CMD", "pg_isready", "-U", "app", "-d", "app"]
      interval: 5s
      timeout: 3s
      retries: 5
      start_period: 10s

  api:
    build: .
    depends_on:
      db:
        condition: service_healthy
    ports:
      - "8080:8080"
```

`condition: service_healthy` tells Compose: do not start `api` until `db`'s healthcheck reports healthy. Now the connection-refused race is gone by construction — the app literally cannot start before the database answers `pg_isready`. This is the single highest-value line in the whole setup.

The other conditions worth knowing:

- **`service_started`** — the old default. Waits for start, not health.
- **`service_healthy`** — waits for a passing healthcheck. Requires the dependency to actually define one; if `db` has no healthcheck, Compose errors out instead of waiting forever.
- **`service_completed_successfully`** — waits for the dependency to exit with code 0. This is how you gate an app behind a one-shot migration container.

That last one is underused. A migration job that runs and exits, with the app waiting on its clean exit, gives you ordered startup without the app ever racing an unmigrated schema.

## An app healthcheck over HTTP

Databases have purpose-built probes. Your own app usually doesn't, so you hit an endpoint. Expose a cheap route that touches the things that must work — I like one that pings the DB connection and returns 200 — and probe it:

```dockerfile
FROM node:20-alpine

WORKDIR /app
COPY package*.json ./
RUN npm ci --omit=dev
COPY . .

# wget is already in alpine's busybox; -q --spider = no output, no download
HEALTHCHECK --interval=10s --timeout=3s --retries=3 --start-period=30s \
  CMD wget -q --spider http://localhost:8080/health || exit 1

EXPOSE 8080
CMD ["node", "server.js"]
```

Two things to notice. The `HEALTHCHECK` instruction uses `--flag=value` syntax rather than YAML keys, but the parameters are identical. And the check runs **inside** the container, so the URL is `localhost` from the container's own point of view — not the host, not the service name.

`wget --spider` just asks "does this URL respond" without downloading the body. The `|| exit 1` makes the failure explicit; busybox wget already exits non-zero on an HTTP error, but being explicit costs nothing and documents intent. If you'd rather use curl, it's `curl -f http://localhost:8080/health || exit 1`, where `-f` makes curl return non-zero on 4xx/5xx responses. Without `-f`, curl happily returns 0 for a 500 page, and your unhealthy app reports healthy.

## start_period is for slow boots, not slow servers

The failure mode `start_period` prevents is subtle. Say your app takes 25 seconds to boot — migrations, cache warm, whatever. Your healthcheck runs every 10 seconds with 3 retries. Without a start period, the check fires at 10s (fail), 20s (fail), 30s (fail) — three failures, container marked `unhealthy`, and if you're using `service_healthy` upstream, everything jams. The app was going to be fine at 25 seconds. The healthcheck killed it at 30 out of impatience.

Set `start_period: 40s` and those early failures don't count. The container sits in the `starting` state, failures are ignored, and the first success flips it straight to `healthy` — you don't pay the full 40 seconds if it comes up in 25. It's a ceiling on the grace window, not a fixed delay.

Rule of thumb I use: set `start_period` to comfortably above your worst-case cold boot, and keep `interval` and `retries` tuned for steady-state detection once you're running. The two jobs are different. Startup is about patience; steady-state is about noticing a real failure quickly without flapping on one blip.

## The gotcha that gets everyone: curl isn't in the image

You write `CMD curl -f http://localhost:8080/health`, it works on your machine, and in the slim production image every check fails with `curl: not found` — so the container is permanently `unhealthy` and `service_healthy` deadlocks the whole stack. The check ran; the binary it needed was never installed.

Slim and Alpine base images ship almost nothing. `curl` is not in `alpine`, `debian:*-slim`, or `distroless`. Three ways out:

1. **Use what's there.** Alpine's busybox includes `wget`, so probe with `wget -q --spider` and install nothing. This is my default.
2. **Install the binary** if you truly need curl: `RUN apk add --no-cache curl` on Alpine, `apt-get install -y curl` on Debian-slim. Costs you a few MB.
3. **Ship a tiny healthcheck binary.** Distroless has no shell at all, so options 1 and 2 don't apply — people compile a ~2MB static Go binary that pings the endpoint and use it as the check. Overkill for most, necessary for distroless.

The reason this is nasty is that a broken healthcheck and a broken app look identical from the outside: both show `unhealthy`. Always run `docker inspect --format '{{json .State.Health}}' <container>` when a check misbehaves — it prints the last few probe outputs, and "curl: not found" versus "connection refused" tells you instantly whether it's the check or the app.

## How this differs from Kubernetes probes

If you've done Kubernetes, Compose healthchecks feel familiar but flatter, and conflating them causes bad assumptions. Kubernetes splits the concern into three probes:

| Concern | Kubernetes | Compose |
|---|---|---|
| Is it alive? Restart if not | Liveness probe | (No restart on unhealthy by default) |
| Is it ready for traffic? | Readiness probe | Healthcheck + `service_healthy` |
| Has slow startup finished? | Startup probe | `start_period` |

The big one: a failing Compose healthcheck does **not** restart your container by default. It just marks it `unhealthy`. Kubernetes' liveness probe actively kills and restarts a failing pod; Compose's healthcheck only reports. If you want restart-on-failure locally, you combine `restart: unless-stopped` with your own logic, or reach for an orchestrator — Compose isn't one.

So Compose's healthcheck is closest to a Kubernetes **readiness** probe used as a startup gate: it decides when a dependent service may start, not whether to reap an unhealthy one. Bring that expectation and you won't be surprised when your "unhealthy" container keeps serving traffic indefinitely.

## FAQ

**Why does my container stay in the "starting" state forever?**
It never got a single passing check, so it never left `start_period`. Usually the check command itself is broken — wrong binary (the curl-in-slim trap), wrong port, or wrong path. Run `docker inspect --format '{{json .State.Health}}' <container>` and read the last probe's actual output.

**Does depends_on with service_healthy work in production, or only locally?**
It works wherever Compose runs, including Swarm. It has no meaning under Kubernetes — K8s handles ordering with init containers, readiness gates, and the probes above, not `depends_on`. The healthcheck baked into your image still carries over; the `depends_on` wiring does not.

**Can I override or disable a healthcheck a base image already defined?**
Yes. In Compose, `test: ["CMD-SHELL", "your command"]` replaces the image's check, and `test: ["NONE"]` disables it entirely. Handy when a base image ships an aggressive check that fights your slower startup.

**What's the difference between CMD and CMD-SHELL in the test?**
`CMD` runs the command directly with no shell, so no pipes, `&&`, or `$VAR` expansion. `CMD-SHELL` wraps it in `/bin/sh -c`, giving you shell features. Use `CMD-SHELL` the moment you need `||`, a pipe, or an env var in the check — and remember distroless has no shell for `CMD-SHELL` to use.

## Wrapping up

The mental shift is small but it fixes a whole class of "works on my machine" startup races: stop trusting that a running container is a ready one, and make readiness something Docker can measure. Give each service a healthcheck that answers a real question, gate dependents with `condition: service_healthy`, and give slow starters a `start_period` so patience during boot doesn't get confused with failure. Then go check your slim images actually contain whatever binary your check calls — that's the one that'll still surprise you at 2am.
