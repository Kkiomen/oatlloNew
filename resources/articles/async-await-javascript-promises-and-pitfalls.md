---
name: "Async Await JavaScript: Promises, async/await, and the Pitfalls That Bite"
slug: async-await-javascript-promises-and-pitfalls
short_description: "A practical guide to async await JavaScript: how promises work, async/await patterns, Promise.all vs allSettled, and the mistakes that break code."
language: en
published_at: 2026-10-16 09:00:00
is_published: true
tags: [javascript, async, promises]
---

The first time async code bit me, it was a loop that fetched 40 user profiles one after another. It worked. It was also painfully slow, and I couldn't figure out why until I actually understood what `await` does inside a loop. That's really what async await JavaScript is about: not just the syntax, but knowing when the engine is waiting and when it's working.

This guide walks through promises from the ground up, then async/await on top of them, and finishes with the pitfalls I keep seeing in code reviews. Every snippet here I ran before pasting it in.

## What a promise actually is

A promise is an object representing a value that isn't ready yet. It sits in one of three states:

- **pending**: the operation hasn't finished
- **fulfilled**: it finished and produced a value
- **rejected**: it failed with a reason (usually an `Error`)

Once a promise settles (fulfilled or rejected), it's locked. It won't flip states again. That one-way transition is a big part of why promises are easier to reason about than raw callbacks: a settled value can't be quietly changed out from under you.

You consume a promise with `.then()`, `.catch()`, and `.finally()`:

```javascript
function getUser(id) {
  return fetch(`https://jsonplaceholder.typicode.com/users/${id}`)
    .then((response) => {
      if (!response.ok) throw new Error(`HTTP ${response.status}`);
      return response.json();
    });
}

getUser(1)
  .then((user) => console.log(user.name))
  .catch((err) => console.error("Failed:", err.message))
  .finally(() => console.log("Request done"));
```

A few things worth noticing. `.then()` returns a new promise, so chaining works. Throwing inside a `.then()` callback rejects the chain, which is why the `.catch()` at the bottom can handle an HTTP error thrown three links up. And `.finally()` runs no matter what, without touching the value, which is good for cleanup like hiding a spinner.

## async/await is sugar over the same promises

`async`/`await` doesn't replace promises. It's a nicer way to write code that consumes them. An `async` function always returns a promise, even if you return a plain number:

```javascript
async function double(n) {
  return n * 2;
}

console.log(double(21)); // Promise { 42 }, not 42
double(21).then((v) => console.log(v)); // 42
```

That last point trips people up constantly. Whatever you `return` from an async function gets wrapped in a resolved promise. Whatever you `throw` becomes a rejected one.

Here's the same user fetch rewritten with async/await and proper error handling:

```javascript
async function getUser(id) {
  const response = await fetch(`https://jsonplaceholder.typicode.com/users/${id}`);
  if (!response.ok) throw new Error(`HTTP ${response.status}`);
  return response.json();
}

async function main() {
  try {
    const user = await getUser(1);
    console.log(user.name);
  } catch (err) {
    console.error("Failed:", err.message);
  } finally {
    console.log("Request done");
  }
}

main();
```

`try/catch` around `await` is your error boundary. A rejected promise that you `await` throws synchronously into that `try`, so a single `catch` covers both network failures and the manual `throw`. To me this reads much closer to synchronous code, and that's the point.

## Running things in parallel

This is where most performance problems live. Compare these two functions:

```javascript
// Sequential: waits for each fetch before starting the next
async function loadSequential(ids) {
  const users = [];
  for (const id of ids) {
    users.push(await getUser(id)); // blocks the loop each iteration
  }
  return users;
}

// Parallel: fires all requests, then waits once
async function loadParallel(ids) {
  const promises = ids.map((id) => getUser(id));
  return Promise.all(promises);
}
```

For three independent requests, the sequential version takes roughly the sum of all three round-trips. The parallel version takes about as long as the slowest single request. That 40-profile loop I mentioned at the start? Switching from the first pattern to the second cut it from around eight seconds to under a second.

The rule I use: **await in a loop only when each step depends on the previous one.** If the calls are independent, collect the promises and hand them to one of the combinators below.

### Choosing a promise combinator

There are four, and they behave differently when things go wrong:

- **`Promise.all`** resolves with an array of all values, but rejects the instant *any* input rejects. It fails fast. Use it when you need everything and a single failure should abort the batch.
- **`Promise.allSettled`** never rejects. It waits for every promise and gives you an array of `{ status, value }` or `{ status, reason }` objects. Use it when partial success is acceptable.
- **`Promise.race`** settles as soon as the *first* promise settles, whether it fulfilled or rejected. Handy for timeouts.
- **`Promise.any`** resolves with the first *fulfilled* value and ignores rejections, only failing if they all reject. Good for racing redundant sources.

Here's `allSettled` in practice, which is what I reach for when loading a dashboard where one broken widget shouldn't blank the whole page:

```javascript
async function loadDashboard(ids) {
  const results = await Promise.allSettled(ids.map((id) => getUser(id)));

  const loaded = results
    .filter((r) => r.status === "fulfilled")
    .map((r) => r.value);

  const failed = results.filter((r) => r.status === "rejected").length;

  console.log(`Loaded ${loaded.length}, failed ${failed}`);
  return loaded;
}
```

And a timeout built with `Promise.race`:

```javascript
function withTimeout(promise, ms) {
  const timeout = new Promise((_, reject) =>
    setTimeout(() => reject(new Error(`Timed out after ${ms}ms`)), ms)
  );
  return Promise.race([promise, timeout]);
}

// Rejects if getUser(1) hasn't settled within 3 seconds
await withTimeout(getUser(1), 3000);
```

One caveat this version glosses over: when the real promise wins, the `setTimeout` still fires later and does nothing useful. Harmless in a browser, but under load in Node those dangling timers add up, so in production I clear the timer in a `.finally()` on the race result.

## A quick note on the event loop

You don't need the whole spec, but one detail explains a lot of confusing ordering. Promise callbacks run as **microtasks**, which the engine drains before it handles the next **macrotask** (like a `setTimeout`). So this:

```javascript
console.log("1");
setTimeout(() => console.log("2"), 0);
Promise.resolve().then(() => console.log("3"));
console.log("4");
```

prints `1`, `4`, `3`, `2` — not `1`, `4`, `2`, `3`. The synchronous lines go first, then the microtask (`3`), and only then the timeout (`2`). Once you internalize that promises jump the queue ahead of timers, a lot of "why did this log out of order" moments stop being mysterious.

## Pitfalls I keep seeing

These show up in real reviews more than any syntax mistake:

- **Forgetting `await`.** Calling `getUser(1)` without awaiting hands you a pending promise, not a user. `user.name` becomes `undefined`, and there's no error to point at it. If a value looks wrong and it came from an async call, check for a missing `await` first.
- **Sequential awaits that should be parallel.** The loop pitfall from earlier. Independent work belongs in `Promise.all`, not a `for` loop.
- **Swallowing errors with a bare `Promise.all`.** Because it fails fast, one rejection throws away the results of everything that already succeeded. When partial results matter, use `allSettled`.
- **Unhandled promise rejections.** A rejected promise with no `.catch()` and no surrounding `try/catch` triggers an `unhandledRejection`. In modern Node this crashes the process by default. Always terminate a chain with error handling, or await inside a `try/catch`.
- **Mixing callbacks and promises.** Wrapping a callback API by hand and forgetting to reject on error leaves promises pending forever. Prefer `util.promisify` in Node, or wrap once in a `new Promise` and handle both branches.
- **Assuming `async` makes code parallel.** It doesn't. `async`/`await` controls *when* you wait, not *how many* things run at once. Parallelism comes from starting promises before you await them.

On that callback-mixing point, `new Promise` is the one place you still write the executor by hand, and it's easy to leave a path where neither `resolve` nor `reject` gets called:

```javascript
const fs = require("node:fs");

function readFilePromise(path) {
  return new Promise((resolve, reject) => {
    fs.readFile(path, "utf8", (err, data) => {
      if (err) return reject(err); // don't forget this branch
      resolve(data);
    });
  });
}
```

If you find yourself writing structural helpers like this a lot, it's worth reading up on [Node.js project structure](/blog/nodejs-project-structure) so the wrappers live somewhere sensible instead of scattered across route files. And if you're layering types on top, [TypeScript generics explained with real examples](/blog/typescript-generics-explained-with-real-examples) covers how to type a generic `Promise<T>` wrapper cleanly.

## FAQ

### Is async/await faster than promises?

No. It compiles down to the same promise machinery, so runtime performance is identical. What changes is readability and how easy it is to get error handling right. Speed comes from running independent work in parallel, not from the syntax you pick.

### Why does my async function return a promise instead of a value?

Because that's the contract. Every `async` function wraps its return value in a promise. To get the underlying value you either `await` the call inside another async function or attach a `.then()`. There's no way to synchronously extract the value; the whole point is that it might not be ready yet.

### When should I use Promise.allSettled instead of Promise.all?

Use `allSettled` when you want every result regardless of individual failures — loading several independent widgets, sending a batch of notifications, scraping multiple sources. Use `all` when the operation is all-or-nothing and a single failure should abort the rest.

### How do I handle errors from multiple awaited calls?

If they run sequentially, one `try/catch` around the block catches the first rejection. If they run in parallel with `Promise.all`, wrap the `await Promise.all(...)` in `try/catch` and remember it stops at the first failure. If you need per-item error detail, switch to `allSettled` and inspect each result's `status`.

## Wrapping up

The mental model that sticks: promises are values-in-progress with three states, `async`/`await` is a readable way to consume them, and performance depends on whether you start work in parallel or wait for each step. If you take one habit away, make it this — before you write `await` inside a loop, ask whether those iterations actually depend on each other. Nine times out of ten they don't, and `Promise.all` is waiting for you.

Go find the slowest async path in your current project and check it for a sequential loop. That's usually where the easy win is.