---
title: "Bridge - Split Abstraction From Implementation"
slug: bridge
seo_title: "Bridge Pattern in PHP: Split Abstraction & Detail"
seo_description: "Learn the bridge pattern: separate an abstraction from its implementation so both vary independently and avoid a class explosion. PHP example."
---

## What is the bridge pattern?

The **bridge** pattern separates an abstraction from its implementation so the two can vary
on their own. Instead of baking one into the other, you connect them with a reference - the
"bridge" - and let each side grow independently. The abstraction holds an implementation and
delegates the concrete work to it, so adding an option on either side never touches the other.

## The problem it solves

Suppose you send notifications, and you have two things that both change: the *kind* of
notification (alert, reminder) and the *channel* it goes through (email, SMS). If you make
one subclass per combination, they multiply fast:

```text
EmailAlert, SmsAlert, EmailReminder, SmsReminder...
```

Two kinds times two channels is four classes. Add a "push" channel and you need six. Add a
"promo" kind and you need nine. Every new option on either side multiplies with the other.

## The bridge

Split the two axes. The notification (abstraction) holds a channel (implementation) and
delegates the actual sending to it:

```php
interface Channel
{
    public function send(string $message): void;
}

final class EmailChannel implements Channel
{
    public function send(string $message): void { /* ...email... */ }
}

final class SmsChannel implements Channel
{
    public function send(string $message): void { /* ...sms... */ }
}

abstract class Notification
{
    public function __construct(protected Channel $channel) {}

    abstract public function notify(): void;
}

final class Alert extends Notification
{
    public function notify(): void
    {
        $this->channel->send('ALERT: something needs attention');
    }
}
```

Now the two sides combine at runtime, and you only ever add *one* class per new option:

```php
$alert = new Alert(new SmsChannel());
$alert->notify(); // an alert, sent over SMS
```

A new channel is one new `Channel` class - every notification kind can use it immediately.
A new notification kind is one new `Notification` subclass - it works over every channel.
Two lists that grow independently instead of one grid that multiplies.

## When it helps

Reach for a bridge when you notice two (or more) dimensions that each vary, and subclassing
would force you to cover every combination. Common signs:

- Class names that read like a matrix (`RedCircleButton`, `BlueSquareButton`).
- A "kind" and a "backend" that change for different reasons - shapes and rendering APIs,
  reports and export formats, documents and storage drivers.

If only one side ever varies, you don't need a bridge - it's just extra indirection. This
is [YAGNI](/course/design-patterns/core-principles/yagni): add the second axis only when it
actually shows up.

## Common mistake

The bridge is often confused with the
[adapter](/course/design-patterns/structural-patterns/adapter), but they solve opposite
timing problems. An adapter fixes a mismatch *after the fact* between things that already
exist. A bridge is a design decision you make *up front* to keep two dimensions apart so
they don't multiply. Don't reach for it to patch an incompatible class - that's the
adapter's job.

## FAQ

### Bridge vs strategy?

They look identical in code - an object holding an interface it delegates to. The
difference is intent and scale. Strategy (a behavioral pattern in the next chapter) swaps
*one algorithm*; bridge separates a *whole abstraction* from a *whole implementation* so
both can grow. Same structure, bigger purpose.

### Isn't this just composition over inheritance?

Yes, it's a specific application of it. The bridge is what
[composition over inheritance](/course/design-patterns/core-principles/composition-over-inheritance)
looks like when you have exactly two dimensions that would otherwise multiply into a class
grid.

### How is it different from a decorator?

A [decorator](/course/design-patterns/structural-patterns/decorator) stacks layers of
behavior on one interface. A bridge connects two *separate* hierarchies (kinds and
channels) so they vary independently. Decorator adds; bridge separates.
