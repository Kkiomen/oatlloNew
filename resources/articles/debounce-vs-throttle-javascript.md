---
name: "Debouncing vs Throttling in JavaScript: When to Use Each"
slug: debounce-vs-throttle-javascript
short_description: "Debounce vs throttle in JavaScript explained with runnable code, real use cases, and a cheat sheet so you pick the right one every time."
language: en
published_at: 2026-08-31 09:00:00
is_published: true
tags: [javascript, performance, frontend]
---

The first time I profiled a search box that fired an API request on every keystroke, the network tab looked like a machine gun. Twelve requests for the word "javascript". The fix took two lines, but figuring out *which* fix took me an embarrassingly long afternoon. The whole thing comes down to **debounce vs throttle** — two techniques that both limit how often a function runs, and that people mix up constantly.

They are not the same tool. Choosing wrong doesn't crash anything; it just makes your UI feel slightly broken in a way that's hard to point at. So let's settle it properly, with code you can paste into a console and run.

## What the two words actually mean

Here's the shortest honest definition I can give:

- **Debounce** waits until the activity *stops* for N milliseconds, then fires once. Every new event resets the timer. Last call wins.
- **Throttle** fires at most once per N milliseconds *while* activity keeps happening. It doesn't wait for a lull.

Think of debounce as an elevator. People keep stepping in, and the doors keep re-opening; the elevator only leaves once nobody has entered for a few seconds. Throttle is more like a turnstile that clicks through one person every two seconds no matter how big the crowd is.

That distinction, "wait for quiet" versus "steady drip", is the entire decision. Keep it in your head and the rest is implementation detail.

## Debounce: a runnable implementation

Debounce is built on a single closure variable that holds the pending timer. Each call clears the previous timer and sets a new one.

```javascript
function debounce(fn, wait = 300) {
  let timeoutId;

  return function debounced(...args) {
    clearTimeout(timeoutId);
    timeoutId = setTimeout(() => {
      fn.apply(this, args);
    }, wait);
  };
}

// Try it: only the final call within 300ms actually runs
const log = debounce((value) => console.log("Searching:", value), 300);

log("j");
log("ja");
log("jav");
log("java"); // <-- only this one logs, ~300ms after the last call
```

Walk through why that works. `timeoutId` lives in the closure, so it survives between calls. Every invocation runs `clearTimeout(timeoutId)` first, cancelling whatever was scheduled, then schedules a fresh `setTimeout`. As long as calls keep arriving faster than the wait window, the timer never gets to fire. The moment there's a gap, the last scheduled callback runs.

Note the `fn.apply(this, args)`. That preserves the `this` binding and forwards arguments, which matters if you're debouncing a method or an event handler that reads `event.target`.

### Leading edge, if you need the first call immediately

The version above is "trailing"; it fires after the pause. Sometimes you want the *opposite*: react to the first event instantly, then ignore the rest until things go quiet. That's the leading edge.

```javascript
function debounceLeading(fn, wait = 300) {
  let timeoutId;

  return function (...args) {
    const callNow = !timeoutId;
    clearTimeout(timeoutId);
    timeoutId = setTimeout(() => {
      timeoutId = null;
    }, wait);
    if (callNow) fn.apply(this, args);
  };
}
```

I reach for the leading variant on things like a "load more" button where I want the click to feel instant but don't want double-taps sneaking through.

## Throttle: a runnable implementation

Throttle needs to track *when* it last ran and refuse to run again until enough time has passed. A timestamp is the cleanest way.

```javascript
function throttle(fn, interval = 200) {
  let lastRun = 0;

  return function throttled(...args) {
    const now = Date.now();
    if (now - lastRun >= interval) {
      lastRun = now;
      fn.apply(this, args);
    }
  };
}

// Try it: fires immediately, then at most once every 200ms
const onScroll = throttle(() => console.log("scroll at", Date.now()), 200);
window.addEventListener("scroll", onScroll);
```

This is a leading-edge throttle: the first event fires right away because `lastRun` starts at 0. After that, any call inside the interval is silently dropped.

There's a subtle bug people hit here. If the user stops scrolling *just* before the interval elapses, the final position is never reported, so the trailing call is lost. For scroll handlers that update a progress bar, that missing last frame can leave the bar a few pixels short. A trailing-capable version fixes it with a backup timeout:

```javascript
function throttle(fn, interval = 200) {
  let lastRun = 0;
  let timeoutId;

  return function (...args) {
    const now = Date.now();
    const remaining = interval - (now - lastRun);

    if (remaining <= 0) {
      clearTimeout(timeoutId);
      timeoutId = null;
      lastRun = now;
      fn.apply(this, args);
    } else if (!timeoutId) {
      timeoutId = setTimeout(() => {
        lastRun = Date.now();
        timeoutId = null;
        fn.apply(this, args);
      }, remaining);
    }
  };
}
```

Now the trailing edge is guaranteed. That extra `setTimeout` schedules one final run for the leftover time, so you always get a closing call after the burst. One honest caveat: this simple version fires that trailing call with the arguments it captured when the timer was set, not the very last event's. If you need the latest values on the trailing edge, capture them into a shared variable on every call, or just use Lodash, which already does this.

## Debounce vs throttle: the comparison table

| | Debounce | Throttle |
|---|---|---|
| Fires when | Activity **stops** for N ms | Every N ms **during** activity |
| Frequency during a burst | Zero, then one | Steady, at the rate you set |
| Which call wins | The last one | The first (and periodic ones) |
| Feels | "Wait until they're done typing" | "Sample regularly" |
| Risk if misused | Nothing happens until you pause | Fires more often than you might expect |
| Typical wait value | 250–500 ms | 100–250 ms |

The frequency row is the one that trips people up. During a two-second burst of events, a 300 ms debounce runs your function exactly once. A 200 ms throttle runs it roughly ten times. Same "rate limiting" family, wildly different behavior.

## Use-case cheat sheet

When I'm deciding, I ask one question: *do I care about every intermediate value, or only the final resting state?* Only-the-final means debounce. Regular-samples means throttle.

**Reach for debounce when:**

- Search-as-you-type, where you wait until the user pauses, then hit the API once with the finished query.
- Autosave in an editor: save 1 second after the last keystroke, not on every letter.
- Validating a form field on `input` where you want the final value, not each half-typed one.
- Resize handlers that do heavy layout recalculation, since you only need the size after the drag settles.

**Reach for throttle when:**

- Scroll listeners driving a sticky header, parallax, or a reading-progress bar.
- `mousemove` tracking for drag interactions or a custom cursor.
- Infinite scroll checks that measure how close you are to the bottom.
- Firing analytics or hitting a rate-limited API on a continuous gesture.

A quick gut-check I use: if the words "when they're done" fit the sentence, it's debounce. If "keep it smooth while they're doing it" fits, it's throttle.

One honest caveat on resize: it can go either way. If the resize triggers an expensive chart redraw, I debounce so it only runs once the window settles. If I want the layout to *feel* live while dragging, I throttle it. Context wins over rules.

## You don't have to write it yourself

Both implementations above are small enough to keep in a utils file, and for a lot of projects that's the right call: no dependency, full control. But if you already have Lodash in the bundle, `_.debounce` and `_.throttle` are battle-tested and cover the edge cases (leading, trailing, `maxWait`, cancellation) without you maintaining them:

```javascript
import debounce from "lodash/debounce";
import throttle from "lodash/throttle";

const search = debounce(fetchResults, 300);
const trackScroll = throttle(updateProgress, 100);

// Both expose .cancel(), handy in React cleanup
search.cancel();
```

That `.cancel()` method is genuinely useful. In a React `useEffect` cleanup, or a component unmount, you want to kill a pending debounced call so it doesn't fire against a component that no longer exists. Rolling your own is fine; just remember you'll want cancellation eventually.

If you're building these as reusable typed helpers, the generic signatures get a little fiddly, the kind of thing that pairs well with knowing your utility types cold. There's a companion piece on [TypeScript utility types every developer should know](/blog/typescript-utility-types-every-developer-should-know) if you want to type these properly.

## A React gotcha worth calling out

Defining a debounced function inside a component body recreates it on every render, which resets the timer and quietly defeats the whole point. Wrap it so it persists:

```javascript
import { useMemo } from "react";
import debounce from "lodash/debounce";

function SearchBox({ onSearch }) {
  const debouncedSearch = useMemo(
    () => debounce(onSearch, 300),
    [onSearch]
  );

  return <input onChange={(e) => debouncedSearch(e.target.value)} />;
}
```

`useMemo` keeps the same debounced instance across renders, so the internal timer actually accumulates. I've reviewed more than one PR where the debounce "wasn't working" and this was the reason every single time.

## FAQ

**Is throttle just debounce with different numbers?**

No. They're structurally different. Debounce resets its timer on every call and fires only after silence; throttle tracks elapsed time and fires on a fixed cadence regardless of how many calls come in. No wait value turns one into the other.

**What wait time should I use?**

For debounced search, 250–350 ms feels responsive without spamming your API. For throttled scroll or mousemove, 100–200 ms keeps things smooth at roughly 60fps-friendly rates. These are starting points; profile with real input before committing.

**Do I still need these with modern browsers?**

Often, yes. For scroll specifically, `IntersectionObserver` and `ResizeObserver` can replace throttled listeners entirely and are more efficient, so prefer them when they fit. But for input handling, autosave, and arbitrary rate limiting, debounce and throttle are still the right tools.

**Can I lose the last event with throttle?**

With a pure leading-edge throttle, yes — the trailing call gets dropped if activity stops mid-interval. The trailing-capable version above (the one with the backup `setTimeout`) fixes that. Lodash handles it by default.

## Wrapping up

Strip away the jargon and it's one decision: **debounce** when you only care about the final value after things settle, **throttle** when you want a steady sample during continuous activity. Search boxes and autosave want debounce. Scroll and mousemove want throttle.

My advice — read the two implementations here until the closure logic feels obvious, paste them into a console and watch them behave, then reach for Lodash in real projects so you get cancellation and edge cases for free. Once the elevator-versus-turnstile picture clicks, you'll never mix them up again. If you're wiring these into a larger app, keeping utilities like this organized matters too; the notes on [Node.js project structure](/blog/nodejs-project-structure) cover where helpers like these belong.