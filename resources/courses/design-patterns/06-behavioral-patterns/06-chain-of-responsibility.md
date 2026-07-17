---
title: "Chain of Responsibility Pattern"
slug: chain-of-responsibility
seo_title: "Chain of Responsibility in PHP - Middleware Pipeline"
seo_description: "Learn the Chain of Responsibility pattern in PHP: pass a request through a chain of handlers until one handles it - the idea behind middleware."
---

## What is the Chain of Responsibility pattern?

The **Chain of Responsibility** pattern passes a request along a line of handlers. Each
handler either deals with it or passes it to the next one. The sender doesn't know which
handler will end up doing the work. This is the pattern behind HTTP middleware.

## The problem: one method deciding everything

You need to process a request through several checks - is it authenticated, is it within
rate limits, is the payload valid - before doing the real work. Cramming it into one method
gets ugly fast:

```php
final class Handler
{
    public function handle(Request $request): Response
    {
        if (! $request->hasValidToken()) {
            return new Response(401, 'Unauthorized');
        }
        if ($request->isRateLimited()) {
            return new Response(429, 'Too many requests');
        }
        if (! $request->payloadIsValid()) {
            return new Response(422, 'Invalid');
        }
        return $this->doTheWork($request);
    }
}
```

Every new check bloats this method, the order is baked in, and you can't reuse a single
check on its own. The concerns are glued together.

## The chain version

Give each handler a `next` link and a single responsibility. It either stops the chain or
passes the request along:

```php
abstract class Middleware
{
    private ?Middleware $next = null;

    public function setNext(Middleware $next): Middleware
    {
        $this->next = $next;
        return $next; // return next so calls can be fluent
    }

    public function handle(Request $request): Response
    {
        return $this->next?->handle($request)
            ?? new Response(200, 'OK');
    }
}

final class Authenticate extends Middleware
{
    public function handle(Request $request): Response
    {
        if (! $request->hasValidToken()) {
            return new Response(401, 'Unauthorized');
        }
        return parent::handle($request); // pass to the next link
    }
}
```

Build the chain once, then send a request in one end:

```php
$auth = new Authenticate();
$rate = new RateLimit();

$auth->setNext($rate); // Authenticate -> RateLimit -> (final OK)

$response = $auth->handle($request);
```

Each check is its own small class, reusable and testable alone. Reordering the pipeline or
inserting a new step is a change to how you *wire* the chain, not a rewrite of a giant
method. [Laravel's HTTP middleware](/course/design-patterns/patterns-in-the-real-world/patterns-you-already-use-in-laravel) is this pattern, wired for you.

## When to use it

Use it when a request should be handled by one of several handlers and you don't want the
sender to know which, or when you want a configurable pipeline of steps that can short-circuit
early. Middleware, validation pipelines, and event-handling chains are the common cases. For
a fixed sequence that never varies and always runs fully, a plain series of method calls is
simpler.

The linked-list version above only passes forward, which is worth noticing: a handler acts,
then delegates, and never sees what came back. The middleware you'll meet in practice adds a
twist - each link calls the next and gets the response, so it can also do work on the way
*out* (set a header, time the request, catch an exception). Same routing idea, but the "after"
half is what makes a request logger or a response wrapper possible, and the pure forward chain
can't express it.

## Common mistake

Forgetting to call the next handler, so the chain silently stops one step in - the hardest
kind of bug to spot, because nothing errors, work just doesn't happen. Make the "pass along"
path explicit (like `parent::handle()` above) and be deliberate about when a handler
short-circuits versus continues.

## FAQ

### What is the difference between chain of responsibility and the decorator pattern?

Both wrap a series of objects, but their goals differ. A
[decorator](/course/design-patterns/structural-patterns/decorator) always calls the thing it
wraps and *adds* to the result - every layer runs. A chain may *stop* at any link: a handler
can deal with the request and never call the next. Decorator enhances; Chain routes and can
short-circuit.

### Does every request have to be handled?

No. A request can travel the whole chain and reach the end unhandled - it's up to your design
whether that's an error, a default response, or simply nothing. Decide explicitly what the
end of the chain does.

### Is this the same as middleware?

Yes. HTTP middleware is the best-known form of Chain of Responsibility: each middleware
inspects the request, optionally handles it (returning early), or calls the next one. If
you've written Laravel middleware, you've used this pattern.
