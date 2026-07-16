---
slug: laravel-notifications-carousel
type: carousel
language: en
title: "One class, three channels"
topic: laravel
source_type: article
source: laravel-notifications
link: https://oatllo.com/laravel-notifications
publish_at: 2026-10-12 19:00
status: ready
formats: [post, reel]
hashtags: [laravel, php, notifications, slack, backend]
caption: |
  `via()` returns three strings and the controller never learns any of it.

  Mail, Slack and a bell icon from one InvoicePaid class. The traps are
  toArray() being a shared fallback and the sync driver quietly defeating
  ShouldQueue.

  Full guide linked in bio.

  Which channel did you bolt on last?
verified:
  verdict: approved
  at: 2026-07-16 07:16
  fingerprint: ac0dc0a9a5a832c8ca453c155eb40ecdfeccbc86
  checks:
    - "via() returning [mail, slack, database] plus one to{Channel} method each - correct"
    - toArray() is the shared fallback for database AND broadcast, split with an explicit toDatabase() - correct
    - ShouldQueue + Queueable needs no call-site change, and the sync driver still runs inline - correct
    - Notification::route(...)->route(...) on-demand needs no Notifiable model - real API
  notes: |
    Slide 3 is actually MORE correct than the source article, which says my email and my broadcast payloads needed to differ - email uses toMail() and never reads toArray(). The post says the two payloads (database and broadcast), which is right. Worth fixing in the article, not here.
---

## One class sends the same message to mail, Slack and a bell icon.

You stop writing "send an email here" and start writing "announce that
something happened." Each channel decides how to present it.

<!-- slide -->

## via() is the whole routing table

```php
public function via($notifiable): array
{
    return ['mail', 'slack', 'database'];
}
```

Three strings, three short formatting methods. The controller that fires
`$user->notify(new InvoicePaid($invoice))` never learns any of this.

<!-- slide -->

## toArray() is a fallback, not a channel

```php
// database AND broadcast both read this
public function toArray($notifiable): array
{
    return ['invoice_id' => $this->id];
}
```

When the two payloads needed different shapes, one array was the wrong
fight. Add an explicit `toDatabase()` and each channel gets its own.

<!-- slide -->

## Two words move it off the request

```php
class InvoicePaid extends Notification
    implements ShouldQueue
{
    use Queueable;
}
```

No change at the call site. But the `sync` driver still runs inline, so
locally everything looks queued and nothing is.

<!-- slide -->

## Never store the model in the payload

```php
// The row is a snapshot. The model may be
// gone by the time someone opens the bell.
'invoice_id' => $this->invoice->id,
```

Serializing a whole model bloats the `data` column and breaks when the
source row is deleted. Store IDs and the few fields you render.

<!-- slide role="cta" -->

## The recipient does not need a table row

`Notification::route('mail', 'ops@example.com')->route('slack', $url)`
notifies a webhook target with no Notifiable model anywhere.

