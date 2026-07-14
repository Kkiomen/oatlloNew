---
name: "Handling Errors in Async JavaScript Properly"
slug: async-javascript-error-handling
short_description: "A practical guide to async JavaScript error handling: try/catch, .catch(), Promise.allSettled, AbortController timeouts, and the traps that bite."
language: en
published_at: 2027-01-27 09:00:00
is_published: true
tags: [javascript, async, error-handling, nodejs]
---

The first production incident I ever caused was a swallowed error. A `catch` block with nothing inside it, sitting quietly in a payment retry loop, hiding the fact that our webhook handler had been failing for six hours. Nobody noticed until a customer emailed. That bug taught me that async javascript error handling is less about knowing the syntax and more about deciding, deliberately, what should happen when things go wrong.

This post picks up where [the async/await guide](/blog/async-await-javascript-promises-and-pitfalls) left off. There we covered how `async`/`await` works. Here we deal with the messier half: what to do when the promise rejects, the network dies, or a function three layers down throws something you didn't expect.

## Why async JavaScript error handling is its own problem

Synchronous errors are easy to reason about. You throw, the stack unwinds, some `catch` up the chain catches it. Done.

Async breaks that mental model. A rejected promise isn't an exception being thrown down a stack. It's a value that propagates through the microtask queue. If nothing is listening when it settles, the error doesn't disappear. It becomes an *unhandled rejection*, and depending on where you run it, that can crash your Node process outright.

So the core question of async error handling is really: **is someone listening when this promise rejects?**

## try/catch with async/await

When you `await` a promise that rejects, the rejection is re-thrown as a regular exception at the `await` line. That means ordinary `try`/`catch` works, and it reads almost like synchronous code.

```javascript
async function loadUser(id) {
  try {
    const res = await fetch(`https://api.example.com/users/${id}`);
    if (!res.ok) {
      throw new Error(`Request failed with status ${res.status}`);
    }
    return await res.json();
  } catch (err) {
    console.error(`Could not load user ${id}:`, err.message);
    throw err; // re-throw so the caller can decide what to do
  }
}
```

Two things worth calling out here. First, `fetch` does **not** reject on HTTP 404 or 500 — it only rejects on network-level failures. You have to check `res.ok` yourself, otherwise a `500` sails straight through your `try` block looking like success. I've reviewed this exact bug more times than any other on this list.

Second, notice the re-throw. Catching an error and then rethrowing it feels redundant until you need it. It lets a low-level function add context (which user, which request) while still letting the caller decide the real recovery strategy.

## .catch() and when it reads better

The `.catch()` method attaches a rejection handler directly to a promise. Functionally it overlaps with `try`/`catch`, but the shapes fit different jobs.

```javascript
// Fine when you have exactly one promise and a simple fallback:
const config = await loadConfig().catch(() => defaultConfig);
```

That one-liner is genuinely nicer than wrapping three lines of `try`/`catch` around a single call just to supply a default.

Reach for `.catch()` when:

- You want a **fallback value** for a single operation and nothing else in the block can throw.
- You're handling a rejection at the very edge of your app, on a promise you're not awaiting.
- You're chaining `.then()` calls in code that predates async/await.

Reach for `try`/`catch` when:

- Several awaited calls share the same recovery logic.
- You need a `finally` for cleanup regardless of outcome.
- The surrounding code reads more clearly top-to-bottom.

They're not rivals. I mix both in the same file without guilt.

## Errors that slip through the cracks

Here's the failure mode nobody warns you about. This looks fine and is quietly broken:

```javascript
async function sendAll(messages) {
  messages.forEach(async (msg) => {
    await deliver(msg); // rejection here is NOT caught by any try/catch outside
  });
}
```

The `async` callback returns a promise that `forEach` throws away. If `deliver` rejects, that rejection has no handler. Your outer `try`/`catch` won't see it because `forEach` returned long before the promise settled.

The fix is to collect the promises and await them together:

```javascript
async function sendAll(messages) {
  const results = await Promise.allSettled(messages.map(deliver));
  const failures = results.filter((r) => r.status === 'rejected');
  if (failures.length > 0) {
    console.warn(`${failures.length} of ${messages.length} messages failed`);
  }
}
```

Which brings us to the two combinators everyone confuses.

## Promise.all vs Promise.allSettled

`Promise.all` is **fail-fast**. The moment one input rejects, the whole thing rejects with that error and stops giving you the rest. The other operations keep running in the background, but you never get their results.

```javascript
try {
  const [user, orders, prefs] = await Promise.all([
    getUser(id),
    getOrders(id),
    getPrefs(id),
  ]);
  render(user, orders, prefs);
} catch (err) {
  // Any single failure lands here. You don't know which one, and you
  // don't get the two that succeeded.
  showError(err);
}
```

Use `Promise.all` when you genuinely need every result and a partial answer is useless, like rendering a page that requires all three pieces.

`Promise.allSettled` never rejects. It waits for everything and hands back an array describing each outcome, so you can degrade gracefully.

```javascript
const results = await Promise.allSettled([
  getUser(id),
  getOrders(id),
  getPrefs(id),
]);

const [user, orders, prefs] = results;
if (user.status === 'fulfilled') render(user.value);
if (orders.status === 'rejected') logMetric('orders_failed', orders.reason);
```

Rule of thumb from my own code: if the operations are independent and I'd rather show three-quarters of a dashboard than a blank error page, `allSettled` wins. If they're a package deal, `all`.

## Timeouts and cancellation with AbortController

A request that never resolves is worse than one that fails. `AbortController` gives you a signal you can pass into `fetch` (and many other APIs) to cancel it.

```javascript
async function fetchWithTimeout(url, ms = 5000) {
  const controller = new AbortController();
  const timer = setTimeout(() => controller.abort(), ms);

  try {
    const res = await fetch(url, { signal: controller.signal });
    return await res.json();
  } catch (err) {
    if (err.name === 'AbortError') {
      throw new Error(`Request to ${url} timed out after ${ms}ms`);
    }
    throw err;
  } finally {
    clearTimeout(timer); // always clear the timer, success or failure
  }
}
```

Node also ships `AbortSignal.timeout(ms)`, which is a shortcut for exactly this pattern when all you want is a timeout:

```javascript
const res = await fetch(url, { signal: AbortSignal.timeout(5000) });
```

The `finally` block is the point I want to hammer on. Whether the fetch succeeds, times out, or throws for some unrelated reason, `clearTimeout` runs. Cleanup that must happen regardless of outcome belongs in `finally`, not duplicated in both the success path and the `catch`.

## Wrapping errors with custom Error subclasses

Once errors cross a few function boundaries, `err.message` alone stops being enough. You want to know the *kind* of failure without string-matching on messages. Custom `Error` subclasses give you that.

```javascript
class ApiError extends Error {
  constructor(message, { status, cause } = {}) {
    super(message, { cause });
    this.name = 'ApiError';
    this.status = status;
  }
}

async function getJson(url) {
  let res;
  try {
    res = await fetch(url);
  } catch (networkErr) {
    // Normalize a low-level failure into our own type, keeping the original.
    throw new ApiError('Network request failed', { cause: networkErr });
  }
  if (!res.ok) {
    throw new ApiError(`Unexpected ${res.status}`, { status: res.status });
  }
  return res.json();
}
```

Now a caller can branch on `err instanceof ApiError` and read `err.status` instead of parsing prose. The `cause` option (standard since ES2022) preserves the original error so you don't lose the stack trace of what actually broke. When you normalize errors, keep the original around — future-you debugging at 2am will thank present-you.

## The safety net: catching what you missed

Even careful code leaks the occasional unhandled rejection. Set a global handler so those don't vanish silently, or crash the process without a trace.

In Node:

```javascript
process.on('unhandledRejection', (reason) => {
  console.error('Unhandled promise rejection:', reason);
  // Log it, flush metrics, then exit. A process in an unknown state
  // shouldn't keep serving traffic.
  process.exit(1);
});
```

In the browser:

```javascript
window.onunhandledrejection = (event) => {
  reportToMonitoring(event.reason);
  event.preventDefault(); // stops the default console warning if you've logged it
};
```

Treat these as a last line of defense and an alarm bell, not as your primary strategy. If this handler fires in production, it means a rejection escaped every local handler you wrote — that's a bug to fix, not a place to recover.

## Common pitfalls

A quick list of the traps I still see in code review every month:

- **The empty catch.** `catch (e) {}` doesn't handle an error, it hides one. If you truly want to ignore a failure, leave a comment saying why. Otherwise at minimum log it.
- **Assuming `fetch` rejects on 4xx/5xx.** It only rejects on network failures. Check `res.ok`.
- **`await` inside `forEach`.** Rejections escape unhandled. Use `map` plus `Promise.all`/`allSettled`, or a plain `for...of` loop with `await`.
- **Forgetting `finally` for cleanup.** Open connections, timers, and file handles leak when the happy path and error path both need closing but only one does it.
- **Swallowing the original error while wrapping.** Pass `{ cause }` so the stack trace survives.
- **A `catch` that logs but doesn't rethrow or recover.** The caller thinks it succeeded and marches on with bad data.

## FAQ

### Why isn't my try/catch catching the async error?

Almost always one of two reasons. Either you forgot the `await` (so the function returns a pending promise and the `try` block exits before it rejects), or the rejection happens inside a callback like `forEach` that doesn't return the promise to you. Add the `await`, and make sure the promise is actually inside the `try` block.

### Should I use try/catch or .catch()?

Use `try`/`catch` when multiple awaited operations share recovery logic or you need `finally`. Use `.catch()` for a quick fallback on a single promise, or at the outermost edge of your app. Both are valid; pick whichever makes the intent clearest at that spot.

### How do I add a timeout to a fetch request?

Pass an `AbortSignal` to `fetch`. Either build one with `AbortController` and a `setTimeout` that calls `controller.abort()`, or use the built-in `AbortSignal.timeout(ms)`. Catch the resulting `AbortError` to turn it into a meaningful timeout message, and clear any timer in a `finally` block.

### What's the difference between Promise.all and Promise.allSettled?

`Promise.all` rejects as soon as any input rejects and discards the other results, which is good when you need every value. `Promise.allSettled` always resolves with an array of `{status, value}` or `{status, reason}` objects, so it fits cases where operations are independent and partial success is acceptable.

## Wrapping up

Good async javascript error handling comes down to a few habits, not a big framework. Await your promises so `try`/`catch` can do its job. Check `res.ok` because `fetch` won't. Choose `Promise.allSettled` when partial results beat total failure, and `Promise.all` when they don't. Wrap network failures in your own `Error` types with a `cause`, clean up in `finally`, cancel slow work with `AbortController`, and keep a global unhandled-rejection handler as a tripwire.

Do those consistently and you avoid the six-hour silent outage I started with. Next time you write an empty `catch`, picture that customer email — then write something inside it.