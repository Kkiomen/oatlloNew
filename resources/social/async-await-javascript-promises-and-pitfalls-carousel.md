---
slug: async-await-javascript-promises-and-pitfalls-carousel
type: carousel
language: en
title: "Async await pitfalls"
topic: javascript
source_type: article
source: async-await-javascript-promises-and-pitfalls
link: https://oatllo.com/async-await-javascript-promises-and-pitfalls
publish_at: 2026-08-21 19:00
status: ready
formats: [post]
hashtags: [javascript, async, promises, nodejs, webdev]
caption: |
  Fetching 40 profiles in a loop took 8 seconds. One line fixed it.

  await inside a for loop waits for each request before starting the next.
  Independent work belongs in Promise.all, not a loop.

  Full guide linked in bio.

  Where's the slowest async path in your project?
verified:
  verdict: approved
  at: 2026-07-16 07:01
  fingerprint: 07df3aa3eb98ec63d927383f91a97eb81663ec93
  checks:
    - 40 profiles / 8 seconds / under one second traced to the article opener and parallel section
    - loop and Promise.all snippets match the article loadSequential/loadParallel verbatim in substance
    - the all-rejects-instantly vs allSettled-never-rejects claim matches the article and matches real JS semantics
    - the async-controls-when-you-wait-not-how-many-run line is quoted correctly from the article pitfall list
  notes: |
    Caption says one line fixed it, which is rhetorical - the fix is two lines - but no factual claim is wrong.
---

## Fetching 40 profiles took 8 seconds

It worked. It was just waiting for every request to come back before starting
the next one.

<!-- slide -->

## The loop blocks on every iteration

```javascript
for (const id of ids) {
  users.push(await getUser(id)); // waits here
}
```

Sequential time is the sum of all 40 round-trips.

<!-- slide -->

## Start them all, then wait once

```javascript
const promises = ids.map((id) => getUser(id));
return Promise.all(promises);
```

Parallel time is about as long as the slowest single request. Eight seconds
became under one.

<!-- slide -->

## await in a loop only when steps depend

`async`/`await` controls *when* you wait, not *how many* things run at once.
Parallelism comes from starting the promises before you await them.

<!-- slide -->

## Promise.all throws away what succeeded

```javascript
// one broken widget blanks the page
await Promise.all(widgets);

// partial success survives
await Promise.allSettled(widgets);
```

`all` rejects the instant any input rejects. `allSettled` never rejects.

<!-- slide role="cta" -->

## Go find your slowest async path

Check it for a sequential loop. Nine times out of ten the iterations don't
depend on each other, and `Promise.all` is waiting.
