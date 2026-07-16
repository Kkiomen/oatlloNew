---
slug: async-javascript-error-handling-carousel
type: carousel
language: en
title: "Async error handling"
topic: javascript
source_type: article
source: async-javascript-error-handling
link: https://oatllo.com/async-javascript-error-handling
publish_at: 2026-09-02 19:00
status: ready
formats: [post, reel]
hashtags: [javascript, nodejs, async, webdev, frontend]
caption: |
  A rejected promise is not an exception on a stack. It is a value nobody may be listening to.

  That is the whole problem. Empty catch, forEach with await, fetch that never
  rejects on a 500. Full write-up linked in bio.

  Which of these shipped to your prod?
---

## An empty catch block hid a broken webhook for six hours.

`catch (e) {}` does not handle an error. It hides one. Sitting in a payment
retry loop, it hid a failing handler until a customer emailed.

<!-- slide -->

## fetch does not reject on 500

```javascript
const res = await fetch(url);
if (!res.ok) {
  throw new Error(`Failed: ${res.status}`);
}
```

It only rejects on network-level failures. Without `res.ok`, a `500` sails
straight through your try block looking like success.

<!-- slide -->

## await inside forEach escapes everything

```javascript
messages.forEach(async (msg) => {
  await deliver(msg); // nobody catches this
});
```

The callback returns a promise `forEach` throws away. Your outer try/catch
returned long before it settled.

<!-- slide -->

## all is fail-fast. allSettled never rejects.

```javascript
const r = await Promise.allSettled(
  messages.map(deliver)
);
r.filter((x) => x.status === 'rejected');
```

Need every value? `all`. Rather show three-quarters of a dashboard than a blank
error page? `allSettled`.

<!-- slide -->

## Cleanup belongs in finally, once

```javascript
const t = setTimeout(() => c.abort(), ms);
try {
  return await fetch(url, { signal });
} finally {
  clearTimeout(t);
}
```

Success, timeout or an unrelated throw - the timer clears. Node also ships
`AbortSignal.timeout(5000)` for the plain case.

<!-- slide role="cta" -->

## The tripwire, not the strategy

```javascript
process.on('unhandledRejection', (reason) => {
  console.error(reason);
  process.exit(1);
});
```

If this fires in production, a rejection escaped every handler you wrote. That
is a bug to fix, not a place to recover. Full guide in bio.
