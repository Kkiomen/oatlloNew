---
title: "The Law of Demeter"
slug: law-of-demeter
seo_title: "Law of Demeter in PHP - Don't Talk to Strangers"
seo_description: "The Law of Demeter, made practical: don't talk to strangers, avoid long a->b->c->d chains, and apply Tell, Don't Ask. With a clear PHP example."
---

The **Law of Demeter** has a friendlier name: **"don't talk to strangers"**. An object should
only talk to its immediate neighbors - the things it was handed or holds directly - and not
reach through them to touch strangers deep inside. In practice the Law of Demeter is a rule
against long chains like `a->b->c->d`.

## The problem with long chains

Here's an order that reaches deep into its own guts to charge a customer:

```php
$order->getCustomer()->getWallet()->getBalance()->charge($amount);
```

Read what this line *knows*. It knows an order has a customer, that a customer has a wallet,
that a wallet has a balance, and that a balance can be charged. That's four objects' internal
structure baked into one caller. Change any link in that chain - rename `getWallet`, wrap the
balance in something else - and this line breaks, along with every other line that walks the
same path.

Each `->` after the first is a step into a stranger. The caller is now coupled to objects it
was never handed, which is exactly the kind of coupling that makes code hard to change.

## Tell, don't ask

The fix is a mindset called **Tell, Don't Ask**. Instead of *asking* an object for its
internals so you can act on them yourself, *tell* the object what you want and let it handle
its own parts. Push the behavior to where the data lives.

```php
final class Order
{
    public function __construct(private Customer $customer) {}

    public function charge(int $amount): void
    {
        $this->customer->charge($amount);
    }
}

final class Customer
{
    public function __construct(private Wallet $wallet) {}

    public function charge(int $amount): void
    {
        $this->wallet->charge($amount);
    }
}
```

Now the caller just says `$order->charge($amount)`. The order tells the customer, the customer
tells the wallet. Each object only talks to its direct neighbor. If the wallet's internals
change, only `Customer` - its immediate owner - has to know. The chain of knowledge is broken
into short, local hops.

Tests are where this pays off in a way that's easy to miss. To unit-test the chained version
you'd mock a customer that returns a mock wallet that returns a mock balance - a "train
wreck" of nested doubles, each one another thing to keep in sync with real code. The
Tell-Don't-Ask version needs one stub: a customer whose `charge` you assert was called. When
a test starts mocking three layers deep to reach a single value, that test is quietly
reporting a Law of Demeter violation in the code it exercises.

## Common mistake

The mistake is reading the Law of Demeter as "never use two arrows in a row". That's too
literal. Fluent, chainable APIs that keep returning the *same* kind of object - a query
builder, for instance - aren't the target:

```php
$query->where('active', true)->orderBy('name')->limit(10);
```

Each call here returns the same builder, so you're still talking to one neighbor, not walking
through four different strangers. The real smell is a chain that digs through *different*
objects to reach data buried several layers down. That's the reaching the Law of Demeter
warns about.

## FAQ

### Does this mean getters are bad?

Not inherently. The problem isn't a single getter - it's chaining getters to reach through
several objects and then acting on what you find. A getter that hands back a simple value your
caller legitimately owns is fine. Reaching `a->b->c->d` to operate on `d` is the smell.

### Isn't adding all those pass-through methods more code?

It's a little more code, and usually worth it. Each small method keeps knowledge local, so a
change deep in the structure ripples out to one class instead of every caller. You trade a few
short methods for far less coupling.

### How is this related to Tell, Don't Ask?

They're two views of the same idea. Long chains happen because you *ask* an object for its
parts and then do the work yourself. Tell, Don't Ask flips it: hand the request to the object
that owns the data, and the long chain disappears on its own.
