---
slug: debounce-throttle-carousel
type: carousel
language: en
title: "Debouncing vs throttling in JavaScript"
topic: javascript
source_type: article
source: debounce-vs-throttle-javascript
link: https://oatllo.com/debounce-vs-throttle-javascript
publish_at: 2026-08-04 19:00
status: ready
hashtags: [javascript, performance, frontend, webdev, react]
caption: |
  Type "java" in your search box and your API just took four requests.

  Debounce waits for quiet, then fires once. Throttle lets one through on a
  timer, the whole time. That difference is the entire decision. The rest is
  implementation detail.

  Full write-up linked in bio.

  Which one does your search box use right now, honestly?
---

## Your search box fires on every keystroke

Type "java" and your API just took four requests for one search.

<!-- slide -->

## Only the last one should have run

```javascript
log("j");
log("ja");
log("jav");
log("java"); // only this one runs
```

Debounce waits for the typing to stop, then fires once.

<!-- slide -->

## It is one variable in a closure

```javascript
function debounce(fn, wait = 300) {
  let id;
  return function (...args) {
    clearTimeout(id);
    id = setTimeout(() =>
      fn.apply(this, args), wait);
  };
}
```

Every call cancels the last one. The gap is what lets it fire.

<!-- slide -->

## Throttle answers a different question

Debounce is zero calls during a burst, then one. Throttle is a steady drip at
the rate you set, all the way through it.

<!-- slide -->

## So it is not a style choice

Search, autosave, validation: you want the final value. Scroll, mousemove,
drag: you want it smooth while it happens.

<!-- slide role="cta" -->

## The sentence that decides it

If "when they're done" fits, it's debounce. If "keep it smooth while they're
doing it" fits, it's throttle.
