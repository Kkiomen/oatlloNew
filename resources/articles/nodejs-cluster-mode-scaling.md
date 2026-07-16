---
name: "Scaling Node.js with Cluster Mode"
slug: nodejs-cluster-mode-scaling
short_description: "Why a Node process pins to one CPU core, how the cluster module forks workers to use them all, and the sticky-session and state traps that come with it."
language: en
published_at: 2027-04-02 09:00:00
is_published: true
tags: [nodejs, javascript, devops, architecture]
---

I once shipped a Node API to a 16-core box and watched it flatline at roughly 6% CPU under load. The requests were slow, the machine was almost idle, and for a while I blamed the database. The database was fine. The problem was that my perfectly healthy application was politely using one core out of sixteen and ignoring the rest.

That gap between "the server has cores" and "my app uses them" is what this article is about. We'll cover why one Node process sticks to a single core, how the built-in cluster module spreads work across all of them, and the two things that bite people afterwards: session stickiness and in-process state.

## Why one process gets one core

Node runs your JavaScript on a single thread. There's a whole thread pool underneath (libuv handles file I/O, DNS, crypto, and compression there), and the event loop juggles thousands of concurrent connections happily. But *your code* — the route handlers, the JSON parsing, the template rendering, that JWT verification loop — all runs on one thread, and a thread runs on one core at a time.

For I/O-bound work this is usually fine. While one request waits on Postgres, the event loop serves others. The single thread is rarely the bottleneck because it spends most of its time waiting.

It falls apart the moment you do real CPU work per request: hashing passwords, resizing images, parsing large payloads, rendering server-side React. Now the thread is *busy*, not waiting, and every other request queues behind it. Fifteen cores sit idle while one runs hot. You can't event-loop your way out of CPU work — you need more threads actually executing, which on Node means more processes.

## The cluster module: one primary, many workers

Node ships a `cluster` module for exactly this. One **primary** process forks several **worker** processes, each a full copy of your app with its own event loop and its own core. The neat trick is that all workers share the same listening port, so from the outside it's still one server on `:3000`.

Here's the shape of it. Fork one worker per CPU:

```js
import cluster from 'node:cluster';
import http from 'node:http';
import { availableParallelism } from 'node:os';

if (cluster.isPrimary) {
  const cpus = availableParallelism();
  console.log(`Primary ${process.pid} starting ${cpus} workers`);

  for (let i = 0; i < cpus; i++) {
    cluster.fork();
  }

  // A worker died — replace it so capacity doesn't quietly shrink.
  cluster.on('exit', (worker, code, signal) => {
    console.log(`Worker ${worker.process.pid} died (${signal || code}). Restarting.`);
    cluster.fork();
  });
} else {
  http.createServer((req, res) => {
    res.end(`Handled by worker ${process.pid}\n`);
  }).listen(3000);

  console.log(`Worker ${process.pid} listening`);
}
```

Run it and hit `:3000` a bunch of times. The PID in the response changes — different workers are answering. One port, several processes, all cores in play.

Use `availableParallelism()` (added in Node 18.14 / 19) rather than `os.cpus().length`. Inside a container with a CPU limit, `os.cpus()` reports the *host's* core count, so on a 32-core host with a 2-core cgroup limit you'd fork 32 workers fighting over 2 cores. `availableParallelism()` respects the limit.

## How connections actually get distributed

This part trips people up because there are two different mechanisms and they behave differently.

On most platforms, the primary accepts incoming connections and hands them to workers using **round-robin** (`cluster.SCHED_RR`), which is the default everywhere except Windows. The primary decides who gets the next connection, and it spreads them evenly. This is what you want almost always.

The other mode (`SCHED_NONE`) hands the listening socket to every worker and lets the operating system decide. In practice the OS tends to favor whichever worker is ready first, so load piles up unevenly — a few workers do most of the work. Round-robin exists precisely because relying on the kernel gave lumpy distribution.

You can force it, though the default is already round-robin on Linux and macOS:

```js
import cluster from 'node:cluster';

cluster.schedulingPolicy = cluster.SCHED_RR; // the default off Windows
```

The important mental model: **round-robin distributes connections, not requests**. With HTTP keep-alive, a client holds one TCP connection open and sends many requests down it, and all of those land on the *same* worker. So a small number of chatty clients can still lopside your load even with perfect round-robin. It's rarely a real problem at scale, but it explains why your workers aren't at identical CPU.

## The sticky-session problem

Round-robin sending each connection to a random worker is fine for stateless HTTP. It's a disaster for anything that assumes the same client keeps hitting the same process.

WebSockets are the classic case. A Socket.IO connection starts with an HTTP long-poll handshake before it upgrades to a WebSocket. Those handshake requests are separate HTTP requests, and round-robin cheerfully scatters them across different workers. Worker 3 starts the handshake, worker 7 gets the next step, worker 7 has never heard of this session, and the client gets a `400` and reconnect loops forever.

The fix is **sticky sessions**: route every request from a given client to the same worker, usually by hashing the source IP. You need this at the layer that distributes connections. Two common places to do it:

- **In the cluster primary**, using a package like `@socket.io/sticky` that hashes the connection and forwards it to a fixed worker.
- **In your reverse proxy**, using `ip_hash` in nginx or a hash-based load balancer, so the same client always reaches the same backend process.

An nginx sticky config across four Node processes looks like this:

```nginx
upstream node_app {
  ip_hash;                 # same client IP -> same backend, every time
  server 127.0.0.1:3001;
  server 127.0.0.1:3002;
  server 127.0.0.1:3003;
  server 127.0.0.1:3004;
}

server {
  listen 80;
  location / {
    proxy_pass http://node_app;
    proxy_http_version 1.1;
    proxy_set_header Upgrade $http_upgrade;   # required for WebSocket upgrade
    proxy_set_header Connection "upgrade";
    proxy_set_header Host $host;
  }
}
```

Sticky solves routing, but it doesn't solve the deeper issue — and if you stop at sticky you've just built a different bug.

## Your state can't live in one process anymore

The moment you have more than one worker, any state kept in a JavaScript variable exists in *one* process and is invisible to the others. This is the single biggest source of "works on my laptop, breaks in production" with clustering, because on your laptop you probably ran one process.

Things that silently break when you go from one worker to many:

- **In-memory sessions.** A user logs in, their session lands in worker 2's memory, their next request hits worker 5, and they're logged out. Feels random. It's just load balancing.
- **In-process caches.** A `Map` you use as a cache is now N separate caches with N different views of the truth.
- **Rate limiting by counter.** A per-process counter allowing 100 requests actually allows `100 × workers`, because each process counts on its own.
- **Socket.IO rooms and broadcasts.** `io.emit()` reaches only the clients connected to *that* worker. Users on other workers never get the message.

The rule is blunt: **shared state has to live outside the process.** Redis is the usual answer — sessions, cache, rate-limit counters, and Socket.IO's cross-worker message bus (the Redis adapter) all move there. It's not there for speed here; it's there because it's the one place all workers can see.

```js
// Sessions in Redis instead of process memory, so any worker can read them.
import session from 'express-session';
import { RedisStore } from 'connect-redis';
import { createClient } from 'redis';

const redisClient = createClient({ url: process.env.REDIS_URL });
await redisClient.connect();

app.use(session({
  store: new RedisStore({ client: redisClient }),
  secret: process.env.SESSION_SECRET,
  resave: false,
  saveUninitialized: false,
}));
```

Do this and something nice falls out: sticky sessions matter far less for plain HTTP. If every worker can read the session from Redis, it no longer matters which worker answers. You still need stickiness for the WebSocket *transport handshake*, but your application logic stops caring where a request lands. That's the goal — treat workers as interchangeable.

## Just use PM2 in real life

Hand-rolling `cluster.fork()` is great for understanding what's happening. For production I reach for **PM2**, which wraps all of this — spawning workers, restarting dead ones, zero-downtime reloads — behind one command:

```bash
# Fork one worker per CPU core, no code changes needed
pm2 start server.js -i max --name api

# Reload with zero downtime after a deploy (restarts workers one at a time)
pm2 reload api
```

The `-i max` flag forks a worker per core using the cluster module under the hood, so your `server.js` doesn't need the `isPrimary` boilerplate at all — PM2 is the primary. `pm2 reload` cycles workers one by one so there's always something listening, which plain `cluster` doesn't give you for free.

PM2 doesn't fix the state problem. It'll happily run 8 workers with your sessions stuck in memory and hand you the same logged-out-users bug, just more reliably. Cluster mode and external state are a package deal regardless of the tool.

## When to skip cluster and run separate processes

Cluster is convenient because of the shared port. But sometimes you want fully independent processes behind a reverse proxy instead:

- **You're already running containers.** Kubernetes or Docker Swarm scaling one-process-per-container, with the orchestrator handling health checks and restarts, is cleaner than nesting a cluster inside each container. One core per container, scale the container count.
- **You want per-process isolation.** Separate processes behind nginx can be deployed, restarted, and monitored independently.
- **You need to scale across machines.** Cluster is single-host only — it forks on the box it runs on. The moment you outgrow one server, you're doing multi-process-behind-a-load-balancer anyway, so the reverse-proxy model is where you'll end up.

Honestly, the two approaches converge. Whether it's the cluster primary or nginx doing the distributing, you still need sticky sessions for WebSockets and external state for everything shared. The mechanics move; the constraints don't.

## Don't confuse this with worker_threads

People reach for `worker_threads` thinking it's the scaling tool. Different job.

**Cluster** gives you multiple *processes*, each with its own memory, sharing a network port — for handling more concurrent requests across cores. **worker_threads** gives you multiple *threads inside one process*, sharing memory via `SharedArrayBuffer` — for offloading a single CPU-heavy task off the event loop so it doesn't block requests.

Scaling an HTTP server to use all cores: cluster (or PM2). Doing a heavy CSV parse or image transform without freezing the event loop for other requests: worker_threads. They solve adjacent problems and you sometimes use both — cluster to spread requests, a worker thread inside each worker to keep a hot computation from blocking that worker's event loop.

## FAQ

### How many workers should I run?

Start with one per CPU core (`availableParallelism()`). More workers than cores just adds context-switching overhead without more parallelism. If your app is I/O-bound rather than CPU-bound, you may not need clustering at all — a single process might already saturate your database or downstream services long before it saturates a core.

### Do I still need a reverse proxy in front of a Node cluster?

Usually yes, but for different reasons: TLS termination, serving static files, request buffering against slow clients, and rate limiting at the edge. The cluster handles spreading load across cores on one host; nginx handles the things Node shouldn't be doing itself and, once you have more than one machine, the load balancing between them.

### Why do my Socket.IO broadcasts only reach some users after I added clustering?

Because `io.emit()` only reaches clients connected to the worker that ran it, and your users are spread across workers. Install the Socket.IO Redis adapter so a broadcast on one worker is published to all of them, and make sure your load balancer uses sticky sessions for the handshake.

### Does cluster mode help a CPU-light API that's just waiting on a database?

Less than you'd hope. If each request is mostly waiting on I/O, one process already handles high concurrency, and adding workers mainly buys you resilience (a crashed worker doesn't take everything down) rather than throughput. Profile first — confirm you're actually CPU-bound before clustering.

## Where to start

If your Node server is pinned at one core of many, the path is short: fork a worker per core (via PM2's `-i max`, don't hand-roll it), move sessions and any shared cache into Redis, and add sticky sessions only where you have long-lived connections like WebSockets. Do the state work first. Clustering without external state doesn't scale your app — it just distributes your bugs across more processes.
