---
name: "Graceful Shutdown in Node.js Services"
slug: nodejs-graceful-shutdown
short_description: "How to close a Node.js HTTP service without dropping in-flight requests: SIGTERM, server.close, draining, DB pools, and a forced-exit timeout."
language: en
published_at: 2027-04-21 09:00:00
is_published: true
tags: [nodejs, devops, express, docker]
---

The first time I noticed the problem, it was a stream of 502s in the load balancer logs during a deploy. Nothing was broken. The new pods were healthy, the old ones were gone, and yet for about eight seconds every rollout leaked a handful of failed requests. The service was being killed mid-request because it never bothered to shut down properly. It just died the instant the orchestrator told it to.

That's the gap this article closes. When Docker or Kubernetes stops a container, it doesn't yank the power cord — it sends a signal and gives you a few seconds to behave. A Node process that ignores that window drops connections. A process that handles it finishes what it started and exits clean. Below is exactly how to do the second thing, with runnable `http`/Express code you can paste into a service today.

## What actually happens when a container stops

Kubernetes (and plain `docker stop`) sends your process a `SIGTERM` first. Then it waits — by default 30 seconds in Kubernetes (`terminationGracePeriodSeconds`), 10 seconds for `docker stop`. If the process is still alive when that timer expires, it gets `SIGKILL`, which cannot be caught, blocked, or cleaned up after. Game over, mid-flight requests and all.

So the deal is simple: **`SIGTERM` is your notice, and the grace period is your budget.** Everything about graceful shutdown is about doing the right things inside that budget and then exiting before someone pulls the plug for you.

One detail that bites people: Node does not exit on `SIGTERM` by default when you have a listener attached, but it *does* exit on `SIGINT` (Ctrl+C) only because there's no handler. The moment you attach a handler to either signal, you own the shutdown. If your handler forgets to call `process.exit()` or forgets to close the server, the process just hangs there until `SIGKILL`.

## The naive version, and why it's not enough

Here's what most people write first:

```js
const http = require('http');

const server = http.createServer((req, res) => {
  res.end('ok');
});

server.listen(3000);

process.on('SIGTERM', () => {
  server.close();
  process.exit(0);
});
```

This looks reasonable and is subtly wrong in two ways.

`server.close()` is **asynchronous**. It stops the server from accepting new connections immediately, but it does not resolve until every existing connection has finished. Calling `process.exit(0)` on the very next line kills the process before any in-flight request completes — which is the exact bug you were trying to fix. The `exit` runs synchronously; the close callback never gets a chance.

The fix is to move `process.exit()` into the `close` callback, or better, drop the explicit exit entirely and let Node exit on its own once the event loop is empty. But even then there's a second trap waiting, and it's the one that wasted an afternoon of mine: keep-alive.

## server.close does not close idle keep-alive sockets

`server.close()` waits for connections to end. A browser or an internal HTTP client using **keep-alive** holds its socket open on purpose, ready to reuse it. From the server's point of view that idle socket is still "a connection," so `server.close()` sits there waiting for it to close — which it won't, not until the client's keep-alive timeout fires. That can be many seconds, well past your grace period.

The symptom is nasty because it's intermittent: shutdown works fine in local testing (curl closes its socket right away) and then hangs in production behind a load balancer that pools connections. Your callback never runs, the timer runs out, `SIGKILL` arrives, and you're back to dropped requests.

Node gives you the tools to deal with this as of v18.2, but you have to use them. Here's the version I actually ship:

```js
const http = require('http');

const server = http.createServer((req, res) => {
  // pretend this does real work
  setTimeout(() => res.end('ok'), 50);
});

// Track live sockets so we can force-close idle ones on shutdown.
const sockets = new Set();
server.on('connection', (socket) => {
  sockets.add(socket);
  socket.on('close', () => sockets.delete(socket));
});

server.listen(3000, () => {
  console.log('listening on :3000');
});

let shuttingDown = false;

async function shutdown(signal) {
  if (shuttingDown) return;      // ignore a second SIGTERM
  shuttingDown = true;
  console.log(`${signal} received, shutting down`);

  // 1. Stop accepting new connections.
  server.close((err) => {
    if (err) {
      console.error('error during server.close', err);
      process.exit(1);
    }
    console.log('server closed, exiting');
    process.exit(0);
  });

  // 2. Nudge idle keep-alive sockets to close so server.close can resolve.
  //    Sockets mid-request stay open until the response finishes.
  for (const socket of sockets) {
    if (socket.writableEnded || socket._httpMessage == null) {
      socket.end();
    }
  }

  // 3. Hard deadline: if draining takes too long, exit anyway.
  setTimeout(() => {
    console.error('drain timed out, forcing exit');
    process.exit(1);
  }, 10_000).unref();
}

process.on('SIGTERM', () => shutdown('SIGTERM'));
process.on('SIGINT', () => shutdown('SIGINT'));
```

A few things worth calling out. The `sockets` set lets us reach the idle keep-alive connections and end them by hand — that's what unblocks `server.close()`. We only `end()` sockets that aren't in the middle of a response, so real requests still get to finish. The `shuttingDown` guard matters because orchestrators sometimes send `SIGTERM` more than once; without the guard the second one restarts your whole teardown.

Since Node 18.2 there's also a cleaner built-in: `server.closeIdleConnections()` closes idle keep-alive sockets, and `server.closeAllConnections()` closes everything immediately. If you're on a recent Node, call `server.closeIdleConnections()` right after `server.close()` and skip the manual socket tracking. I still show the manual version because it works on older runtimes and makes the mechanism obvious.

The `setTimeout(...).unref()` is your safety net. `.unref()` means that timer alone won't keep the process alive — if everything drains cleanly and the event loop empties, the process exits and the timer is irrelevant. It only fires if something is genuinely stuck.

## Ordering matters: readiness probe, then drain

There's a subtlety that pure Node examples skip because it lives in the orchestrator, not the code. When Kubernetes decides to terminate a pod, it does two things roughly at the same time: it sends `SIGTERM`, and it removes the pod from the Service endpoints. "Roughly at the same time" is the problem — endpoint removal propagates through kube-proxy and your ingress asynchronously, so for a short window the load balancer is *still routing new traffic to a pod that already got its SIGTERM.*

If you close the HTTP server the instant `SIGTERM` lands, those in-flight-to-arrive requests hit a closed port and 502. The trick is to **fail your readiness probe first, wait a beat, then start draining.** Flipping readiness to unhealthy is the signal that pulls you out of the load balancer rotation. Once traffic has actually stopped arriving, you close the server.

```js
let ready = true;

app.get('/readyz', (req, res) => {
  res.status(ready ? 200 : 503).send(ready ? 'ready' : 'draining');
});

async function shutdown(signal) {
  console.log(`${signal} received`);

  // Flip readiness FIRST so the load balancer stops sending new requests.
  ready = false;

  // Give kube-proxy / the ingress time to notice and reroute.
  await new Promise((r) => setTimeout(r, 5_000));

  // Now it's safe to stop accepting connections and drain.
  await drainAndClose();
}
```

That five-second sleep looks like a hack, and it kind of is, but it's the accepted pattern — it just has to be shorter than your `terminationGracePeriodSeconds` with room to spare for the actual draining. Set the grace period to something like 30s and you've got plenty of margin. The alternative, a `preStop` lifecycle hook with a `sleep`, does the same thing at the pod spec level; pick one, not both.

## Closing the rest: DB pools, queue consumers, timers

The HTTP server is only half the shutdown. A real service holds a database pool, maybe a Redis client, maybe a message-queue consumer that's actively pulling jobs. If you `process.exit()` without closing these, you leak connections on the database side and, worse, you can lose or double-process a job that was half-done.

The rule I follow: **drain HTTP first, then close the things HTTP depended on.** You don't want to close the database pool while a request is still trying to run a query — that turns a clean shutdown into a 500. Order is server → then dependencies.

```js
const { Pool } = require('pg');
const pool = new Pool();

async function drainAndClose() {
  // 1. Stop accepting connections, wait for in-flight requests.
  await new Promise((resolve, reject) => {
    server.close((err) => (err ? reject(err) : resolve()));
    server.closeIdleConnections?.(); // Node >= 18.2
  });
  console.log('http drained');

  // 2. Now nothing is using the DB — safe to close the pool.
  await pool.end();
  console.log('pg pool closed');
}
```

For a queue consumer the same logic applies but the "in-flight" work isn't an HTTP request, it's a job. Tell the consumer to stop pulling new messages, wait for the current handler to finish (or nack it so another worker picks it up), then disconnect. Most libraries expose this. With BullMQ it's `await worker.close()`, which stops fetching and waits for active jobs; with a raw AMQP channel you `channel.cancel(consumerTag)` to stop delivery, let handlers drain, then close the connection. The shape is always: stop intake → finish or requeue current work → disconnect.

## The bug that hides in every one of these: not awaiting cleanup

If there's one mistake I've seen more than any other, it's this:

```js
process.on('SIGTERM', () => {
  server.close();
  pool.end();       // returns a promise nobody waits for
  process.exit(0);  // runs immediately, kills everything
});
```

`pool.end()` returns a promise. The handler doesn't `await` it, doesn't chain a `.then()`, and then calls `process.exit(0)` synchronously on the next line. The process is dead before the pool has flushed anything. This is the async version of the very first bug in this article, and it's easy to miss because the code *looks* like it's doing cleanup.

Make your shutdown function `async`, `await` every teardown step, and only exit after they've all resolved:

```js
async function shutdown(signal) {
  console.log(`${signal} received`);
  ready = false;
  await new Promise((r) => setTimeout(r, 5_000));
  try {
    await drainAndClose();   // http + pool, both awaited
    console.log('clean shutdown');
    process.exit(0);
  } catch (err) {
    console.error('shutdown failed', err);
    process.exit(1);
  } finally {
    // absolute backstop in case something above never settles
    setTimeout(() => process.exit(1), 10_000).unref();
  }
}

process.on('SIGTERM', () => shutdown('SIGTERM'));
process.on('SIGINT', () => shutdown('SIGINT'));
```

## A few traps worth remembering

- **PID 1 in Docker.** If your container runs `node server.js` as PID 1, the kernel treats PID 1 specially and does not apply default signal handlers — but more importantly, if you shell out (`npm start` → `sh` → `node`), the shell is PID 1 and often doesn't forward `SIGTERM` to Node at all. Your handler never runs. Fix it with `exec` in your start script, or run with `--init` / a tiny init like `tini`.
- **`npm start` swallows signals.** Same root cause. Don't wrap `node` in `npm` inside your `CMD`; call `node` directly so it's the process that receives the signal.
- **The grace period is a hard cap, not a suggestion.** Your total shutdown time — readiness delay plus drain plus dependency close — must fit inside `terminationGracePeriodSeconds`. If it doesn't, `SIGKILL` truncates you and the whole exercise was pointless. Budget it.
- **Long-lived requests need their own answer.** WebSockets, SSE, and streaming downloads won't "finish" on their own within a grace window. Decide deliberately: close them with a reconnect-friendly code, or let the timeout force them. Don't just hope they drain.

## FAQ

### What's the difference between SIGTERM and SIGKILL?

`SIGTERM` is a polite request to stop that your process can catch and handle — it's what `docker stop` and Kubernetes send first. `SIGKILL` (signal 9) cannot be caught, handled, or ignored; the kernel terminates the process immediately with no cleanup. Kubernetes sends `SIGKILL` only after the grace period expires. All your shutdown logic hangs off `SIGTERM`; you never get a say once `SIGKILL` arrives.

### Why does server.close() never call its callback?

Almost always because an idle keep-alive connection is still open. `server.close()` stops accepting new connections but waits for existing ones to end, and an idle keep-alive socket doesn't end until its client-side timeout fires. Force idle sockets closed with `server.closeIdleConnections()` (Node 18.2+) or by tracking sockets yourself and calling `socket.end()` on the idle ones.

### Do I need this if I'm behind a load balancer with health checks?

Yes, and arguably more so. The load balancer keeps connections pooled to your service, which is exactly what makes `server.close()` hang, and there's a routing window after `SIGTERM` where new requests still arrive. Failing the readiness probe first and then draining is what closes that window cleanly.

### Where should I put the shutdown timeout?

Set an unref'd timer that force-exits after a few seconds less than your platform's grace period — for a 30s Kubernetes grace period, a 10s internal timeout leaves comfortable margin. Use `.unref()` so it never keeps an otherwise-idle process alive, and treat it as a backstop for stuck drains, not the normal path.

## Wrapping up

Graceful shutdown isn't a feature you add, it's a bug you stop having. The whole thing reduces to a short checklist: catch `SIGTERM` and `SIGINT`, fail readiness so traffic drains away, stop accepting connections, close idle keep-alive sockets so `server.close()` can actually resolve, `await` your database and queue teardown in order, and keep an unref'd timer as the force-exit backstop. Get those right and deploys stop leaking 502s.

Start by adding the signal handlers to your service and watching a rollout in the logs. If you still see connections dropping, the keep-alive socket handling is almost always the missing piece — that's the one nobody remembers until it costs them an afternoon.
