---
title: "YAGNI - You Aren't Gonna Need It"
slug: yagni
seo_title: "YAGNI Principle in PHP: You Aren't Gonna Need It"
seo_description: "The YAGNI principle explained: building for speculative future needs is a cost, not an investment. Where over-engineering hides, with a clear PHP example."
---

The **YAGNI principle** - "You Aren't Gonna Need It" - warns against a very common habit:
building features, options and flexibility for a future that hasn't arrived and often never
does. Build what today's requirement needs, and no more.

## Speculative generality is a cost

It feels responsible to plan ahead. "We'll probably need multiple payment providers, so
I'll make this configurable now." But every bit of flexibility you add before it's needed
is code you have to write, read, test and maintain - to support a requirement that may
never come, or may come in a shape you didn't predict.

Design smells even have a name for this: **speculative generality** - structure added for
imagined cases that never materialize. It's not free insurance. It's cost you pay today for
a guess about tomorrow.

## An over-engineered version

You need to send a welcome email when someone signs up. That's the whole requirement.
Here's the "future-proof" version:

```php
interface NotificationChannel
{
    public function send(string $to, string $message): void;
}

final class EmailChannel implements NotificationChannel { /* ... */ }
final class SmsChannel implements NotificationChannel { /* ... */ }
final class SlackChannel implements NotificationChannel { /* ... */ }
final class PushChannel implements NotificationChannel { /* ... */ }

final class NotificationManager
{
    /** @param NotificationChannel[] $channels */
    public function __construct(private array $channels) {}

    public function dispatch(string $to, string $message): void { /* ... */ }
}
```

Four channels, an interface, a manager - to send one email. Three of those channels have no
caller. Nobody asked for SMS, Slack or push. This is a lot of surface area defending against
a future that isn't in any ticket.

## What today actually needs

```php
final class WelcomeMailer
{
    public function send(string $email, string $name): void
    {
        // send the welcome email
    }
}
```

That's it. It does exactly what the requirement asks. If a real second channel shows up
later, you'll extract an interface *then* - and you'll do it knowing the actual second case,
not a guessed one. Refactoring toward an abstraction when you have two real examples is easy.
Guessing the abstraction from one is where teams get it wrong.

The real bill for the speculative version isn't the hour spent writing `SmsChannel`. It's
that the dead class now answers every "find all implementations" search, sits in every
grep for `NotificationChannel`, and makes anyone reading the code stop to ask whether SMS is
actually wired up somewhere. Unused abstractions tax navigation for the whole team, quietly,
for as long as they live in the tree.

## Common mistake

The mistake is treating YAGNI as an excuse for sloppy code. It isn't. YAGNI says don't build
*unrequested features and flexibility*. It does not say skip validation, skip tests, or write
a mess. Clean, simple code for today's requirement is the goal - not a pile of hooks for
requirements nobody has.

YAGNI is the natural partner of [KISS](/course/design-patterns/core-principles/kiss): the
simplest thing that works is usually also the thing that doesn't try to predict the future.

## FAQ

### Isn't planning ahead a good thing?

Planning is good; pre-building is not. Thinking about where the code might go helps you keep
it changeable. Actually writing the SMS channel nobody asked for is different - that's code
you own now for a maybe.

### What if I'm sure we'll need it soon?

"Soon" and "sure" are exactly the feelings YAGNI is skeptical of. If it's genuinely in the
next sprint with a real requirement, build it then. If it's a hunch, wait. Adding it later
when the need is concrete is cheaper than maintaining a wrong guess in the meantime.

### Doesn't leaving it out mean painful rewrites later?

Rarely, if your code is simple. Simple, well-factored code is easy to extend when the real
need appears. It's the speculative structure that causes painful rewrites, because you have
to undo the wrong guess before you can build the right thing.
