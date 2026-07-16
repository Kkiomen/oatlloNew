---
name: "The JavaScript Event Loop Explained"
slug: javascript-event-loop-explained
short_description: "How the call stack, task queue, and microtask queue order your code, and why setTimeout(0) runs after a resolved Promise."
language: en
published_at: 2027-03-05 09:00:00
is_published: true
tags: [javascript, async, nodejs, performance]
---

I once spent an afternoon staring at a log that printed in an order I was sure was impossible. A `setTimeout(fn, 0)` was firing *after* a `.then()` callback that I'd scheduled later in the code. My mental model said "0 milliseconds means first." The runtime disagreed, and the runtime was right. This article is the model I wish I'd had that afternoon: the call stack, the two queues that feed it, and the rules that decide who runs next.

If you can predict the output of a handful of `console.log` calls, you understand the event loop. That's the whole test, and we'll get there.

## The runtime has one thread and one stack

JavaScript executes on a single thread. There's one **call stack** — a pile of function calls — and the engine runs whatever is on top until the stack is empty. When you call `a()` which calls `b()`, `b` sits on top of `a`; when `b` returns, it pops off, and `a` continues. Synchronous, boring, predictable.

The interesting part is what happens when nothing is on the stack. The engine doesn't just sit there. It asks: is there queued work waiting to run? That question, asked over and over, *is* the event loop. Run everything on the stack, pull the next piece of pending work, repeat.

Here's the part that trips people up: `setTimeout`, `fetch`, DOM events, and `Promise.then` don't run their callbacks immediately. They hand a callback to the runtime and return right away. The callback runs later, when the stack is clear and it's that callback's turn. "Later" and "its turn" are two different things, and the difference is the whole game.

## Two queues, not one

There isn't one line of waiting callbacks. There are two, and they have different priority.

- **The task queue** (also called the macrotask queue) holds callbacks from `setTimeout`, `setInterval`, I/O, and UI events like clicks. One task runs per loop iteration.
- **The microtask queue** holds callbacks from resolved Promises (`.then`, `.catch`, `.finally`), `await` continuations, `queueMicrotask`, and `MutationObserver`.

The rule that governs them is short and worth memorizing: **after each task, the engine drains the entire microtask queue before touching the next task.** Not one microtask — all of them, including any new microtasks that get queued while draining.

So the priority order, whenever the stack goes empty, is:

1. Run the current synchronous code to completion.
2. Drain every microtask.
3. Run exactly one task (macrotask).
4. Drain every microtask again.
5. (Browser only) render if needed.
6. Repeat.

That single asymmetry — *all* microtasks versus *one* macrotask — explains almost every surprising ordering you'll ever hit.

## Why setTimeout(0) loses to Promise.then

Back to my afternoon of confusion. Watch this:

```js
console.log('1: sync start');

setTimeout(() => console.log('2: timeout'), 0);

Promise.resolve().then(() => console.log('3: promise'));

console.log('4: sync end');
```

The output is:

```
1: sync start
4: sync end
3: promise
2: timeout
```

Both `console.log('1')` and `console.log('4')` are plain synchronous code, so they run first, top to bottom. Nothing surprising yet.

Now the stack is empty. Before the engine will even look at the task queue, it drains microtasks — and the resolved Promise's callback is a microtask. So `3: promise` prints. Only after the microtask queue is empty does the engine pull one task: the timeout callback, `2: timeout`.

`setTimeout(fn, 0)` does not mean "run now." It means "queue this as a task, as soon as possible." A resolved Promise's `.then` is a microtask, and microtasks jump the whole task queue. The `0` was never a lie. It just measures a different line than the one I thought I was standing in.

## await is just microtasks wearing a suit

`async/await` isn't a separate mechanism. Everything after an `await` is a microtask continuation. This example catches almost everyone:

```js
async function run() {
  console.log('A');
  await null;          // suspends here; the rest becomes a microtask
  console.log('B');
}

console.log('start');
run();
console.log('end');
Promise.resolve().then(() => console.log('promise'));
```

Output:

```
start
A
end
B
promise
```

`run()` executes synchronously up to the `await`. `await null` wraps its value in a resolved Promise and schedules the rest of the function as a microtask, then hands control back. So `console.log('end')` runs while `run` is parked. When the stack clears, microtasks drain in order: `B` was queued before the `promise` callback, so `B` prints first.

The mental shortcut: **an `await` splits a function in two.** Everything before it is synchronous. Everything after it is a microtask that gets queued the moment the awaited value settles.

## Starving the loop

Because the engine drains *all* microtasks before doing anything else, a microtask that keeps queuing more microtasks never lets go. The task queue starves. Rendering starves. The tab freezes.

```js
function starve() {
  Promise.resolve().then(starve); // queues another microtask, forever
}
starve();
setTimeout(() => console.log('I will never run'), 0);
```

The timeout callback is a task, and the engine won't run a task until the microtask queue is empty — which never happens here. The page locks up, and in a browser you'll eventually get an unresponsive-page warning.

The same shape written with `setTimeout` recursion behaves completely differently:

```js
function breathe() {
  setTimeout(breathe, 0); // queues a task
}
breathe();
setTimeout(() => console.log('I run fine'), 0);
```

This churns forever too, but it's polite. Each iteration is one task, so between iterations the engine drains microtasks and, in a browser, gets a chance to render and handle input. I learned this the hard way chunking a big CSV parse across a Promise chain to "keep the UI responsive" — the spinner never even painted. The rule I've used since: **if you're chunking heavy work, yield with `setTimeout` or `MessageChannel`, never with a Promise chain.** A Promise chain won't give the browser a paint slot.

## Where rendering fits (browser only)

In the browser, painting is not free-floating — it's wedged into the loop at a specific point. Roughly: run a task, drain microtasks, then *if the browser decides it's time*, run `requestAnimationFrame` callbacks and paint. That's typically about 60 times a second on a 60Hz display, not after every microtask.

This is why work stuffed into microtasks can block a frame while the same work spread across tasks doesn't. It's also why `requestAnimationFrame` is the right home for animation updates: it runs right before paint, so you mutate the DOM at the last moment with fresh layout.

```js
// Wrong home for visual work — this fights the frame budget
element.style.left = x + 'px';

// Right home — runs just before the browser paints
requestAnimationFrame(() => {
  element.style.left = x + 'px';
});
```

If you set a style, force a layout read, set another style, and force another read in a tight loop, you get layout thrashing — the browser recomputes geometry repeatedly within a single frame. Batching writes into a `requestAnimationFrame` callback is how you avoid it.

## Node is the same idea with more rooms

Node.js runs the same call-stack-plus-microtask model, but its macrotask side is built on libuv and split into named **phases**: timers, pending callbacks, poll (I/O), check (`setImmediate`), and close callbacks. The loop walks these phases in order, and microtasks drain between each one just like in the browser.

Two Node-specific things are worth carrying in your head:

- `process.nextTick` has its **own** queue that drains *before* the Promise microtask queue. It's even higher priority than `.then`. Overusing it can starve the loop the same way runaway microtasks do in the browser.
- `setImmediate(fn)` runs in the check phase, after I/O. The classic gotcha is that `setTimeout(fn, 0)` versus `setImmediate(fn)` at the top level have **non-deterministic** relative order, because it depends on how fast the loop reaches the timers phase. Inside an I/O callback, though, `setImmediate` reliably runs first.

```js
// Ordering here is NOT guaranteed
setTimeout(() => console.log('timeout'), 0);
setImmediate(() => console.log('immediate'));

// But inside an I/O callback, setImmediate always wins:
const fs = require('fs');
fs.readFile(__filename, () => {
  setTimeout(() => console.log('timeout in IO'), 0);
  setImmediate(() => console.log('immediate in IO')); // this prints first
});
```

If you're writing code that runs in both environments, lean on the guarantees that hold everywhere — sync code first, then microtasks, then macrotasks — and treat `process.nextTick` and `setImmediate` as Node-only tools you reach for deliberately.

## A quick reference

| Source | Queue | When it runs |
| --- | --- | --- |
| `Promise.then` / `await` continuation | Microtask | Before the next task; drained fully |
| `queueMicrotask` | Microtask | Same as above |
| `process.nextTick` (Node) | nextTick (highest) | Before Promise microtasks |
| `setTimeout` / `setInterval` | Task (macrotask) | One per loop turn |
| `setImmediate` (Node) | Task (check phase) | After I/O, per loop turn |
| `requestAnimationFrame` (browser) | Render step | Just before paint, ~60/s |

## FAQ

### Does setTimeout(fn, 0) run after 0 milliseconds?

No. It queues `fn` as a task to run as soon as possible, but never before the current synchronous code finishes and never before the microtask queue is empty. Browsers also clamp nested timeouts to a minimum (about 4ms after several levels of nesting), so even the delay isn't truly zero.

### What's the difference between a microtask and a macrotask?

They're callbacks in two different queues. Microtasks (Promises, `await`, `queueMicrotask`) drain completely after each macrotask. Macrotasks (`setTimeout`, I/O, UI events) run one at a time, with a full microtask drain between each. Microtasks always have priority when the stack is empty.

### Can too many Promises freeze the page?

Yes, if they form a chain that keeps queuing new microtasks with no gap. The engine won't move on to tasks or rendering until the microtask queue empties, so an unbounded microtask loop locks the UI. Split long-running work across `setTimeout` or `MessageChannel` tasks so the loop can breathe.

### Is the event loop different in Node.js and the browser?

The core rule is identical: synchronous code, then all microtasks, then one macrotask, repeat. Node adds phased macrotask handling via libuv plus two extras with no browser equivalent — `process.nextTick` (drains before Promise microtasks) and `setImmediate` (check phase). The browser adds a render step that Node has no reason to.

## The takeaway

The event loop isn't magic and it isn't a scheduler you fight — it's one rule applied relentlessly: empty the stack, drain every microtask, run one task, repeat. Once that ordering is in your fingers, "why did this log print second?" stops being a mystery and becomes arithmetic.

Next time output surprises you, don't reach for the debugger first. Label each `console.log` with its source — sync, microtask, or task — and read them back in that priority order. Nine times out of ten you'll predict the log before you run it, which is the moment you actually own this model.
