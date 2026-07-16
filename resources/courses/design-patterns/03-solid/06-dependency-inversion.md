---
title: "Dependency Inversion Principle (DIP)"
slug: dependency-inversion
seo_title: "Dependency Inversion Principle in PHP (DIP) Explained"
seo_description: "The Dependency Inversion Principle says depend on abstractions, not concretions. Learn DIP in PHP by injecting an interface instead of a concrete class."
---

## What is the Dependency Inversion Principle?

The **Dependency Inversion Principle** is the "D" in SOLID. It has two parts:

> High-level modules should not depend on low-level modules. Both should depend on
> abstractions.
>
> Abstractions should not depend on details. Details should depend on abstractions.

The practical takeaway is short: **depend on interfaces, not on concrete classes.** Your
important business logic (the "high-level" part) shouldn't be nailed to a specific database,
mailer, or HTTP client (the "low-level" details).

## Depending on a concrete class

Here a service creates and uses a concrete mailer directly:

```php
final class SmtpMailer
{
    public function send(string $to, string $body): void
    {
        // ... talk to an SMTP server
    }
}

final class OrderService
{
    private SmtpMailer $mailer;

    public function __construct()
    {
        $this->mailer = new SmtpMailer(); // hard-wired dependency
    }

    public function placeOrder(string $email): void
    {
        // ... save the order
        $this->mailer->send($email, 'Thanks for your order!');
    }
}
```

`OrderService` is now welded to `SmtpMailer`. You can't send through a different provider
without editing `OrderService`, and you can't test it without actually sending mail. The
high-level class (placing orders) depends on a low-level detail (SMTP).

## Depending on an abstraction

Introduce an interface and **inject** it through the constructor:

```php
interface Mailer
{
    public function send(string $to, string $body): void;
}

final class SmtpMailer implements Mailer
{
    public function send(string $to, string $body): void
    {
        // ... talk to an SMTP server
    }
}

final class OrderService
{
    public function __construct(private Mailer $mailer) {}

    public function placeOrder(string $email): void
    {
        // ... save the order
        $this->mailer->send($email, 'Thanks for your order!');
    }
}
```

`OrderService` now depends only on the `Mailer` contract. You can pass a `SmtpMailer` in
production, a different provider later, or a fake in tests - all without touching
`OrderService`. The dependency has been "inverted": both the service and the SMTP class now
depend on the `Mailer` abstraction, and the abstraction depends on neither.

There's a subtlety in *where* that `Mailer` interface belongs, and it's the part that makes
the word "inversion" earn its keep. The contract should be owned by the code that needs
sending - the order side - not filed away next to `SmtpMailer`. When the high-level policy
defines the interface and the low-level detail implements it, the arrow of dependency flips:
the SMTP class now bends to the order module's terms instead of the reverse. Drop the
interface in the mailer package and you'll still get testable code, but you haven't actually
inverted anything - you've just added a layer.

Handing a class its dependencies from the outside like this is called **dependency
injection**, and it's the everyday technique that makes DIP practical. You'll go deeper into
it - and how a framework's container wires it up for you - in
Chapter 7.

## Common mistake

Confusing dependency inversion with dependency injection. Injection is *how* you pass a
dependency in; inversion is *what* you pass - an abstraction rather than a concrete type.
Injecting a concrete `SmtpMailer` still couples you to SMTP. The win comes from depending on
the `Mailer` interface.

## Common mistake

Wrapping everything in an interface out of habit. If a dependency has exactly one
implementation and will never be swapped or faked - a small value object, a date helper - an
interface just adds indirection. Invert dependencies at the boundaries that change or need
faking (I/O, external services), not everywhere.

## FAQ

### What is the Dependency Inversion Principle?

It says high-level code should depend on abstractions (interfaces), not on concrete
low-level classes. That keeps your core logic independent of specific databases, mailers or
other details.

### What's the difference between dependency inversion and dependency injection?

Inversion is the principle - depend on abstractions. Injection is the technique - pass
dependencies in from outside rather than creating them internally. You typically use
injection to achieve inversion.

### Where should I apply DIP?

At boundaries that vary or need testing in isolation - databases, external APIs, mailers,
file systems. Stable, single-implementation helpers usually don't need an interface.
