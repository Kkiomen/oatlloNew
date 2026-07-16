---
title: "Testing across boundaries"
slug: testing-across-boundaries
seo_title: "Testing Across Boundaries: The Test Pyramid in PHP"
seo_description: "The test pyramid for hexagonal architecture: fast unit tests on a framework-free domain, use-case tests with in-memory adapters, a few integration tests on real ones."
---

A clean architecture is **cheap to test**, and that's one of its biggest and least-discussed
payoffs. When the domain has no framework and no database inside it, you can test the
interesting logic with plain PHP objects - no container, no migrations, no HTTP. This lesson
maps the **test pyramid** onto the architecture you've built: lots of fast unit tests at the
bottom, some use-case tests in the middle, a few slow integration tests at the top.

## The problem: tests that are slow and brittle

If your only tests boot the whole framework, hit a real database, and go through HTTP, they
are slow (seconds each), flaky (a network blip fails them), and hard to write (you set up
half the world to check one rule). Developers stop running them. The rule "an order can't be
paid twice" gets covered by a test that also needs a migrated database and a seeded user -
which is absurd, because the rule doesn't touch the database at all.

The architecture already separated the layers. Testing should use those seams.

## The three layers of the pyramid

Each layer of the app gets a kind of test, and the shape is a pyramid: many at the bottom,
few at the top.

### Base: unit tests on the framework-free domain

The [domain at the center](/course/software-architecture/hexagonal-architecture/the-domain-at-the-center)
has no dependencies - no Eloquent, no HTTP, no clock. So you test it by constructing an
object and calling a method. No mocks, no setup.

```php
<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class OrderTest extends TestCase
{
    public function test_an_order_cannot_be_paid_twice(): void
    {
        $order = new Order(id: 'ord_1');
        $order->addItem('BOOK-1');
        $order->pay();

        $this->expectException(\DomainException::class);
        $order->pay(); // the invariant lives in the entity, so this is all we need
    }
}
```

This test runs in microseconds. There are hundreds of these, one per rule, and they're the
foundation of your confidence. They're only this easy *because* the domain is clean - if
`Order::pay()` reached into the database, this test would need one.

### Middle: use-case tests with in-memory adapters

The [application layer](/course/software-architecture/application-layer-and-use-cases/the-application-layer)
orchestrates the domain and depends on
[ports](/course/software-architecture/hexagonal-architecture/ports) - interfaces like
`OrderRepository`. You test a use case by giving it a **fake** adapter that keeps data in an
array instead of a database.

```php
final class InMemoryOrderRepository implements OrderRepository
{
    /** @var array<string, Order> */
    private array $orders = [];

    public function byId(string $id): Order { return $this->orders[$id]; }
    public function save(Order $order): void { $this->orders[$order->id] = $order; }
}

final class PayOrderHandlerTest extends TestCase
{
    public function test_it_marks_the_order_paid(): void
    {
        $repo = new InMemoryOrderRepository();
        $order = new Order(id: 'ord_1');
        $order->addItem('BOOK-1');
        $repo->save($order);

        (new PayOrderHandler($repo))->handle('ord_1');

        $this->assertTrue($repo->byId('ord_1')->isPaid());
    }
}
```

This tests the *whole use case* - load, run the rule, save - with no database. The
in-memory adapter is possible only because the handler depends on the port interface, not on
Eloquent directly. This is the payoff of
[driving and driven adapters](/course/software-architecture/hexagonal-architecture/adapters):
swap the real one for a fake at the seam.

### Top: a few integration tests on real adapters

You still need to know the *real* Eloquent repository actually saves and loads correctly.
That's an integration test: it hits a real (test) database and exercises the driven adapter
end to end. These are slow and setup-heavy, so you write **few** of them - just enough to
prove each adapter is wired to the outside world correctly. You are not re-testing business
rules here (the base already did that); you're testing the *plumbing*.

There's a trap in the middle layer worth naming: an in-memory adapter that quietly disagrees
with the real one. If your fake returns objects by reference and Eloquent returns fresh
instances, or the fake never enforces a unique constraint the database does, your use-case
tests go green while production breaks. The fix is a **contract test** - one set of
assertions about how any `OrderRepository` must behave, run against both the fake and the
real adapter. The fake earns the right to stand in only by passing the same suite.

## Why the shape matters

Get the proportions right and the suite stays fast and trustworthy:

- **Many unit tests** because they're free to run and cover every rule.
- **Some use-case tests** because they cover how rules combine, still without a database.
- **Few integration tests** because they're slow and only need to check the adapters, not
  the logic.

An upside-down pyramid - everything tested through the database and HTTP - is the common
mistake. It's slow, flaky, and tests the same rule many times over while covering the
edge cases poorly. Push the coverage down to the fast layers the architecture handed you.

## FAQ

### What is the test pyramid?

A guideline for the shape of a test suite: many fast, isolated unit tests at the base, fewer
integration tests in the middle, and very few slow end-to-end tests at the top. The wide
base keeps the suite fast and reliable.

### Why is a clean domain cheap to test?

Because a framework-free domain has no dependencies to set up. You create an object and call
a method - no container, database or HTTP. Rules become one-line tests that run in
microseconds, so you can afford hundreds of them.

### What is an in-memory adapter?

A fake implementation of a port (like a repository) that stores data in an array instead of
a real database. Because use cases depend on the port interface, you can inject the fake in
tests and exercise the whole use case fast, without any infrastructure.
