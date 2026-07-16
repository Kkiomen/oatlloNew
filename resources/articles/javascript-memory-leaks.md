---
name: "Finding and Fixing JavaScript Memory Leaks"
slug: javascript-memory-leaks
short_description: "Why JS memory keeps climbing, the leak sources that cause it, and how to confirm one with Chrome DevTools heap snapshots and fix it."
language: en
published_at: 2027-05-28 09:00:00
is_published: true
tags: [javascript, node, performance, debugging, frontend]
---

The tab was using 1.4 GB. It was a dashboard that people left open all day, and by mid-afternoon Chrome would start swapping and the whole thing crawled. Nobody had "done anything" — you just navigated between a few views and let it sit. That slow, one-directional climb in memory, the kind that never comes back down, is the signature of a leak. So here's how the garbage collector decides what to keep, the handful of patterns that quietly defeat it, and how to prove which one you've got instead of guessing.

## What the garbage collector actually keeps

JavaScript engines like V8 use a **mark-and-sweep** collector. It doesn't count references or track how "old" an object is. It starts from a set of **roots** — the global object, the current call stack, and a few engine internals — and walks every reference it can reach. Anything it can touch is marked as live. Everything it can't reach gets swept.

The single idea that matters here: **an object stays in memory as long as something reachable points to it.** Not "as long as you're using it." Reachability is mechanical, and it doesn't care about your intent. If a detached DOM node is still referenced by an array that's still referenced by a module-level variable, that node is live — the collector is behaving correctly, and you have a leak anyway.

That reframes the whole problem. A leak isn't the GC failing. It's your code holding a reference you forgot about, so the GC is *right* to keep the object alive. Finding a leak means finding that forgotten reference — the **retaining path** from a root down to the thing that should be dead.

## High memory is not the same as a leak

Before you go snapshot-hunting, rule this out. A heavy single-page app can legitimately sit at 300–500 MB with big data structures, cached views, and a fat framework runtime. That's *high memory*, not a leak.

The difference is the shape of the curve over time. A leak grows without bound and never releases. High memory rises to a plateau and stays there — it goes up when you open a big view and comes back down when you leave it. The test is repetition: do the same action ten times (open a modal, close it; navigate to a page, navigate away) and watch whether memory returns to roughly where it started. If each cycle leaves a little residue behind and the baseline creeps up, that's a leak. If it bounces back to the same floor, you're just heavy — optimize allocations, not lifetimes.

## Where the references hide

Nearly every leak I've chased in the browser came from one of these. They all share a shape: something long-lived is holding a reference to something that should have been short-lived.

**Forgotten event listeners.** You add a listener on `window`, `document`, or some element that outlives your component, and never remove it. The handler is a closure — it captures the surrounding scope, often including large objects or the whole component. As long as the listener is registered, the closure is reachable, and so is everything it closed over.

**Detached DOM nodes.** You remove a node from the document but keep a reference to it in a variable, array, or Map. It's gone from the page but not from memory, and if it had children, the whole subtree comes with it. This is the classic one, and it's usually a symptom of the previous point — a listener or a cache holding the node.

**Timers and intervals never cleared.** `setInterval` keeps its callback alive forever by design. If that callback references your component and you never call `clearInterval`, the component can never be collected. `setTimeout` is less dangerous because it fires once and releases, but a timeout that reschedules itself is just an interval wearing a disguise.

**Closures that capture more than they need.** A closure keeps its *entire* enclosing scope alive, not just the variables it uses — engines optimize this somewhat, but you can't rely on it. If a long-lived callback closes over a scope that also contains a 10 MB buffer, that buffer stays.

**Growing module-level caches.** A `Map` or plain object at module scope that you only ever `set` into. Every entry is a root-reachable reference. Without eviction, it's an unbounded leak that looks like a feature. Route caches, memoization tables, and "I'll just remember the last result" objects are the usual suspects.

**Subscriptions and observers.** Event emitters, `ResizeObserver`, `IntersectionObserver`, RxJS subscriptions, store subscriptions — anything with a `subscribe` needs a matching `unsubscribe`. The producer holds a reference to your callback until you tell it to let go.

## A concrete leak, and the fix

Here's a stripped-down version of the dashboard bug. A component subscribes to window resize and to a data store, and caches rendered rows by id:

```js
const rowCache = new Map();

function mountWidget(container, store) {
  const rows = [];

  function onResize() {
    // relayout using `rows`, which references DOM nodes
    rows.forEach((row) => row.recalcWidth(container.clientWidth));
  }

  function onData(records) {
    for (const record of records) {
      const el = document.createElement('div');
      el.textContent = record.label;
      container.appendChild(el);
      const row = { el, recalcWidth: (w) => (el.style.width = w + 'px') };
      rows.push(row);
      rowCache.set(record.id, row); // never deleted
    }
  }

  window.addEventListener('resize', onResize); // never removed
  store.subscribe(onData);                     // never unsubscribed

  return function unmount() {
    container.innerHTML = ''; // clears the DOM, but not the references
  };
}
```

Look at what survives an `unmount()`. Setting `innerHTML = ''` detaches the nodes from the page, but `rows` still holds every row object, each row holds its `el`, and `rowCache` holds them too — at module scope, which is root-reachable. On top of that, the `resize` listener is still registered, so `onResize` (and the entire scope it closed over, including `rows` and `container`) can never be collected. Every mount adds a fresh set of detached nodes that nothing will ever free. That's the climb.

The fix is to make `unmount` actually release everything the component acquired:

```js
function mountWidget(container, store) {
  const rows = [];
  const cachedIds = [];

  function onResize() {
    rows.forEach((row) => row.recalcWidth(container.clientWidth));
  }

  function onData(records) {
    for (const record of records) {
      const el = document.createElement('div');
      el.textContent = record.label;
      container.appendChild(el);
      const row = { el, recalcWidth: (w) => (el.style.width = w + 'px') };
      rows.push(row);
      rowCache.set(record.id, row);
      cachedIds.push(record.id);
    }
  }

  window.addEventListener('resize', onResize);
  const unsubscribe = store.subscribe(onData);

  return function unmount() {
    window.removeEventListener('resize', onResize);
    unsubscribe();
    cachedIds.forEach((id) => rowCache.delete(id));
    rows.length = 0;
    container.innerHTML = '';
  };
}
```

Every acquisition now has a matching release: the listener is removed, the store subscription is torn down, the cache entries are deleted, and the local `rows` array is emptied so its closure stops retaining the nodes. After `unmount`, nothing reachable points at those DOM elements, and the next GC pass reclaims them. The rule that makes this reliable: **whatever you register in setup, unregister in teardown, in the same place.** If your framework has an unmount/cleanup hook (`useEffect`'s return, `onUnmounted`, `disconnectedCallback`), that's where all of this belongs.

## Let the collector do it for you: WeakMap and WeakRef

Sometimes you genuinely want to associate data with an object — metadata for a DOM node, a computed value for an entity — without being the reason it stays alive. That's what **`WeakMap`** is for. Its keys are held *weakly*: if the key object becomes unreachable everywhere else, the entry disappears and the GC collects the key. You can't iterate a `WeakMap` and it has no `size`, precisely because entries can vanish at any moment.

```js
const nodeMeta = new WeakMap();

function tag(node, data) {
  nodeMeta.set(node, data); // does NOT keep `node` alive
}

// When `node` is removed from the DOM and nothing else references it,
// the WeakMap entry is collected automatically — no manual delete needed.
```

Swap the `rowCache` in the example for a `WeakMap` keyed by the DOM element and the "never deleted" cache stops being a leak: once the element is gone, its cache entry goes with it. The trade-off is that you lose enumeration and explicit control — you can't list what's cached, and you can't force-evict.

**`WeakRef`** is the lower-level tool: a reference that doesn't prevent collection, which you dereference with `.deref()` (getting `undefined` if the target is gone). It's genuinely useful for things like a cache of expensive objects you're willing to lose under memory pressure. But reach for it rarely — the timing of collection is non-deterministic, and code that depends on *when* a `WeakRef` clears is code you'll regret. For the vast majority of cases, disciplined teardown or a `WeakMap` is the right answer, not `WeakRef` or `FinalizationRegistry`.

## Confirming it in Chrome DevTools

Guessing which pattern you hit is a waste of an afternoon. The heap tells you. Open DevTools → **Memory**.

The most reliable method is the **three-snapshot technique**:

1. Load the app, do the action once to warm up any lazy caches, then take a heap snapshot (Snapshot 1). This is your baseline.
2. Perform the suspected leaky cycle several times — open and close the view ten times, say.
3. Take a second snapshot, do the cycle a few more times, take a third.

Now select the third snapshot and, in the class filter dropdown, choose **"Objects allocated between Snapshot 1 and Snapshot 2."** DevTools shows you exactly what was created in that window and *survived* to snapshot 3. If your cycle is clean, this list is nearly empty. If it's leaking, you'll see your objects piling up — detached `HTMLDivElement`s, arrays, closures — with a count that matches the number of cycles you ran. Click one, and the **Retainers** panel at the bottom shows the retaining path back to a root. That path *is* your forgotten reference. Read it from the bottom up and you'll find the `window` listener, the module Map, or the interval holding it.

Two shortcuts worth knowing. Filter the snapshot for **"Detached"** to jump straight to DOM nodes that are off-page but still retained — the fastest way to catch the detached-node class of bug. And use the **Allocation instrumentation on timeline** profile: it records allocations over time as a bar chart, and the blue bars that never turn gray are memory that was allocated and never freed. It's noisier than three snapshots but great for narrowing down *when* during an interaction the growth happens.

## Leaks in Node.js

The browser at least reloads eventually. A Node process runs for weeks, so a leak that adds a few kilobytes per request is a restart-at-3am problem. The usual culprits are server-flavored versions of the same patterns: an emitter you add listeners to on every request (watch for the `MaxListenersExceededWarning`, which fires once you cross the default cap of 10 listeners — often the first sign), a module-level array or Map that accumulates per-request data, or a closure captured in a route handler that pins a large object.

You get the same tooling. Start the process with the inspector:

```bash
node --inspect server.js
```

Then open `chrome://inspect` in Chrome, click **inspect** on your process, and you're in the exact same Memory panel — take heap snapshots and use the three-snapshot technique against a load test instead of clicks. For headless capture on a running server, `v8.writeHeapSnapshot()` dumps a `.heapsnapshot` file you can load into DevTools later:

```js
const v8 = require('node:v8');
process.on('SIGUSR2', () => {
  const file = v8.writeHeapSnapshot();
  console.log('heap snapshot written to', file);
});
```

Send `SIGUSR2` to the process before and after a sustained load, diff the two snapshots in DevTools, and the survivors point you at the retaining path. If you want the growth curve first, `process.memoryUsage().heapUsed` logged on an interval will tell you whether you're looking at a real leak or just a busy process — same "does it plateau or climb forever" question as the browser.

## FAQ

**Why does memory keep climbing even after garbage collection runs?**
Because the GC is doing its job correctly — the objects are still reachable. If memory grows across GC cycles and never drops, something live is retaining the "dead" objects. Take a heap snapshot and read the retainers panel; the path back to a root is the reference you need to break.

**Does setting a variable to null free the memory?**
Only if that was the *last* reference to the object. Nulling a variable removes one edge in the reference graph; if the object is still reachable through another path (a cache, a closure, a listener), it stays. `null`-ing helps most for long-lived variables holding big values you're truly done with — but it's no substitute for removing listeners and clearing timers.

**When should I use WeakMap instead of a regular Map?**
Use `WeakMap` when the keys are objects whose lifetime you don't want to control — you want the entry to disappear the moment the key is collected elsewhere. Use a regular `Map` when you need to enumerate entries, know the size, or explicitly evict. If you find yourself writing manual `delete` calls to avoid a leak in a Map keyed by objects, a `WeakMap` probably wants the job.

**Is a growing heap always a leak?**
No. Heaps grow to a working-set size and then plateau. Repeat the same operation many times and watch the baseline: bounce back to the same floor means high-but-healthy memory, a steadily rising floor means a leak. Confirm which before you spend time hunting.

## The one habit that prevents most of them

Every leak in this article reduces to an acquisition without a matching release. So make the pairing structural: the moment you write `addEventListener`, `subscribe`, `setInterval`, or `observe`, write the teardown in the same breath and put it where cleanup runs. When you can hand the lifetime problem to the collector instead — with a `WeakMap` — do that and skip the bookkeeping entirely. And when memory does climb, don't theorize. Take three snapshots and let the retainers panel name the reference you forgot.
