---
name: "Laravel Reverb WebSockets: Real-Time Broadcasting Done Right"
slug: laravel-reverb-websockets
short_description: "A hands-on guide to Laravel Reverb WebSockets: install the server, broadcast events, authorize private channels, and wire up Laravel Echo."
language: en
published_at: 2026-11-20 09:00:00
is_published: true
tags: [laravel, websockets, reverb, broadcasting, real-time]
---

If you've ever refreshed a page just to check whether a notification arrived, you already understand the itch that **Laravel Reverb websockets** are meant to scratch. Reverb is Laravel's own first-party WebSocket server, introduced with Laravel 11, and it lets your server push data to the browser the moment something happens. No polling, no third-party dashboard, no monthly bill from a hosted socket provider.

I've shipped real-time features on top of Pusher, then Soketi, then the old `beyondcode/laravel-websockets` package. Reverb is the first option where the server, the broadcasting layer, and the client all come from the same team and actually feel designed together. Here's how to get it running, and the handful of things that tripped me up so they don't trip you.

## What Reverb actually is

Reverb is a standalone WebSocket server that speaks the **Pusher protocol**. That last part matters more than it sounds. Because it's Pusher-compatible, the client-side story you already know works without modification: Laravel Echo, the `pusher-js` transport, `Echo.private(...).listen(...)`. You point Echo at Reverb instead of a hosted service and carry on.

So there are really three moving parts:

- **The broadcaster**: your Laravel app dispatches events that implement `ShouldBroadcast`.
- **The server**: Reverb, a long-running process that holds the open socket connections.
- **The client**: Laravel Echo in the browser, subscribing to channels and reacting to events.

Get all three talking and you have live updates. Miss one and you get silence, which is exactly the debugging trap most people fall into.

## Installing Reverb

Reverb ships as a Composer package. From the root of a Laravel 11+ project:

```bash
composer require laravel/reverb
php artisan reverb:install
```

The `reverb:install` command does the boring-but-important wiring for you. It publishes the Reverb config, sets `BROADCAST_CONNECTION=reverb` in your `.env`, and drops the relevant credentials in. After it runs you'll have something like this in `.env`:

```bash
BROADCAST_CONNECTION=reverb

REVERB_APP_ID=123456
REVERB_APP_KEY=your-app-key
REVERB_APP_SECRET=your-app-secret
REVERB_HOST="localhost"
REVERB_PORT=8080
REVERB_SCHEME=http
```

Those `REVERB_*` values are the credentials the server and client use to trust each other. Treat the secret like any other secret. It does not belong in your frontend bundle.

Start the server in its own terminal:

```bash
php artisan reverb:start
```

That process needs to stay alive. In production you'd run it under a supervisor (Supervisor, systemd, or a container that restarts it), not in a terminal you're going to close. Add `--debug` while you're developing and Reverb will log every connection and message, which is genuinely useful when things go quiet.

## Broadcasting your first event

An event that broadcasts is just a normal Laravel event that implements `ShouldBroadcast`. Say we want to notify a user when their order status changes:

```php
<?php

namespace App\Events;

use App\Models\Order;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class OrderShipped implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public Order $order)
    {
    }

    public function broadcastOn(): PrivateChannel
    {
        return new PrivateChannel('orders.' . $this->order->user_id);
    }

    public function broadcastAs(): string
    {
        return 'order.shipped';
    }
}
```

Two methods carry the weight here. `broadcastOn()` decides *which* channel the event goes out on: a `PrivateChannel` scoped to a single user, so nobody else can listen in. `broadcastAs()` gives the event a clean name on the wire; without it, Echo would have to listen for the fully-qualified class name, which is ugly and leaks your namespace.

By default the event serializes all public properties. If you only want to send part of the model, add a `broadcastWith()` method returning an explicit array. I almost always do this, since shipping a whole Eloquent model over a socket is wasteful and occasionally leaks fields you'd rather keep server-side.

Dispatch it like any event:

```php
OrderShipped::dispatch($order);
```

If you want the mechanics of events and listeners in general, I wrote about that in [Laravel events and listeners](/blog/laravel-events-listeners). Broadcasting is really just a specialized listener bolted onto that same system.

## The queue detail that bites everyone

Here's the thing the docs mention once and everyone skims past: **broadcasting is queued by default.** When you dispatch a `ShouldBroadcast` event, Laravel doesn't send it to Reverb inline. It pushes a job onto your queue, and a worker sends it to Reverb.

Which means if you have no queue worker running, your event goes nowhere. It's not an error. Nothing throws. The socket connects fine, the client subscribes fine, and the message simply never arrives. I lost the better part of an afternoon to this once.

Two ways out:

- Run a worker: `php artisan queue:work`. This is what you want in production anyway.
- Or, for a quick local check, set `QUEUE_CONNECTION=sync` so events broadcast immediately in-process.

If you want the event to broadcast synchronously regardless of your queue config, implement `ShouldBroadcastNow` instead of `ShouldBroadcast`. Handy for low-volume, latency-sensitive events, though you lose the buffering a queue gives you under load.

## Authorizing private channels

A `PrivateChannel` isn't private by magic. Reverb asks your Laravel app, "is this connection allowed on this channel?" and you answer in `routes/channels.php`:

```php
use App\Models\User;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('orders.{userId}', function (User $user, int $userId) {
    return (int) $user->id === (int) $userId;
});
```

The callback returns `true` to grant access, `false` to deny. The authenticated user comes from your normal session/guard, so this runs through your existing auth. The `{userId}` wildcard is matched against the channel name from `broadcastOn()`. Cast both sides. I've seen `"5" === 5` quietly deny a legitimate user because one side was a string.

Public channels (`Channel` instead of `PrivateChannel`) skip this step entirely. Use them only for data that's genuinely fine for anyone to see.

## Wiring up Laravel Echo on the client

On the frontend, install the client libraries and configure Echo to use the `reverb` broadcaster:

```bash
npm install --save-dev laravel-echo pusher-js
```

```js
import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.Pusher = Pusher;

window.Echo = new Echo({
    broadcaster: 'reverb',
    key: import.meta.env.VITE_REVERB_APP_KEY,
    wsHost: import.meta.env.VITE_REVERB_HOST,
    wsPort: import.meta.env.VITE_REVERB_PORT ?? 80,
    wssPort: import.meta.env.VITE_REVERB_PORT ?? 443,
    forceTLS: (import.meta.env.VITE_REVERB_SCHEME ?? 'https') === 'https',
    enabledTransports: ['ws', 'wss'],
});
```

Note the `VITE_*` variables. Reverb's install step usually adds these to `.env` already, mirroring the server-side values so the client and server agree on host, port, and key.

Now subscribe and listen:

```js
window.Echo.private(`orders.${userId}`)
    .listen('.order.shipped', (e) => {
        console.log('Order shipped:', e.order);
    });
```

The leading dot in `.order.shipped` is deliberate. It tells Echo you're using a custom broadcast name (the one from `broadcastAs()`) rather than a class name. Forget the dot and Echo will look for `App\Events\order.shipped` and hear nothing.

## Pitfalls I keep running into

- **No queue worker.** The single most common "why isn't this working" cause. Run `queue:work` or switch to `ShouldBroadcastNow`.
- **The missing leading dot** in the client `listen()` call when you've set `broadcastAs()`.
- **Server not running or not reachable.** Reverb is a separate process. If it isn't up, or a firewall blocks the port, Echo silently retries forever.
- **Type mismatches in channel auth.** String-vs-int comparisons in `routes/channels.php` deny valid users. Cast explicitly.
- **TLS mismatch in production.** If your site is HTTPS, the socket must be too, or the browser blocks the connection as mixed content. Set `REVERB_SCHEME=https` and terminate TLS in front of Reverb.
- **Confusing Reverb with the old package.** `beyondcode/laravel-websockets` is the community predecessor. Reverb is the official replacement, so don't install both, and follow Reverb's own docs rather than the older ones.

## FAQ

### Is Laravel Reverb free?

Yes. Reverb is open source and ships as a free first-party Laravel package. You self-host it, so your only cost is the server it runs on. There's no per-message or per-connection fee like a hosted service charges.

### Does Reverb work with Laravel Echo and Pusher?

It works with Echo out of the box: set `broadcaster: 'reverb'` and you're done. Reverb speaks the Pusher protocol, so it uses the same `pusher-js` transport under the hood, but you don't need a Pusher account. You're pointing the same client at your own server.

### Why is my broadcast event not firing?

Nine times out of ten, no queue worker is running. Broadcasting is queued by default, so start `php artisan queue:work`, or set `QUEUE_CONNECTION=sync` locally, or implement `ShouldBroadcastNow`. After that, check the Reverb server is actually up and that your client's `listen()` name matches `broadcastAs()`.

### Do I need a queue worker in production?

For broadcasting, effectively yes — running a worker is the intended setup, and it keeps event dispatch from blocking your web requests. Relying on `sync` in production means every broadcast happens inside the request lifecycle, which hurts response times as traffic grows.

## Wrapping up

The recipe is short once it clicks: `composer require laravel/reverb`, run `reverb:install`, start the server with `reverb:start`, dispatch a `ShouldBroadcast` event, authorize the channel in `routes/channels.php`, and listen with `Echo.private(...).listen(...)`. Keep a queue worker running and match your event names on both ends.

Start with a single private channel and a `console.log` on the client. Once you see that event land in the browser, everything else — presence channels, typing indicators, live dashboards — is a variation on the same four pieces. Get the loop working first, then build on it.