---
slug: laravel-reverb-websockets-carousel
type: carousel
language: en
title: "Reverb broadcasts silently"
topic: laravel
source_type: article
source: laravel-reverb-websockets
link: https://oatllo.com/laravel-reverb-websockets
publish_at: 2026-11-23 19:00
status: ready
formats: [post, reel]
hashtags: [laravel, php, websockets, reverb, webdev]
caption: |
  Broadcasting is queued by default. No worker, no error, no message - the socket connects fine and stays silent.

  Nothing throws, so you debug the client for an hour. Two lines fix it. The missing leading dot in listen() is the other one.

  Full guide linked in bio.

  Which silent failure cost you an afternoon?
---

## Broadcasting is queued by default. No worker means silence.

You dispatch the event. Laravel pushes a job. With no worker, that job sits
there forever. The socket connects, the client subscribes, nothing arrives.

<!-- slide -->

## Two ways out. Both are one line.

```php
// Fix 1: run the worker (prod answer)
php artisan queue:work

// Fix 2: skip the queue entirely
implements ShouldBroadcastNow
```

`ShouldBroadcastNow` sends in-process. Handy for latency-sensitive events, but
you lose the buffering a queue gives you under load.

<!-- slide -->

## The leading dot is not a typo

```js
// broadcastAs() returns 'order.shipped'
Echo.private(`orders.${id}`)
    .listen('.order.shipped', handler);
```

The dot tells Echo this is a custom broadcast name. Forget it and Echo listens
for `App\Events\order.shipped` and hears nothing.

<!-- slide -->

## "5" === 5 denies a real user

```php
Broadcast::channel('orders.{userId}',
  function (User $user, int $userId) {
    return (int) $user->id === (int) $userId;
  });
```

The wildcard arrives as a string. Cast both sides or a legitimate user gets
quietly refused on their own channel.

<!-- slide role="cta" -->

## Get the loop working first

One private channel, one `console.log`. Once you see that event land, presence
channels and live dashboards are the same four pieces.
