---
name: "Cancelling Requests with AbortController"
slug: javascript-abortcontroller-cancel-requests
short_description: "How to cancel fetch requests in JavaScript, fix the stale autocomplete race, tell AbortError from real errors, and abort timers and listeners."
language: en
published_at: 2027-05-14 09:00:00
is_published: true
tags: [javascript, typescript, fetch, react]
---

The bug that sent me looking for this was an autocomplete box that kept showing the wrong results. You type `rea`, then `react`, and for a split second the list snaps back to results for `rea`. Nothing was broken in the usual sense — every request succeeded, every response was parsed correctly. The problem was that I had two requests in flight and no rule about which one wins.

That race is what `AbortController` is really for. Cancelling a request is the headline feature, but the reason it earns its place in a codebase is that it gives you a single, standard way to say "I don't care about this work anymore" — to `fetch`, to an event listener, to your own async loop. Here's how it works, where it bites, and the patterns I actually reach for.

## The out-of-order response bug

Let's make the race concrete. This is a stripped-down autocomplete that fires a request on every keystroke:

```js
const input = document.querySelector('#search');
const list = document.querySelector('#results');

input.addEventListener('input', async (e) => {
  const q = e.target.value;
  const res = await fetch(`/api/search?q=${encodeURIComponent(q)}`);
  const items = await res.json();
  render(items); // whoever finishes last wins
});
```

The failure mode isn't obvious in local dev where every response comes back in 2ms. It shows up on a real network. The request for `rea` might take 400ms because the server was busy; the request for `react` comes back in 90ms. So `react`'s results render first, then `rea`'s slower response lands and overwrites them. The user typed more, but the UI went backwards.

You can't fix this by comparing timestamps or debouncing alone — debouncing reduces how often it happens but doesn't eliminate it. The correct fix is to cancel the previous request the moment you start a new one, so the stale response never resolves into a render.

## Cancelling a fetch

`AbortController` is a small object with two parts: a `signal` you hand to whatever you want to be cancellable, and an `abort()` method that flips that signal. `fetch` accepts the signal in its options.

```js
let controller = null;

input.addEventListener('input', async (e) => {
  const q = e.target.value;

  controller?.abort();          // cancel the previous in-flight request
  controller = new AbortController();

  try {
    const res = await fetch(`/api/search?q=${encodeURIComponent(q)}`, {
      signal: controller.signal,
    });
    const items = await res.json();
    render(items);
  } catch (err) {
    if (err.name === 'AbortError') return; // we cancelled it on purpose
    throw err; // a real failure
  }
});
```

When you call `controller.abort()`, the pending `fetch` rejects its promise with an `AbortError`. That's the key: an aborted request doesn't quietly disappear, it throws. If you don't catch it you'll get unhandled rejections in the console for every keystroke. So the stale `rea` request now rejects before it ever reaches `render`, and only the newest request survives to paint the list.

One controller is single-use. Once you've called `abort()`, that signal is spent — you create a fresh `AbortController` for the next request, which is why the code reassigns `controller` every time.

## AbortError vs. a real error

This is the part I've watched people get wrong in code review over and over. When a `fetch` fails, you have to distinguish three situations:

- **You aborted it** — expected, do nothing.
- **The network failed** — connection dropped, DNS died, CORS rejected. `fetch` rejects with a `TypeError`.
- **The server answered with an error status** — 404, 500. `fetch` does *not* reject for these; the promise resolves and you check `res.ok` yourself.

Only the first should be swallowed. Silently ignoring the other two is how a broken feature ends up looking like an empty result set instead of an error.

```js
try {
  const res = await fetch(url, { signal });
  if (!res.ok) throw new Error(`Request failed: ${res.status}`);
  return await res.json();
} catch (err) {
  if (err.name === 'AbortError') return; // intentional, not a failure
  console.error('Search failed', err);
  showErrorState();
}
```

Checking `err.name === 'AbortError'` is more reliable than `instanceof DOMException` across environments. If you want to attach your own reason, `abort()` takes an argument: `controller.abort(new Error('user navigated away'))`, and that value shows up as `signal.reason` and as the rejection.

## Timeouts without the plumbing

Before there was a built-in, everyone wired up their own timeout with `setTimeout` calling `abort()`. You still need that pattern when you want a timeout *and* manual cancellation, but for a plain "give up after N milliseconds" there's now `AbortSignal.timeout()`:

```js
const res = await fetch('/api/report', {
  signal: AbortSignal.timeout(5000), // aborts after 5s
});
```

When it fires, the rejection is a `TimeoutError`, not an `AbortError` — so you can tell "we timed out" apart from "the user cancelled." Handle both:

```js
try {
  const res = await fetch(url, { signal: AbortSignal.timeout(5000) });
  return await res.json();
} catch (err) {
  if (err.name === 'TimeoutError') return showSlowNetworkMessage();
  if (err.name === 'AbortError') return;
  throw err;
}
```

## Combining signals with AbortSignal.any()

Now the real-world case: you want a request that dies if it takes too long *or* if the user navigates away. Two independent reasons to cancel, one fetch. `AbortSignal.any()` takes an array of signals and returns a signal that fires as soon as any of them does.

```js
const userCancel = new AbortController();

const res = await fetch('/api/upload', {
  signal: AbortSignal.any([
    userCancel.signal,
    AbortSignal.timeout(30000),
  ]),
});

// elsewhere, when the user hits "cancel":
userCancel.abort();
```

You keep a long-lived controller tied to component lifetime, mix in a per-request timeout, and never have to manually chain listeners between them. Do this by hand and you end up adding an `abort` listener on one signal that calls `abort()` on another, then remembering to detach it so you don't leak — `AbortSignal.any()` is exactly that, done correctly.

## It's not just for fetch

The signal is a general cancellation token. Two places it pays off that aren't network calls at all.

**Event listeners.** `addEventListener` accepts a `signal` in its options. When the signal aborts, the listener is removed — no `removeEventListener`, no keeping a reference to the handler function. This is genuinely nicer for teardown:

```js
const controller = new AbortController();

window.addEventListener('resize', onResize, { signal: controller.signal });
document.addEventListener('keydown', onKey, { signal: controller.signal });

// one call removes both:
controller.abort();
```

One `abort()` detaches every listener registered with that signal. For a component that wires up a dozen listeners, this collapses your cleanup into a single line.

**Your own async work.** If you write a function that does something long-running, accept a signal and check it. The signal exposes `aborted` (a boolean) and `throwIfAborted()`:

```js
async function processChunks(items, { signal } = {}) {
  for (const item of items) {
    signal?.throwIfAborted(); // bail between chunks
    await heavyWork(item);
  }
}
```

`throwIfAborted()` throws the abort reason if the signal has fired, otherwise does nothing. You decide where the cancellation points are — usually at the top of a loop or between awaits. This is how you make a custom pipeline cooperate with the same cancellation your fetches use.

## The React useEffect pattern

In React this ties directly into effect cleanup. An effect that fetches must cancel on unmount and on dependency change, or you get the classic "can't update state on an unmounted component" behaviour and, worse, the same out-of-order race from the top of this article — this time between the previous render's fetch and the new one.

```js
useEffect(() => {
  const controller = new AbortController();

  fetch(`/api/user/${id}`, { signal: controller.signal })
    .then((res) => res.json())
    .then(setUser)
    .catch((err) => {
      if (err.name !== 'AbortError') setError(err);
    });

  return () => controller.abort(); // cleanup runs before the next effect and on unmount
}, [id]);
```

The cleanup function aborts the controller. When `id` changes, React runs cleanup for the old effect before running the new one, so the request for the old `id` is cancelled before the request for the new `id` starts. The stale response rejects with `AbortError` and never calls `setUser`. That's the whole bug fixed for free by respecting the effect lifecycle.

## Node support

This isn't a browser-only feature anymore. `AbortController`, `AbortSignal`, and the signal option on the built-in `fetch` are all available in Node (fetch landed as stable in Node 21, having been experimental earlier). `AbortSignal.timeout()` and `AbortSignal.any()` are available in current Node releases too. Many core Node APIs accept a signal directly — `fs.readFile`, `setTimeout` from `node:timers/promises`, streams, `http` requests, and `child_process`:

```js
import { setTimeout as sleep } from 'node:timers/promises';

const ac = new AbortController();
setTimeout(() => ac.abort(), 1000);

try {
  await sleep(5000, undefined, { signal: ac.signal });
} catch (err) {
  if (err.name === 'AbortError') console.log('slept less than planned');
}
```

So the same mental model — pass a signal, catch `AbortError` — carries from front-end fetches to server-side timeouts and file reads.

## Pitfalls I've actually hit

- **Reusing a controller.** After `abort()`, the signal stays aborted forever. A new request needs a new `AbortController`.
- **Swallowing every error.** `catch (err) { if (err.name === 'AbortError') return; }` and then forgetting the `else` branch — real failures vanish. Always rethrow or handle the non-abort case.
- **Assuming abort undoes server work.** Cancelling the request stops your client from waiting; it does not necessarily stop the server. If the server already started charging a card or writing a row, `abort()` won't roll that back.
- **Forgetting `res.ok`.** An aborted fetch throws, but a 500 doesn't. Both need handling, in different places.

## FAQ

**Does aborting a fetch stop the server from processing the request?**
Not reliably. `abort()` closes the client's connection and rejects the promise, but by then the server may have already received and acted on the request. Treat it as "I stopped waiting," not "that never happened." For anything with side effects, make the endpoint idempotent or cancellable server-side.

**How do I tell a timeout apart from a user cancel?**
`AbortSignal.timeout()` rejects with a `TimeoutError`; a manual `controller.abort()` rejects with an `AbortError`. Check `err.name`. If you need a custom reason, pass it to `abort(reason)` and read `signal.reason`.

**Can one AbortController cancel multiple fetches at once?**
Yes. Pass the same `signal` to every fetch, and a single `abort()` cancels all of them. That's handy for tearing down a whole screen's worth of requests together. To cancel them individually, give each its own controller.

**Why do I get "AbortError" logged on every keystroke?**
Because an aborted fetch rejects, and you're not catching it. Wrap the fetch in try/catch (or `.catch`) and return early when `err.name === 'AbortError'` — cancelling is expected, not an error worth surfacing.

The pattern is small enough to memorize: make a controller, pass its signal, catch `AbortError` and ignore it, throw everything else. Once it's muscle memory you stop writing debounce hacks and timestamp comparisons to paper over races, because the race can't happen — the losing request is already gone. Start with your autocomplete and your `useEffect` fetches; those are where the stale-response bug hides most often.
