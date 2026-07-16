---
title: "Coupling and cohesion"
slug: coupling-and-cohesion
seo_title: "Coupling and Cohesion in PHP Explained (Before & After)"
seo_description: "Low coupling and high cohesion are the two lenses for judging any design. See what each means with a small before/after PHP example you can follow."
---

Almost every design idea in this course comes back to two words: **coupling** and
**cohesion**. Learn to see them and you can judge a design without knowing any pattern
yet.

## The two lenses

**Coupling** is how tightly two parts depend on each other. When you change part A, do
you also have to change part B? If yes, they're tightly coupled. You want **low
coupling**: parts that can change independently.

**Cohesion** is how focused a single part is. Does this class or method do one clear
job, or a bit of everything? You want **high cohesion**: each unit has one reason to
exist.

A short way to remember it: **low coupling between things, high cohesion inside a
thing.**

## Why you want both

Tight coupling makes change dangerous. Touch one class and unrelated features break,
because everything reaches into everything else.

Low cohesion makes code hard to understand. A class that handles orders, sends email
and writes to a log has three jobs tangled together, and you can't reason about one
without the others.

Low coupling and high cohesion go together: when each unit does one thing, it needs
less from its neighbours, so the connections between units get thinner too.

## Before: tangled together

Here one class does everything, and reaches straight into the details of sending email.

```php
class OrderService
{
    public function placeOrder(array $items): void
    {
        // job 1: save the order
        $total = array_sum(array_column($items, 'price'));
        // ...save to database...

        // job 2: send a confirmation, wired directly to a mail server
        $smtp = new SmtpConnection('mail.example.com', 25);
        $smtp->connect();
        $smtp->send('Your order total is ' . $total);
    }
}
```

This class has **low cohesion** (it saves orders *and* knows SMTP details) and **tight
coupling** (it depends on one exact mail transport). Switch to a different mail provider
and you edit order code. Test placing an order and you need a real mail server.

## After: separated and connected loosely

Give email its own focused unit, and let the order class depend on an idea, not a
detail.

```php
interface Mailer
{
    public function send(string $message): void;
}

class OrderService
{
    public function __construct(private Mailer $mailer) {}

    public function placeOrder(array $items): void
    {
        $total = array_sum(array_column($items, 'price'));
        // ...save to database...

        $this->mailer->send("Your order total is {$total}");
    }
}
```

Now `OrderService` has **one job** (higher cohesion) and depends only on the `Mailer`
interface, not a specific transport (**lower coupling**). You can swap SMTP for an API,
or pass a fake mailer in a test, without touching order logic.

A practical tell: coupling usually shows up in your test setup before it shows up in any
diagram. If a unit test needs a live mail server, a database and three other objects
just to run one method, the code is telling you it depends on too much. The "after"
version tests with a one-line fake. That gap between setups is coupling, made visible.

## Common mistake

Don't confuse "fewer classes" with "simpler design". Splitting the mailer out added a
class and an interface, yet the result is easier to change, not harder. Cohesion is
about each unit doing one thing well - sometimes that means *more* small units, not
fewer big ones.

## FAQ

### What is the difference between coupling and cohesion?

Coupling is *between* units - how much they depend on each other (you want it low).
Cohesion is *inside* a unit - how focused it is (you want it high). Different directions,
same goal: code that's safe to change.

### Is all coupling bad?

No. Parts have to talk to each other, so some coupling is normal and necessary. The goal
is to avoid *unnecessary* or *tight* coupling, especially depending on exact details
that are likely to change.

### How do I know if a class has low cohesion?

Try to describe it in one sentence. If you need "and" - "it saves orders *and* sends
email *and* logs" - it's probably doing too much and cohesion is low.
