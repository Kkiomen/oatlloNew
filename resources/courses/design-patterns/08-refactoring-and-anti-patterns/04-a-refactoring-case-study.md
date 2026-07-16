---
title: "A refactoring case study"
slug: a-refactoring-case-study
seo_title: "PHP Refactoring Case Study - SRP, Strategy, DI"
seo_description: "A full before-and-after: refactor one ugly PHP service with a fat, nested method into clean design using SRP, the Strategy pattern and dependency injection."
---

This is the capstone: one PHP refactoring case study, worked end to end. We take a genuinely
ugly service and reshape it step by step, using the principles and patterns from the whole
course. The smells from this chapter show up in real code here, and each one dissolves under a
named refactoring you have already met. Read it slowly. Every step links back to the lesson it
draws on, and the point isn't the finished class - it's the order of the moves that got us
there.

## The starting point

Here's `OrderProcessor`, a class that has grown for two years. It calculates a discount,
picks a shipping cost, charges a payment provider, saves the order and sends a confirmation -
all in one method.

```php
final class OrderProcessor
{
    public function process(array $order): array
    {
        // discount
        $total = 0.0;
        foreach ($order['items'] as $item) {
            $total += $item['price'] * $item['qty'];
        }
        if ($order['customer']['type'] === 'vip') {
            $total = $total * 0.8;
        } elseif ($order['customer']['type'] === 'member') {
            $total = $total * 0.9;
        }

        // shipping
        if ($order['shipping'] === 'express') {
            $total += $order['weight'] * 2.5;
        } elseif ($order['shipping'] === 'courier') {
            $total += $order['weight'] * 4.0;
        } else {
            $total += $order['weight'] * 1.0;
        }

        // charge
        if ($order['gateway'] === 'stripe') {
            $api = new \Stripe\StripeClient('sk_live_xxx');
            $api->charge($total);
        } elseif ($order['gateway'] === 'paypal') {
            $pp = new \PayPal\Api();
            $pp->pay($total);
        }

        // save
        $db = new \PDO('mysql:host=localhost;dbname=shop', 'root', 'secret');
        $stmt = $db->prepare('INSERT INTO orders (total) VALUES (?)');
        $stmt->execute([$total]);

        // notify
        mail($order['customer']['email'], 'Your order', "Total: {$total}");

        return ['total' => $total];
    }
}
```

It works. It's also a textbook mess. Let's name what's wrong before we touch it.

## Diagnose the smells

Reading against the
[code smells catalog](/course/design-patterns/refactoring-and-anti-patterns/code-smells-catalog):

- **Long method** - one method does five jobs; you have to scroll and it needs comments to
  navigate.
- **Large / God class** - `OrderProcessor` is drifting toward a
  [God Object](/course/design-patterns/refactoring-and-anti-patterns/anti-patterns): pricing,
  payment, persistence and email all live here.
- **Repeated conditionals** - the `if/elseif` chains on customer type, shipping and gateway
  are the shotgun-surgery smell waiting to happen: a new shipping option means editing this
  method.
- **Magic numbers** - `0.8`, `0.9`, `2.5`, `4.0` carry no meaning.
- **Hard-coded dependencies** - `new StripeClient`, `new PDO`, `mail()` are created inside the
  method, so nothing here can be tested without a live database, a real payment provider and an
  SMTP server.

The last point is the killer: because it builds its own collaborators, this class is impossible
to unit-test. That, more than looks, is why we refactor. First, though, a safety net.

## Step 0: pin behavior with a test

Before changing structure we lock in current behavior, exactly as
[refactoring techniques](/course/design-patterns/refactoring-and-anti-patterns/refactoring-techniques)
insists. We can at least test the math - the total for a known order:

```php
public function test_vip_express_total(): void
{
    // 2 x 50 = 100, VIP 20% off = 80, express 3kg x 2.5 = 7.5
    $expected = 87.5;
    // ...assert the calculation part produces 87.5
}
```

With a green test on the calculation, we can refactor the pricing logic and know instantly if
we broke it. Now we move in small steps.

## Step 1: Extract Method to see the shape

The first move is the cheapest:
[Extract Method](/course/design-patterns/refactoring-and-anti-patterns/refactoring-techniques).
We give each block a name without changing any logic yet.

```php
public function process(array $order): array
{
    $total = $this->calculateItemsTotal($order);
    $total = $this->applyDiscount($order, $total);
    $total = $this->addShipping($order, $total);
    $this->charge($order, $total);
    $this->save($total);
    $this->notify($order, $total);

    return ['total' => $total];
}
```

Run the test: still green. The method now reads like a table of contents, and the five
responsibilities are suddenly obvious - which tells us where the class boundaries should fall.
This is [KISS](/course/design-patterns/core-principles/kiss): the same work, just legible.

## Step 2: Extract Class by responsibility (SRP)

Named methods aren't enough - they still all live in one class with one reason to change per
job. We apply the
[Single Responsibility Principle](/course/design-patterns/solid/single-responsibility) via
[Extract Class](/course/design-patterns/refactoring-and-anti-patterns/refactoring-techniques),
moving each concern into its own class:

- `PriceCalculator` - items total plus discount.
- `ShippingCalculator` - shipping cost.
- `PaymentGateway` - charging.
- `OrderRepository` - persistence (as we built in
  [SRP](/course/design-patterns/solid/single-responsibility) and the
  [repository pattern](/course/design-patterns/patterns-in-the-real-world/the-repository-pattern)).
- `OrderMailer` - the confirmation email.

`OrderProcessor` stops *doing* the work and starts *coordinating* it. This is
[separation of concerns](/course/design-patterns/core-principles/separation-of-concerns) at the
class level.

## Step 3: Replace Conditional with Polymorphism (Strategy)

Two `if/elseif` chains remain - discount by customer type and shipping by method. Both branch
on a mode, and both will grow. This is the exact signal to
[replace the conditional with polymorphism](/course/design-patterns/refactoring-and-anti-patterns/refactoring-techniques),
which lands us on the
[Strategy pattern](/course/design-patterns/behavioral-patterns/strategy). Take shipping:

```php
interface ShippingStrategy
{
    public function cost(float $weight): float;
}

final class StandardShipping implements ShippingStrategy
{
    private const RATE = 1.0;

    public function cost(float $weight): float
    {
        return $weight * self::RATE;
    }
}

final class ExpressShipping implements ShippingStrategy
{
    private const RATE = 2.5;

    public function cost(float $weight): float
    {
        return $weight * self::RATE;
    }
}

final class CourierShipping implements ShippingStrategy
{
    private const RATE = 4.0;

    public function cost(float $weight): float
    {
        return $weight * self::RATE;
    }
}
```

Notice the magic numbers became named constants along the way -
[Replace Magic Number with Constant](/course/design-patterns/refactoring-and-anti-patterns/refactoring-techniques).
Adding "same-day" shipping is now a new class, not an edit to a shared method: the
[open/closed principle](/course/design-patterns/solid/open-closed) in practice, and the end of
the shotgun-surgery risk.

## Step 4: Depend on abstractions, inject dependencies

The biggest problem was `new StripeClient`, `new PDO` and `mail()` buried inside the method.
We apply the
[Dependency Inversion Principle](/course/design-patterns/solid/dependency-inversion) - depend
on interfaces, not concretions - and pass collaborators in through the constructor with
[dependency injection](/course/design-patterns/patterns-in-the-real-world/dependency-injection-and-the-container).

```php
final class OrderProcessor
{
    public function __construct(
        private PriceCalculator $prices,
        private ShippingStrategy $shipping,
        private PaymentGateway $gateway,
        private OrderRepository $orders,
        private OrderMailer $mailer,
    ) {}

    public function process(Order $order): OrderResult
    {
        $total = $this->prices->totalFor($order);
        $total += $this->shipping->cost($order->weight);

        $this->gateway->charge($total);
        $this->orders->save($order, $total);
        $this->mailer->sendConfirmation($order, $total);

        return new OrderResult($total);
    }
}
```

One honest note on how this actually goes: the block above is the destination, not a single
commit. In practice you introduce one interface at a time - extract `PaymentGateway`, inject it,
run the test from Step 0, commit - then the next, then the next. Swap all five collaborators in
one move and a red test tells you *something* broke but not *which* one. The whole reason for
the discipline is to never be in that position.

`PaymentGateway` and `OrderRepository` are now interfaces, so Stripe, PayPal, MySQL or a fake
are all just implementations passed in. In a Laravel app the
[container](/course/design-patterns/patterns-in-the-real-world/dependency-injection-and-the-container)
wires these automatically. Notice too that the raw `array $order` became an `Order` object -
we cured the primitive obsession and gave the data a home, as in
[Introduce Parameter Object](/course/design-patterns/refactoring-and-anti-patterns/refactoring-techniques).

## Before and after

The `process` method went from a 40-line tangle of five jobs, three conditional chains, four
magic numbers and three hard-wired dependencies to a six-line coordinator that reads like a
sentence. Every piece is now:

- **Single-purpose** - each class has one reason to change (SRP).
- **Open for extension** - new shipping or payment options are new classes, not edits
  (open/closed, Strategy).
- **Testable** - collaborators are injected, so a unit test passes in fakes and asserts the
  flow with no database, no network, no email server (DIP + DI).
- **Readable** - named methods, named constants, real objects instead of arrays (KISS, DRY).

Crucially, we never did a big rewrite. Each step was small, behavior-preserving and verified by
the test from Step 0 - the discipline from
[refactoring techniques](/course/design-patterns/refactoring-and-anti-patterns/refactoring-techniques).
At no point was the code broken.

## Knowing when to stop

We could keep going - a strategy for discounts too, a factory to build the whole graph, events
on completion. But stop and ask
[YAGNI](/course/design-patterns/core-principles/yagni) and
[when not to use a pattern](/course/design-patterns/patterns-in-the-real-world/when-not-to-use-a-pattern):
does the code we have handle the changes we actually expect? Discounts and shipping vary, so
Strategy earns its place. If a third gateway never arrives, an interface with one
implementation is fine as-is. Refactor toward the design the problem needs, not the most
patterns you can name.

That's the whole course in one exercise: read the smells, name the fix, apply it in small safe
steps, and stop when the design fits. You now have the eye to spot bad design and the tools to
repair it.

## FAQ

### Why not just rewrite the ugly class from scratch?

Because a rewrite throws away every behavior the old code quietly got right, including edge
cases nobody remembers. Small, verified refactoring steps keep the system working the entire
time and let you stop the moment it's good enough. Rewrites are how a Big Ball of Mud becomes a
newer, differently-shaped Big Ball of Mud.

### How did dependency injection make this testable?

The original class built its own `PDO`, Stripe client and mailer, so running it required all
three for real. Once those are interfaces passed into the constructor, a test injects fakes -
an in-memory repository, a fake gateway that records the charge - and asserts the flow in
milliseconds with no external systems.

### Isn't this a lot of small classes for one order?

It's more classes but far less complexity, because each one is small, named and independent -
higher [cohesion](/course/design-patterns/why-design-matters/coupling-and-cohesion), lower
coupling. The one-giant-method version had fewer files and was far harder to change safely.
Count reasons to change, not files.
