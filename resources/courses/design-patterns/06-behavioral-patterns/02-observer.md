---
title: "Observer Pattern"
slug: observer
seo_title: "Observer Pattern in PHP - Publish/Subscribe Events"
seo_description: "Learn the Observer pattern in PHP: a subject notifies subscribers when something happens, keeping the event and the reactions loosely coupled."
---

## What is the Observer pattern?

The **Observer** pattern lets one object (the *subject*) tell a list of other objects (the
*observers*) that something happened, without knowing or caring who they are. It's the
foundation of publish/subscribe and of every "event" system you've used.

## The problem: the subject knows too much

A user just registered. Now you must send a welcome email, add them to the newsletter, and
log the signup. The naive version wires all of that into the registration code:

```php
final class Registration
{
    public function register(string $email): void
    {
        // ...create the user...

        (new Mailer())->sendWelcome($email);
        (new Newsletter())->subscribe($email);
        (new AuditLog())->record("registered: $email");
    }
}
```

`Registration` now depends on the mailer, the newsletter and the log. Every new reaction
(send to CRM, trigger analytics) means editing this class - and it's tightly coupled to
three unrelated concerns. That's low cohesion and high
[coupling](/course/design-patterns/why-design-matters/coupling-and-cohesion) in one method.

## The observer version

Define what a subscriber looks like, then let the subject keep a list of them:

```php
interface Observer
{
    public function handle(string $email): void;
}

final class Registration
{
    /** @var Observer[] */
    private array $observers = [];

    public function subscribe(Observer $observer): void
    {
        $this->observers[] = $observer;
    }

    public function register(string $email): void
    {
        // ...create the user...

        foreach ($this->observers as $observer) {
            $observer->handle($email);
        }
    }
}
```

Each reaction becomes its own observer:

```php
final class SendWelcomeEmail implements Observer
{
    public function handle(string $email): void
    {
        // send the email
    }
}

$registration = new Registration();
$registration->subscribe(new SendWelcomeEmail());
$registration->subscribe(new AddToNewsletter());
$registration->register('sam@example.com');
```

`Registration` no longer knows what happens after a signup - only that it should announce
it. Adding, removing or reordering reactions never touches the subject. PHP even ships a
built-in `SplSubject`/`SplObserver` interface pair with the same shape.

## When to use it

Use Observer when one event should trigger several independent reactions, and you want the
source of the event decoupled from the handlers. Domain events, UI updates when data
changes, and webhook-style hooks are all textbook cases. If there's exactly one reaction
that will never change, a direct call is simpler and clearer.

Worth knowing before you scatter logic into observers: in a plain PHP request the observers
run synchronously, in-process. A welcome email sent straight from an observer blocks the
response until the mail server answers. That's why mature apps usually have observers enqueue
the slow work rather than perform it inline - the observer stays the announcement point, not
the place the job actually runs.

## Common mistake

Letting observers throw and take down the subject, or depending on the order they run. Each
observer should be independent: one failing shouldn't break the others, and reaction B
shouldn't silently rely on reaction A having run first. If order truly matters, that's a
sign the work belongs in one place, not scattered across observers.

## FAQ

### What is the difference between the observer and mediator pattern?

Observer is one-to-many broadcasting: a subject tells many listeners, one-directionally.
Mediator (later in this chapter) is many-to-many coordination: several peers talk *through*
a central object that routes messages in every direction.

### Is Observer the same as an event dispatcher?

An event dispatcher is Observer scaled up. Instead of one subject holding its own list, a
central dispatcher maps event types to listeners, so any object can publish and any object
can subscribe. Laravel's events work this way. The core idea - announce, don't call - is
identical.

### Should the subject pass itself or just the data?

Either works. Passing plain data (like the email above) keeps observers loosely coupled and
easy to test. Pass the whole subject only when observers genuinely need to read more of its
state - and be aware it ties them to the subject's shape.
