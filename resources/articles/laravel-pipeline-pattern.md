---
name: "Using Laravel's Pipeline for Clean Sequential Logic"
slug: laravel-pipeline-pattern
short_description: "How Laravel's Pipeline class works, when to reach for it over a plain array of callbacks, and when it's over-engineering a service method."
language: en
published_at: 2027-06-30 09:00:00
is_published: true
tags: [laravel, php, architecture, patterns]
---

I inherited a `checkout()` method once that was 340 lines long. Validate the cart, re-check stock, apply the coupon, calculate tax, charge the card, decrement inventory, fire the emails - all in one method, all sharing local variables, all wrapped in a single try/catch that nobody dared touch. Every new "small" requirement added another `if` block somewhere in the middle. Adding a fraud check meant reading the whole thing to find a safe place to slot it in.

The tool that untangled it was already sitting in the framework: the same `Pipeline` class Laravel uses to run your HTTP middleware. If you've ever written a middleware `handle($request, Closure $next)`, you already know 90% of this. This is how to use that same mechanism for your own sequential business logic, where it pays off, and where it's just ceremony.

## What the Pipeline actually is

The Pipeline is a class that passes an object through a list of "stages" one at a time. Each stage gets the object, does something with it, and calls the next stage. When you write middleware, the framework runs `Illuminate\Pipeline\Pipeline` under the hood - your request flows through every middleware, then hits the controller, then the response flows back out. There's nothing HTTP-specific about it. You can push any object through any list of stages.

The public API is three chained methods:

```php
use Illuminate\Support\Facades\Pipeline;

$result = Pipeline::send($order)
    ->through([
        EnsureStockAvailable::class,
        ApplyCoupon::class,
        CalculateTax::class,
        ChargePayment::class,
    ])
    ->then(fn ($order) => $order);
```

`send()` is the object you're passing (Laravel's docs call it the "passenger"). `through()` is the ordered list of pipes. `then()` is the final callback that receives whatever comes out the other end. There's also `thenReturn()`, which is sugar for `->then(fn ($passenger) => $passenger)` when you just want the object back.

The `Pipeline` facade landed in Laravel 9.x. On older apps you can resolve it from the container with `app(Illuminate\Pipeline\Pipeline::class)` and call `->send()->through()->then()` - same object, no facade.

## Each pipe is a class with one method

A pipe is any class with a `handle` method that takes the passenger and a `Closure`:

```php
namespace App\Pipes\Checkout;

use Closure;
use App\Exceptions\OutOfStockException;

class EnsureStockAvailable
{
    public function handle($order, Closure $next)
    {
        foreach ($order->lines as $line) {
            if ($line->product->stock < $line->quantity) {
                throw new OutOfStockException($line->product);
            }
        }

        return $next($order);
    }
}
```

The shape is the whole point. Every pipe does its bit and then calls `$next($order)` to hand control to the next pipe. That `return` matters - the return value bubbles back up through every pipe that came before, which is how the response half of middleware works.

If you'd rather not stringify class names, `through()` also accepts instances. That's how you inject constructor dependencies:

```php
Pipeline::send($order)
    ->through([
        new EnsureStockAvailable(),
        new ChargePayment($this->stripe),
    ])
    ->thenReturn();
```

Or let the container build them - passing the class string means Laravel resolves each pipe out of the service container, so type-hinted constructor dependencies get injected for free. That's the version I reach for most.

## Why not just an array of callbacks?

This is the fair question, and the honest answer is: for a plain sequence where every step always runs, you often don't need the Pipeline. A `foreach` over closures does the job:

```php
$order = collect($steps)->reduce(fn ($order, $step) => $step($order), $order);
```

The Pipeline earns its keep the moment a step needs to do something *around* the rest of the chain instead of just *before* it. Because each pipe decides whether and when to call `$next`, it controls everything downstream. Three things fall out of that which a flat array can't do cleanly:

**Short-circuiting.** A pipe can skip the rest by simply not calling `$next`:

```php
class SkipTaxForExemptOrders
{
    public function handle($order, Closure $next)
    {
        if ($order->customer->is_tax_exempt) {
            return $order; // rest of the pipeline never runs
        }

        return $next($order);
    }
}
```

**Wrapping.** A pipe can run code before *and* after everything downstream, because `$next(...)` is a normal function call that returns:

```php
class WrapInTransaction
{
    public function handle($order, Closure $next)
    {
        return DB::transaction(function () use ($order, $next) {
            return $next($order); // whole rest of the pipeline runs inside the transaction
        });
    }
}
```

Put that first in `through()` and every pipe after it is transactional. Try expressing "wrap the next N steps in a DB transaction" with a flat array of callbacks - you can't, because a callback in a `reduce` only sees its own step.

Order bites you here, though. If `ChargePayment` runs inside that transaction and a pipe after it throws, the database rolls back but Stripe already took the money - external side effects don't care about your rollback. Keep the irreversible steps last, or move them after the transaction closes. This is the one place the "just reorder the array" freedom will hurt you if you're not paying attention.

**Decorating the result.** A logging or timing pipe can measure the entire remaining chain:

```php
class LogDuration
{
    public function handle($order, Closure $next)
    {
        $start = microtime(true);
        $result = $next($order);
        logger()->info('checkout pipeline', ['ms' => (microtime(true) - $start) * 1000]);

        return $result;
    }
}
```

That before/after control flow is exactly why middleware is built on this and not on a plain loop.

## Passing extra parameters to a pipe

Sometimes a pipe needs configuration - which discount code, which threshold. You append parameters to the class string with a colon, comma-separated, the same syntax route middleware uses (`throttle:60,1`):

```php
Pipeline::send($order)
    ->through([
        'App\Pipes\Checkout\ApplyCoupon:SUMMER25',
        'App\Pipes\Checkout\RequireMinimumTotal:50',
    ])
    ->thenReturn();
```

Those extra values arrive as trailing arguments after the `Closure`:

```php
class RequireMinimumTotal
{
    public function handle($order, Closure $next, $minimum)
    {
        if ($order->total() < (float) $minimum) {
            throw new BelowMinimumException($minimum);
        }

        return $next($order);
    }
}
```

Note the values come through as strings (it's a parsed string, after all), so cast them. For anything richer than scalars, build the pipe as an instance and pass real objects to its constructor instead.

## A pipeline that reads query filters from the request

The checkout is the dramatic example, but the pattern I use most often is building an Eloquent query from optional request parameters. You know the mess: a controller with eight `if ($request->has('...'))` blocks each tacking on a `where`. Each filter becomes a pipe, and the passenger is the query builder itself.

```php
namespace App\Http\Filters;

use Closure;

class Status
{
    public function handle($query, Closure $next)
    {
        if ($status = request('status')) {
            $query->where('status', $status);
        }

        return $next($query);
    }
}

class PriceBetween
{
    public function handle($query, Closure $next)
    {
        if ($min = request('min_price')) {
            $query->where('price', '>=', $min);
        }
        if ($max = request('max_price')) {
            $query->where('price', '<=', $max);
        }

        return $next($query);
    }
}
```

The controller stays flat and honest:

```php
public function index()
{
    $products = Pipeline::send(Product::query())
        ->through([
            \App\Http\Filters\Status::class,
            \App\Http\Filters\PriceBetween::class,
            \App\Http\Filters\Search::class,
        ])
        ->thenReturn()
        ->paginate(20);
}
```

Adding a filter is now adding a class and a line, never editing a growing method. Each filter is unit-testable in isolation, and if a filter's param isn't in the request it just calls `$next` unchanged. This is the same idea as Spatie's `laravel-query-builder`, minus the package - handy when you want the mechanics without another dependency, or when your filters do more than map a param to a `where`.

## Invokable pipes

If `handle` bothers you, tell the Pipeline to use `__invoke` instead with `via()`:

```php
Pipeline::send($order)
    ->via('__invoke')
    ->through([UppercaseSku::class, TrimNotes::class])
    ->thenReturn();
```

```php
class UppercaseSku
{
    public function __invoke($order, Closure $next)
    {
        $order->sku = strtoupper($order->sku);

        return $next($order);
    }
}
```

`via()` just names the method the Pipeline calls on each pipe - `handle` is the default. It's a small readability win for a data-transformation chain where "handle" doesn't describe anything. I don't feel strongly about it; pick one convention per project and stick to it so people know what to grep for.

## When it's over-engineering

The Pipeline is not free. It scatters one method across a folder of tiny classes, and the control flow now hops between files instead of reading top-to-bottom. That's a real cost when someone new is trying to understand the flow.

Reach for it when:

- You have **five or more genuinely independent steps**, and steps get added or reordered over time.
- Steps need to **short-circuit, wrap, or run conditionally** - the array-of-callbacks version starts fighting you.
- The steps are **reusable** across contexts (the same tax pipe in checkout, in quote preview, in an admin recalculation).

Leave it alone when:

- The logic is **three linear steps that will never grow**. A private method each and a plain call reads better.
- Steps are **tightly coupled and share a pile of local state**. Forcing them into pipes means either fattening the passenger with scratch fields or threading a context object around, and you've traded a long method for indirection without buying clarity.
- You'd be creating a class **per line of code**. If a pipe is one statement and always runs, it's abstraction for its own sake.

The honest heuristic: use the Pipeline when the *shape* of your logic is "a list of steps that will change," not merely "some steps that happen in order." My 340-line checkout qualified because every quarter brought a new step and someone always needed to slot one in the middle. A three-line formatter would not have.

## FAQ

### Does the Pipeline run steps asynchronously or in parallel?
No. It's strictly sequential and synchronous - each pipe finishes and calls `$next` before the following one starts. That's the point: it's for *ordered* logic where step three depends on step two having run. If you want concurrency, that's a queue/job problem, not a pipeline one.

### How do I stop the pipeline early on a failure?
Two ways. Throw an exception from a pipe and let it propagate (best when it's a genuine error - out of stock, payment declined). Or, for a non-error early exit, just `return` the passenger without calling `$next` - everything downstream is skipped and your `then()` receives whatever that pipe returned.

### Can I use the Pipeline outside of an HTTP request?
Yes. It's a plain class in `Illuminate\Pipeline` with no request dependency. It works fine in console commands, queued jobs, or scheduled tasks - anywhere you're pushing an object through ordered steps. The filter example reads from `request()` only because that example happens to filter by query params; the Pipeline itself doesn't care.

### What's the difference between `then()` and `thenReturn()`?
`then(Closure $callback)` runs your callback with the final passenger and returns whatever the callback returns - use it when you want to do one last thing (paginate, wrap in a resource, dispatch an event). `thenReturn()` is shorthand for returning the passenger unchanged. Same pipeline, different landing.

## Where to take it next

Start small: find one method in your app with a stack of sequential `if` blocks that keeps growing - a checkout, an onboarding flow, a request-driven query - and pull each step into a pipe. Keep the pipes stateless and side-effect-honest, and let the passenger carry everything they need. If after extracting three pipes it reads worse than the original method, you had linear logic, not a pipeline, and reverting is the right call. The mechanism is the easy part; knowing which methods actually deserve it is the skill.
