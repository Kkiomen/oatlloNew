---
name: "Web Workers in JavaScript: Offloading Heavy Work in the Browser"
slug: web-workers-javascript
short_description: "A practical guide to web workers javascript: run CPU-heavy tasks off the main thread, keep the UI responsive, and avoid the common pitfalls."
language: en
published_at: 2027-02-15 09:00:00
is_published: true
tags: [javascript, performance, web-workers, frontend]
---

The first time I profiled a page that froze for two full seconds while parsing a CSV, I blamed the framework. It wasn't the framework. It was me, doing 40 MB of string work on the same thread that paints the screen. That's the exact problem **web workers** in JavaScript solve: they run your code on a background thread so the main thread stays free to handle clicks, scroll, and rendering.

If you've ever seen a spinner that doesn't spin, or a button that ignores you for a beat, you've felt a blocked main thread. This guide walks through what web workers actually do, when they help, when they don't, and the mistakes that cost me an afternoon so they don't cost you one.

## What a web worker actually is

The browser runs your JavaScript on a single main thread by default. That thread does everything: running your logic, handling events, laying out the page, painting pixels. When one long function hogs it, nothing else can happen. The page is frozen until the function returns.

A web worker is a separate thread with its own JavaScript engine context. You hand it a script, it runs independently, and it talks to your page by passing messages back and forth. No shared variables, no shared DOM, just messages.

Two facts shape everything else about workers:

- **They have no DOM access.** There's no `document`, no `window`, no direct way to touch the page. A worker can't set `element.textContent`. It computes; the main thread renders.
- **They have a separate global scope.** Inside a worker the global object is `self`, not `window`. Your usual globals aren't there.

That isolation is the whole point. Because a worker can't reach into your page state, the browser can safely run it in parallel.

## Your first worker

Start with two files. Here's the main script that lives with your page:

```javascript
// main.js
const worker = new Worker('worker.js');

// Send data into the worker
worker.postMessage({ numbers: [5, 12, 8, 130, 44] });

// Receive results back
worker.onmessage = (event) => {
  console.log('Worker replied:', event.data);
};

// Always handle errors
worker.onerror = (err) => {
  console.error('Worker failed:', err.message);
};
```

And the worker itself:

```javascript
// worker.js
self.onmessage = (event) => {
  const { numbers } = event.data;
  const total = numbers.reduce((sum, n) => sum + n, 0);

  // Send the result back to the main thread
  self.postMessage({ total });
};
```

The mechanics that matter:

- `new Worker('worker.js')` spins up the thread and loads the script. The URL is resolved relative to the page.
- `postMessage()` sends data. It exists on *both* sides. From the page you call `worker.postMessage(...)`; from inside the worker you call `self.postMessage(...)` (or just `postMessage(...)`).
- `onmessage` receives data. The payload arrives on `event.data`.
- `onerror` catches uncaught errors thrown inside the worker. Wire it up before you need it.

Notice there's no callback linking a specific message to its reply. Messages are fire-and-forget events. If you send three requests, you get three `onmessage` events, and it's on you to match them up (a request id in the payload is the usual trick).

## Module workers: the modern default

The classic `new Worker('worker.js')` form runs the worker as a plain script, so you're stuck with `importScripts()` for dependencies. I skip that now. Module workers let you use real `import` statements:

```javascript
// main.js
const worker = new Worker(new URL('./worker.js', import.meta.url), {
  type: 'module'
});
```

```javascript
// worker.js
import { parseRows } from './parser.js';

self.onmessage = (event) => {
  const rows = parseRows(event.data.csv);
  self.postMessage({ rowCount: rows.length });
};
```

Two things worth knowing here. The `new URL('./worker.js', import.meta.url)` pattern is what bundlers like Vite and webpack recognize, so the worker file gets built and hashed correctly instead of being treated as a runtime string. And `type: 'module'` is what unlocks `import` inside the worker. Every evergreen browser supports module workers today.

## How data crosses the boundary: structured clone

When you `postMessage` an object, it does not pass by reference. The browser serializes it using the **structured clone algorithm** and the worker receives a deep copy. Mutating the object on one side never affects the other.

Structured clone handles far more than JSON: `Date`, `RegExp`, `Map`, `Set`, `ArrayBuffer`, typed arrays, even circular references. What it can't clone are things tied to a scope, functions and DOM nodes among them. Try to send a function and you get a `DataCloneError`.

The catch is cost. Cloning a large object means copying every byte. Ship a 50 MB buffer to a worker and you pay to duplicate 50 MB in memory, plus the time to walk the structure. For big binary payloads there's a better way.

## Transferables: move, don't copy

Some objects can be *transferred* instead of cloned. Transfer means ownership moves to the other thread and the original becomes unusable, no copy involved. `ArrayBuffer` is the classic transferable.

You pass the transfer list as the second argument to `postMessage`:

```javascript
// main.js
const buffer = new ArrayBuffer(64 * 1024 * 1024); // 64 MB

// The buffer is MOVED into the worker, not copied
worker.postMessage({ buffer }, [buffer]);

console.log(buffer.byteLength); // 0 — we no longer own it
```

After the transfer, `buffer.byteLength` on the main thread is `0`. That's expected. The bytes now live in the worker, and reaching for them here would be a bug. This is the technique to reach for with image data, audio samples, WASM memory, anything large and binary. It turns a multi-megabyte copy into a near-instant pointer hand-off.

## Terminating a worker

Workers don't clean themselves up. When you're done, shut it down:

```javascript
// From the main thread
worker.terminate();
```

```javascript
// Or the worker can stop itself from the inside
self.close();
```

A leaked worker holds memory and, if it's stuck in a loop, a whole CPU core. If you spin up workers dynamically (say, one per file upload), terminating them when the job finishes is not optional.

## When workers are worth it

Web workers help when the main thread is busy *computing*, not waiting. Good candidates:

- **Parsing** large CSV, JSON, or XML payloads before you render them.
- **Image processing**: filters, resizing, format conversion pixel by pixel.
- **Cryptography and hashing** over large inputs.
- **Data crunching**: sorting or aggregating tens of thousands of rows, running simulations.
- **Text search or diffing** across big documents.

The common thread is CPU work that takes long enough for a user to notice. If a task runs for more than ~50ms on the main thread, it's a frame budget you've blown, and a worker is worth considering.

## When a worker is the wrong tool

This is where I see people over-reach. Workers don't make I/O faster.

- **I/O-bound work doesn't benefit.** `fetch` is already asynchronous and non-blocking. Wrapping a network request in a worker adds a message round-trip and buys you nothing, because the main thread was never blocked waiting for the response in the first place. If your slowness is really about waiting on async operations, the fix is in how you structure your promises, not a new thread. My write-up on [async/await and promise pitfalls](/blog/async-await-javascript-promises-and-pitfalls) covers that side.
- **Tiny tasks lose to overhead.** Spinning up a worker and cloning data has a fixed cost. Offloading a sum over ten numbers is slower than just doing it inline.
- **DOM-heavy work can't move.** If the expensive part is creating thousands of DOM nodes, a worker can't help directly, because it can't touch the DOM. Sometimes the real answer is throttling how often you react to an event; I compared the approaches in [debounce vs throttle](/blog/debounce-vs-throttle-javascript).

A quick gut check: is the tab pinned near 100% CPU during the slow moment, or is it idle and waiting? Workers fix the first case, never the second.

## Web workers vs service workers

The names collide, the jobs don't. A **web worker** is a background thread for computation, tied to the page that created it, gone when you close the tab. A **service worker** is a network proxy that sits between your page and the network to enable caching, offline support, and push notifications. It can run when no page is open at all.

If your goal is "stop this heavy loop from freezing the UI," you want a web worker. If your goal is "make the app load offline," that's a service worker. Different API, different lifecycle, different mental model.

## Common pitfalls

These are the ones that actually bit me:

- **Expecting shared state.** The worker gets a *copy*. Mutating `event.data` inside the worker changes nothing on the main thread. Send the result back explicitly.
- **Trying to touch the DOM.** `document is not defined` inside a worker isn't a bug, it's the design. Return data and let the main thread render.
- **Forgetting the error handler.** An uncaught throw in the worker fails silently unless you set `worker.onerror`. You'll stare at a dead worker with no console output.
- **Cloning huge buffers.** If you're moving megabytes of binary data and it feels sluggish, you probably forgot the transfer list. Use the second `postMessage` argument.
- **Using a transferred buffer afterward.** Once transferred, the source `ArrayBuffer` is empty. Read from it on the old thread and you get zeros.
- **Never terminating.** Dynamically created workers that are never terminated leak. Call `terminate()` when the job is done.
- **One worker per tiny task.** Worker creation isn't free. For repeated small jobs, reuse a single worker or a small pool instead of spawning fresh ones.

## FAQ

### Can a web worker access the DOM or `window`?

No. Workers run in an isolated scope with no `document` and no `window`. The global object is `self`. They can use plenty of APIs, `fetch`, `WebSocket`, `IndexedDB`, timers, but anything that manipulates the page has to happen on the main thread after you message the result back.

### How many web workers should I create?

Roughly cap it around `navigator.hardwareConcurrency`, the number of logical cores the machine reports. Spawning far more threads than cores just means they fight over the same CPUs and add scheduling overhead. For steady workloads, a small reusable pool beats creating a worker per task.

### Do errors inside a worker crash my whole page?

No. An uncaught error stays contained in the worker's thread and won't take down the page. But it also won't surface anywhere useful unless you attach `worker.onerror` on the main thread (or a `self.onerror` inside the worker). Without a handler, failures are easy to miss.

### Is `postMessage` slow?

For small payloads, no, it's cheap. The cost grows with the size of the data because of structured cloning, which deep-copies everything. When you're passing large binary buffers, switch to transferables so the data is moved rather than copied, and the transfer is close to instant regardless of size.

## Wrapping up

Web workers are a focused tool: they take CPU-bound work off the main thread so your interface stays responsive. Reach for them when a task is heavy enough to drop frames, parsing, image work, crypto, big data crunching, and skip them for I/O that's already async or for jobs too small to justify the overhead.

The mental model that keeps me out of trouble: a worker is a coworker in another room. You slide requests under the door and results come back the same way. You never share a desk. Once that clicks, `postMessage`, structured clone, transferables, and `terminate()` all fall into place, and that frozen CSV parse becomes a background job the user never notices. And when the worker itself does async work that can fail, the same disciplined [error handling in async JavaScript](/blog/async-javascript-error-handling) still applies inside the worker as it does anywhere else.