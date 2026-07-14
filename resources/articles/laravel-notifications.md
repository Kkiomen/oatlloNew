---
name: "Laravel Notifications: Mail, Slack, and Database Channels"
slug: laravel-notifications
short_description: "A hands-on guide to Laravel notifications: send the same message over mail, Slack, and database, queue it, and read it back in the UI."
language: en
published_at: 2026-12-21 09:00:00
is_published: true
tags: [laravel, notifications, php, slack]
---

I used to send transactional email straight from a controller with the `Mail` facade, and it worked fine right up until product asked for the same "your invoice is ready" message to also land in Slack and show up as a bell icon in the app. Suddenly one message meant three code paths, three formats, and three places to forget something. Laravel notifications exist for exactly this: one notification class, many delivery channels, one call to send it.

This guide walks through the parts I actually use in production. We'll build a notification, send it over mail, Slack, and the database, queue it so it doesn't block the request, and then read it back in a dashboard. I'll flag the pitfalls that cost me time, because a few of them are not obvious from the docs.

## What a notification really is

A notification is a single PHP class that describes *what* happened and lets each channel decide *how* to present it. The event that fires it doesn't care whether the user prefers email or Slack. That decision lives in one place.

Generate one with Artisan:

```bash
php artisan make:notification InvoicePaid
```

That drops a class in `app/Notifications/InvoicePaid.php`. The two methods that matter are `via()`, which returns the list of channels, and one `to{Channel}` method per channel.

The built-in channels ship under recognizable names: `mail`, `database`, `broadcast`, `vonage` (SMS), and `slack`. You return those strings from `via()`.

## Sending to a user

Any model that receives notifications needs the `Notifiable` trait. On a fresh Laravel install, `App\Models\User` already has it:

```php
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use Notifiable;
}
```

That trait gives the model a `notify()` method and the relationships you'll use later to read stored notifications. Sending is then a one-liner:

```php
$user->notify(new InvoicePaid($invoice));
```

Need to hit a whole collection? The facade takes a notifiable, an array, or a whole collection and iterates for you:

```php
use Illuminate\Support\Facades\Notification;

Notification::send($admins, new InvoicePaid($invoice));
```

## The mail channel

Add `mail` to `via()` and write a `toMail()` method returning a `MailMessage`. This is the fluent builder, not raw Blade, though you can drop into a custom view if you want.

```php
use Illuminate\Notifications\Messages\MailMessage;

public function via(object $notifiable): array
{
    return ['mail'];
}

public function toMail(object $notifiable): MailMessage
{
    return (new MailMessage)
        ->subject("Invoice #{$this->invoice->number} paid")
        ->greeting("Thanks, {$notifiable->name}!")
        ->line('We received your payment. Here is your receipt.')
        ->action('View invoice', url("/invoices/{$this->invoice->id}"))
        ->line('No action is needed on your side.');
}
```

By default the mail channel sends to the notifiable's `email` attribute. If your model stores the address elsewhere, add a `routeNotificationForMail()` method to the model and return the right value.

## The database channel

This is the one people underuse. The `database` channel writes a row you can render as an in-app notification feed, no third-party service required.

First create the table. In current Laravel the command is:

```bash
php artisan make:notifications-table
php artisan migrate
```

Then implement `toArray()`, which defines the JSON payload stored in the `data` column:

```php
public function via(object $notifiable): array
{
    return ['mail', 'database'];
}

public function toArray(object $notifiable): array
{
    return [
        'invoice_id' => $this->invoice->id,
        'number'     => $this->invoice->number,
        'amount'     => $this->invoice->amount,
    ];
}
```

A note that tripped me up early: `toArray()` is the shared fallback. The database channel will use `toDatabase()` if you define it, and fall back to `toArray()` otherwise. If you also broadcast, both channels read the same `toArray()` unless you split them. When my email and my broadcast payloads needed to differ, I added an explicit `toDatabase()` so each channel got its own shape.

Store only what you need to rebuild the UI later. Don't stuff a whole Eloquent model in there. The row is a snapshot; the model might be deleted by the time someone opens the bell menu.

## The Slack channel

Slack is its own package. Install it and you get a dedicated builder:

```bash
composer require laravel/slack-notification-channel
```

Point the notifiable at a Slack webhook (or use a bot token setup) by adding `routeNotificationForSlack()` to the model, then implement `toSlack()`:

```php
use Illuminate\Notifications\Slack\SlackMessage;

public function via(object $notifiable): array
{
    return ['slack'];
}

public function toSlack(object $notifiable): SlackMessage
{
    return (new SlackMessage)
        ->text("Invoice #{$this->invoice->number} was just paid.")
        ->headerBlock('Payment received')
        ->contextBlock(fn ($block) =>
            $block->text("Amount: {$this->invoice->amount}")
        );
}
```

The whole point holds up here: `via()` returns three strings, and each `to{Channel}` method shapes the same event for its medium. The controller that fires the notification never learns any of this.

## Don't send synchronously: queue it

Here's the part that separates a demo from production. Sending mail and a Slack call inside the web request means the user waits for both network round trips before the page responds. On a bad day, Slack being slow makes your app feel slow.

Fix it by implementing `ShouldQueue`. Laravel then pushes the whole notification onto your queue automatically:

```php
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class InvoicePaid extends Notification implements ShouldQueue
{
    use Queueable;

    // ... via(), toMail(), toArray(), toSlack()
}
```

That's it. No change at the call site. `$user->notify(...)` now dispatches a job instead of blocking. You do need a running worker (`php artisan queue:work`) and a real queue driver in production — the `sync` driver still runs inline and defeats the purpose.

Need finer control? Say you want Slack to fire right away but the email to wait a few minutes. Define a `withDelay()` method on the notification and return a per-channel array of delays. For most apps, though, "queue the whole thing" is the right default and you never touch this.

Notifications play nicely with the rest of the queue system. If you're already coordinating background work, the same patterns from [job batching](/blog/laravel-job-batching) apply here too, and notifications are a natural companion to [events and listeners](/blog/laravel-events-listeners): fire an event, let a listener send the notification.

## Reading database notifications back

The stored notifications come off the `Notifiable` model as relationships. Two you'll use constantly:

```php
// All notifications, newest first
foreach ($user->notifications as $notification) {
    echo $notification->data['number'];
}

// Just the unread ones, perfect for a badge count
$count = $user->unreadNotifications->count();
```

Marking as read is a method on each notification record:

```php
$user->unreadNotifications->markAsRead();
```

Call that when the user opens the notification panel. The `data` you get back is exactly the array you returned from `toArray()`, decoded for you.

## On-demand notifications

Sometimes you need to notify someone who isn't a user in your database — a webhook target, a one-off admin address. Use on-demand routing:

```php
Notification::route('mail', 'ops@example.com')
    ->route('slack', $webhookUrl)
    ->notify(new InvoicePaid($invoice));
```

No `Notifiable` model required. The `route()` calls tell each channel where to deliver.

## Pitfalls I've actually hit

- **The `sync` queue driver silently defeats `ShouldQueue`.** In local dev everything looks queued but runs inline. Set a real driver before you benchmark anything.
- **Storing whole models in `toArray()`.** It serializes the entire model, bloats the `data` column, and breaks when the source row is later deleted. Store IDs and the few display fields you need.
- **Forgetting `toArray()` is the fallback for multiple channels.** If database and broadcast payloads need different shapes, add explicit `toDatabase()` and `toBroadcast()` methods instead of fighting one array.
- **Assuming `email` is the mail address.** If your model uses a different column, mail silently goes nowhere until you add `routeNotificationForMail()`.
- **Queued notifications and deleted models.** A queued notification that references a model deleted before the worker runs will fail. Pass what you need into the constructor, or handle the missing model in the channel methods.

## FAQ

### How do I send one notification to many users at once?

Use `Notification::send($users, new YourNotification(...))` with the facade. It accepts a collection and iterates for you, so you skip the manual `foreach`. Functionally it does the same work as calling `->notify()` on each model; the facade just reads cleaner when the recipient is a set rather than one user.

### Can a single notification go to email, Slack, and the database together?

Yes — that's the core feature. Return `['mail', 'slack', 'database']` from `via()` and implement `toMail()`, `toSlack()`, and `toArray()`. Each channel formats the same event independently.

### Do I need a queue for notifications?

Not to make them work, but you want one in production. Without `ShouldQueue`, mail and Slack calls run inside the web request and the user waits for every network round trip. Add `implements ShouldQueue` plus the `Queueable` trait and run a worker.

### Where does the database channel store notifications?

In a `notifications` table you create with `php artisan make:notifications-table` and `php artisan migrate`. The payload from `toArray()` is saved in a JSON `data` column, and you read rows via `$user->notifications` and `$user->unreadNotifications`.

## Wrapping up

The mental shift with Laravel notifications is small but real: stop thinking "send an email here" and start thinking "announce that something happened, and let each channel decide." One `InvoicePaid` class, a `via()` that returns `['mail', 'slack', 'database']`, and three short formatting methods gave me email, Slack, and an in-app feed with no duplication.

Start with mail and database — they cover most needs and require no extra service. Add `implements ShouldQueue` before you ship so nothing blocks the request. Reach for Slack when a human genuinely needs a real-time ping. And keep your `toArray()` payload lean, because future-you will be reading those rows long after the original models are gone.