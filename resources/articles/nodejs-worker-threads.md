---
name: "Node.js Worker Threads for CPU-Bound Work"
slug: nodejs-worker-threads
short_description: "How to move CPU-heavy work off the event loop with worker_threads, transfer data without copying, and build a reusable worker pool."
language: en
published_at: 2027-03-15 09:00:00
is_published: true
tags: [nodejs, javascript, performance, concurrency]
---

The first time this bit me, it was a PDF export endpoint. Fine in staging, then production traffic hit and the whole API went unresponsive for two or three seconds at a time — not the export route, *every* route. Health checks started failing. The culprit wasn't a slow database or a leaking connection pool. It was a single `for` loop rasterizing pages, running on the one thread that also serves every other request. That's the trap nobody warns you about when they sell Node on "non-blocking I/O": the I/O is non-blocking, but your own CPU work isn't.

This is a walk through moving that kind of work off the main thread with `worker_threads` — how the module actually works, how to pass data without paying a copy tax, how to build a small pool you can reuse, and the cases where a worker is the wrong answer entirely.

## Why one busy loop stalls everything

Node runs your JavaScript on a single thread with a single event loop. Async I/O feels concurrent because the *waiting* happens elsewhere — the OS, libuv's thread pool, a socket — and your callback gets queued when the data is ready. While it waits, the loop is free to run other work.

CPU work has no waiting. A JSON parse over a 40 MB payload, a bcrypt round, image resizing, a regex backtracking on hostile input — all of it runs *inside* the loop, start to finish, holding the thread. Nothing else advances until it returns. No incoming request is accepted, no timer fires, no promise resolves.

You can see it in ten lines:

```js
const http = require('http');

function blockFor(ms) {
  const end = Date.now() + ms;
  while (Date.now() < end) {} // busy loop, no yielding
}

http.createServer((req, res) => {
  if (req.url === '/heavy') blockFor(3000);
  res.end('done\n');
}).listen(3000);
```

Hit `/heavy` in one tab, then hammer `/` in another. Every `/` request is frozen for the full three seconds. One user's heavy call became everyone's outage. That is the exact failure mode of my PDF endpoint, minus the PDF.

The fix isn't "make the loop faster." It's to stop running that work on the loop at all.

## What worker_threads gives you

`worker_threads` is a core module (stable since Node 12, no flag needed) that spins up real OS threads, each with its own V8 isolate and its own event loop. A worker runs a separate script. It shares *no* JavaScript memory with the parent by default — communication goes over a message channel, and values are copied via the structured clone algorithm unless you explicitly transfer or share them.

That last part is the mental model that matters. Workers are not free-threaded shared-memory concurrency like Java or Go. They're closer to isolated processes that happen to be cheaper to start and can, when you opt in, share a slab of raw bytes. If you come in expecting to mutate a shared object across threads, you'll fight the model the whole way.

Here's the minimal shape. One file, using `isMainThread` to branch:

```js
const {
  Worker, isMainThread, parentPort, workerData,
} = require('worker_threads');

if (isMainThread) {
  // Parent side
  const worker = new Worker(__filename, {
    workerData: { start: 1, end: 100_000_000 },
  });
  worker.on('message', (sum) => console.log('sum =', sum));
  worker.on('error', (err) => console.error(err));
  worker.on('exit', (code) => {
    if (code !== 0) console.error('worker stopped, code', code);
  });
} else {
  // Worker side — runs on its own thread
  const { start, end } = workerData;
  let sum = 0;
  for (let i = start; i <= end; i++) sum += i;
  parentPort.postMessage(sum);
}
```

`workerData` is the payload handed to the worker at construction time — read-only from the worker's view, and cloned, not shared. `parentPort` is the worker's end of the channel back to the parent. `postMessage` sends; the parent's `message` event receives. Run this and the main thread stays responsive while the loop grinds away on another core.

Two things people forget and then debug for an hour. Always attach an `error` handler — an unhandled throw in a worker with no listener will crash the process. And always handle `exit` with a non-zero code check, because a worker that dies mid-task otherwise just goes silent and your `message` never arrives.

## Passing data: clone, transfer, or share

How you get data in and out of a worker decides whether threading actually helps. Move a 200 MB buffer the naive way and the structured-clone copy can cost more than the computation you offloaded.

**Structured clone (the default).** Whatever you `postMessage` gets deep-copied into the receiving thread. It handles most things — objects, arrays, `Map`, `Set`, typed arrays, `Date` — but not functions, class instances (you lose the prototype), or DOM-style nodes. For small messages this is fine and you shouldn't think about it. For large binary payloads, the copy is the bottleneck.

**Transfer list (move, don't copy).** For an `ArrayBuffer`, you can *transfer* ownership instead of cloning. The bytes aren't copied — the buffer is detached from the sender (accessing it afterward throws) and handed to the receiver. Near-zero cost regardless of size.

```js
const buf = new Uint8Array(50_000_000); // 50 MB
// ... fill buf ...

// Second arg is the transfer list. Pass the underlying ArrayBuffer.
worker.postMessage(buf, [buf.buffer]);

// After this line, buf is detached in the parent:
console.log(buf.byteLength); // 0
```

Transfer is the right default when the sender is done with the data. You hand it over, you stop touching it. `MessagePort` instances are transferable too, which is how you wire workers to talk directly to each other.

**SharedArrayBuffer (genuinely shared memory).** When both threads need to read and write the *same* bytes at once, use a `SharedArrayBuffer`. No copy, no detach — both sides see one region of memory. This is the only way to get true shared state, and it's the one that will hurt you if you're careless, because now you have data races.

```js
// Parent
const shared = new SharedArrayBuffer(4); // 4 bytes = one Int32
const view = new Int32Array(shared);
const worker = new Worker('./inc.js', { workerData: shared });

// inc.js (worker)
const { workerData } = require('worker_threads');
const view = new Int32Array(workerData);
Atomics.add(view, 0, 1); // atomic increment, race-safe
Atomics.notify(view, 0);
```

Use the `Atomics` API for anything more than a single reader and single writer. `Atomics.add`, `Atomics.compareExchange`, `Atomics.wait`, and `Atomics.notify` give you the memory ordering guarantees a plain `view[0]++` does not. If you find yourself reaching for shared memory and locks, stop and ask whether the problem really needs it — most CPU-offload jobs are "here's input, give me output," and a transfer list is simpler and safe.

Quick guide:

| Situation | Use |
| --- | --- |
| Small structured message | Default clone (just `postMessage`) |
| Large buffer, sender is done with it | `postMessage(data, [data.buffer])` |
| Both threads read/write same bytes live | `SharedArrayBuffer` + `Atomics` |
| Two workers need a direct channel | Transfer a `MessagePort` |

## Don't spawn a worker per task — pool them

Starting a worker isn't free. You're booting a fresh V8 isolate, loading and compiling your worker script, and setting up the message channel — call it tens of milliseconds and a few megabytes each. Spawn one per incoming request under load and you've traded a blocked loop for thread-creation thrash and unbounded memory.

The pattern that scales is a **fixed pool**: create N long-lived workers once (N usually near your core count), then feed tasks to whichever worker is idle, queueing when all are busy. Here's a small, complete pool with no dependencies.

```js
// pool.js
const { Worker } = require('worker_threads');
const os = require('os');

class WorkerPool {
  constructor(workerFile, size = os.cpus().length) {
    this.workerFile = workerFile;
    this.idle = [];
    this.queue = [];
    for (let i = 0; i < size; i++) this.#spawn();
  }

  #spawn() {
    const worker = new Worker(this.workerFile);
    worker.busy = false;
    worker.on('error', (err) => {
      // A crashed worker rejects its current job, then gets replaced.
      if (worker.reject) worker.reject(err);
      this.#replace(worker);
    });
    this.idle.push(worker);
  }

  #replace(dead) {
    this.idle = this.idle.filter((w) => w !== dead);
    dead.terminate();
    this.#spawn();
    this.#drain();
  }

  run(task) {
    return new Promise((resolve, reject) => {
      this.queue.push({ task, resolve, reject });
      this.#drain();
    });
  }

  #drain() {
    if (this.queue.length === 0 || this.idle.length === 0) return;
    const worker = this.idle.pop();
    const { task, resolve, reject } = this.queue.shift();
    worker.busy = true;
    worker.reject = reject;

    const onMessage = (result) => {
      cleanup();
      resolve(result);
      worker.busy = false;
      worker.reject = null;
      this.idle.push(worker);
      this.#drain();
    };
    const cleanup = () => worker.off('message', onMessage);

    worker.once('message', onMessage);
    worker.postMessage(task);
  }

  async destroy() {
    await Promise.all(this.idle.map((w) => w.terminate()));
  }
}

module.exports = { WorkerPool };
```

The worker script just answers messages in a loop — it lives for the whole process, so the startup cost is paid once:

```js
// task-worker.js
const { parentPort } = require('worker_threads');

parentPort.on('message', (task) => {
  // Whatever your CPU-bound job is:
  const result = hashPassword(task.input);
  parentPort.postMessage(result);
});
```

And using it from your server:

```js
const { WorkerPool } = require('./pool');
const pool = new WorkerPool('./task-worker.js');

app.post('/hash', async (req, res) => {
  const result = await pool.run({ input: req.body.password });
  res.json({ result });
});
```

Now the event loop is doing what it's good at — accepting requests, awaiting promises — while the pool chews through CPU work on other cores. The queue gives you natural backpressure: a spike doesn't spawn a thousand threads, it lines up behind N of them.

If you'd rather not maintain this yourself, `piscina` is a well-worn library that does the same thing with more polish (task cancellation, timeouts, per-task transfer lists). I'll hand-roll the pool when it's one job type and reach for `piscina` the moment I need more than one.

## When a worker is the wrong tool

Threads aren't the answer to every "this is slow" problem, and reaching for them reflexively adds real complexity.

**If the work is I/O, you don't need a worker at all.** Waiting on a database, an HTTP call, or a file read already doesn't block the loop. Wrapping an `await fetch()` in a worker buys you nothing but overhead and a copy. Workers are for CPU, full stop.

**If the job is heavy, long-running, or needs isolation, use a separate process.** `child_process` or `cluster` gives you a fresh V8 heap and memory that can't take your API down when it balloons. A worker shares the parent process's memory limit and lifecycle; a runaway allocation in a worker can still OOM the whole thing. For a 5-minute video transcode, I want a child process (or better, an external `ffmpeg`) I can kill independently, not a worker holding a slot in my pool.

**If the work can be deferred, use a queue.** The moment work doesn't need to finish inside the request — sending emails, generating reports, processing an upload — a job queue (BullMQ on Redis, or your platform's equivalent) beats an in-process pool. You get retries, persistence across restarts, backpressure, and the ability to scale workers on separate machines. That PDF export I opened with? It ended up on a queue, not a worker pool. The user got a "we'll email you the file" response and the API never blocked again. Worker threads were the right fix for the *synchronous* version; the better fix was making it not synchronous.

Rule of thumb: worker threads for CPU work that must return within the request and shares data cheaply; child processes for heavy or untrusted work needing isolation; a queue for anything that can happen later.

## FAQ

**How many workers should I create?**
Start at the number of physical cores (`os.cpus().length` as a proxy) for pure CPU work — more threads than cores just means they fight over the same cores with context-switching overhead. If your tasks mix a little I/O in, you can go slightly above core count. Measure under real load rather than guessing; the right number is the one where throughput stops improving.

**Can workers share regular JavaScript objects?**
No. Everything sent over `postMessage` is either cloned or transferred, and neither gives you a live shared object — a transferred buffer leaves the sender, a cloned object is an independent copy. The only genuinely shared memory is a `SharedArrayBuffer`, and that shares raw bytes, not objects. If you need shared structured state, you serialize it into that buffer yourself or coordinate through messages.

**Do worker threads make my code faster automatically?**
Only for CPU-bound work, and only if the work outweighs the cost of moving data across the thread boundary. For small or I/O-bound tasks, the clone and message overhead can make things slower. Profile the single-threaded version first; if the flame graph shows a fat synchronous function hogging the loop, that's a worker candidate. If it shows time spent in `await`, it isn't.

**What happens if a worker throws?**
Its `error` event fires on the parent. With no `error` listener attached, an uncaught exception in the worker crashes the whole process. Always listen for `error` and `exit`, and in a pool, treat a crashed worker as disposable — reject its in-flight task and spawn a replacement, which is exactly what the pool above does.

## Where this leaves you

The single event loop is Node's great strength for I/O and its sharpest edge for computation. `worker_threads` closes that gap without leaving the runtime: real threads for the CPU work, the loop free for everything else. Reach for a pool over one-off workers, transfer or share buffers instead of cloning them when they're large, and keep a clear line in your head between "must finish now" (worker), "heavy and isolated" (child process), and "can wait" (queue).

Next time an endpoint mysteriously drags every other route down with it, open a CPU profile before you touch the database config. If a synchronous function is sitting on the loop, you already know where it belongs.
