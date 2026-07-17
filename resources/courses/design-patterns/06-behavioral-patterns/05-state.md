---
title: "State Pattern"
slug: state
seo_title: "State Pattern in PHP - Replace Status Conditionals"
seo_description: "Learn the State pattern in PHP: give each state its own class so an object changes behavior as its state changes, instead of scattered if/else."
---

## What is the State pattern?

The **State** pattern lets an object change its behavior when its internal state changes -
so it appears to switch class. Each state becomes its own object, and the behavior for that
state lives there instead of in conditionals sprinkled everywhere.

## The problem: conditionals on a status field

An order moves through a lifecycle - pending, paid, shipped, cancelled - and what each
action does depends on where it is. The straightforward version checks the status in every
method:

```php
final class Order
{
    public string $status = 'pending';

    public function pay(): void
    {
        if ($this->status === 'pending') {
            $this->status = 'paid';
        } elseif ($this->status === 'cancelled') {
            throw new LogicException("Cannot pay a cancelled order");
        }
        // ...
    }

    public function ship(): void
    {
        if ($this->status === 'paid') {
            $this->status = 'shipped';
        } elseif ($this->status === 'pending') {
            throw new LogicException("Pay before shipping");
        }
        // the same status ladder, repeated in every method...
    }
}
```

The rules for each state are smeared across every method. Adding a "refunded" state means
hunting down and editing every one of these `if` ladders.

## The state version

Define a contract for what an order can do, then one class per state that answers for itself:

```php
interface OrderState
{
    public function pay(Order $order): void;
    public function ship(Order $order): void;
}

final class Pending implements OrderState
{
    public function pay(Order $order): void  { $order->setState(new Paid()); }
    public function ship(Order $order): void { throw new LogicException("Pay first"); }
}

final class Paid implements OrderState
{
    public function pay(Order $order): void  { throw new LogicException("Already paid"); }
    public function ship(Order $order): void { $order->setState(new Shipped()); }
}
```

The order holds a state object and delegates to it - no conditionals:

```php
final class Order
{
    public function __construct(private OrderState $state = new Pending()) {}

    public function setState(OrderState $state): void { $this->state = $state; }

    public function pay(): void  { $this->state->pay($this); }
    public function ship(): void { $this->state->ship($this); }
}

$order = new Order();
$order->pay();   // Pending -> Paid
$order->ship();  // Paid -> Shipped
```

Each state knows its own allowed transitions and hands the order its next state. Adding a
new state is a new class, not edits scattered across every method - the same
[Open/Closed](/course/design-patterns/solid/open-closed) win you saw with [Strategy](/course/design-patterns/behavioral-patterns/strategy).

## When to use it

Use State when an object's behavior depends on a status that changes over time, and you have
the same status checks repeated across several methods. Order and payment lifecycles,
document workflows (draft/review/published), and connection states (open/closed) fit well.
If there are only two states and one method, a boolean is plenty - don't build a class
hierarchy for a light switch.

One thing the toy example hides: real orders come back from a database as a status *string*,
so you need a single place that maps `'paid'` to a `Paid` object when you load one. Keep that
mapping in one function. If it's scattered, a legacy or unknown status string fails in a
different, confusing way each place it's rebuilt - the one spot that can't map cleanly should
fail loudly, in one spot.

## Common mistake

Putting the transition rules in the wrong place - having the `Order` decide which state
comes next with a `switch`, which just reintroduces the conditional you were removing. Let
each state own its transitions. The other trap is giving states access to too much of the
context; pass them only what they need to do their job.

## FAQ

### What is the difference between the state and strategy pattern?

The code shapes are twins - both delegate to an injected interface - but the intent is
opposite. A strategy is chosen from outside and usually stays put; the object doesn't change
it. A state changes *itself*: states typically decide the next state and swap it in, driving
the object through a lifecycle. Strategy is about *how*; State is about *where in the flow*.

### Where do the transitions belong - in the state or the context?

In the states, almost always. Each state knows which moves are legal from itself, so it's the
natural owner of "what happens next". If the context decides, you're back to a central
conditional and lose the pattern's benefit.

### Isn't a database status column enough?

The column stores *which* state you're in; the pattern decides *how each state behaves*. You
often keep both: persist a status string, and rebuild the matching state object when you load
the order. The pattern replaces the behavior conditionals, not the stored value.
