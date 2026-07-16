---
title: "DTOs and mapping"
slug: dtos-and-mapping
seo_title: "DTO in PHP: Data Transfer Objects and Mapping"
seo_description: "What Data Transfer Objects are and why they matter: carrying data across boundaries, keeping Eloquent models and arrays out of the domain, with simple PHP mapping."
---

Data crosses boundaries constantly: an HTTP request becomes a command, a domain object
becomes a response. The question is *what shape* it takes on the way across. Pass the wrong
thing - a raw request array, an Eloquent model - and the boundary leaks. A **Data Transfer
Object (DTO)** is the answer: a small, plain object that carries data and nothing else.

## What a DTO is

A DTO has fields and no behavior. No business rules, no database access, no framework
methods - just typed data. PHP 8.4 makes them tiny with constructor promotion and
`readonly`:

```php
<?php

final class PlaceOrderCommand
{
    public function __construct(
        public readonly string $customerId,
        public readonly array $items,   // list of ['sku' => string, 'qty' => int]
        public readonly string $currency,
    ) {}
}
```

`readonly` means once built it cannot change - a DTO is a snapshot of data in motion, not a
thing you mutate. A command like this one is a DTO travelling *into* the application; a
response object is a DTO travelling *out*.

## Why not just pass the model or an array?

Two shapes tempt people, and both leak boundaries.

**Passing an Eloquent model outward** ties every caller to your database schema. Return an
`Order` model from a use case and the controller (and the view, and the API client) now
depend on your column names, your relations, your ORM. Rename a column and the JSON your API
returns changes. Worse, the caller gets `save()`, `delete()` and every relation - it can now
reach past the use case and change the database directly, and your application layer no
longer controls what happens.

**Passing raw arrays** loses all the guarantees. `$data['custmer_id']` is a typo the
compiler cannot catch; `$data['items']` might be missing; nothing tells the next developer
what keys exist. A typed DTO documents itself and fails loudly when a field is wrong.

A DTO in between keeps the domain and the database as private implementation details. The
outside world sees only the fields you chose to expose.

## Mapping is a simple, boring job

Mapping is just copying values from one shape to another. Keep it explicit and keep it at
the edges. On the way *in*, a controller turns request input into a command:

```php
<?php

public function store(Request $request): JsonResponse
{
    $command = new PlaceOrderCommand(
        customerId: $request->string('customer_id'),
        items: $request->array('items'),
        currency: $request->string('currency'),
    );

    $this->placeOrder->handle($command);

    return response()->json(status: 201);
}
```

On the way *out*, the use case turns a domain object into a response DTO so the model never
escapes:

```php
<?php

final class OrderResponse
{
    public function __construct(
        public readonly string $id,
        public readonly string $status,
        public readonly int $total,
    ) {}

    public static function fromOrder(Order $order): self
    {
        return new self(
            id: $order->id()->toString(),
            status: $order->status()->value,
            total: $order->total()->amount(),
        );
    }
}
```

The `fromOrder` factory is the one place that knows how an `Order` becomes an
`OrderResponse`. If the domain changes, you fix the mapping here and every caller keeps
working against the stable DTO.

One thing typed properties do *not* buy you: a `public readonly array $items` type-checks
that `items` is an array, but nothing enforces the shape of the elements inside it.
`$request->array('items')` will happily hand you a list of malformed rows and the DTO
constructor will accept them. Validate the request before you build the command - the DTO
guards the boundary's *fields*, not the contents of an untyped `array`.

## Where mapping belongs

Do the mapping at the boundary you are crossing, not deep inside. Request-to-command mapping
belongs in the controller (a driving adapter, see
[driving vs driven adapters](/course/software-architecture/hexagonal-architecture/driving-vs-driven-adapters)).
Domain-to-response mapping belongs at the edge of the use case or in the response DTO
itself. The domain in the center never does mapping - it does not know that HTTP or JSON
exist, which is exactly what keeps it
[framework-free](/course/software-architecture/hexagonal-architecture/the-domain-at-the-center).

## Common mistake

The common mistake is letting the ORM model become the DTO "to save typing" - returning
`Order` models straight from use cases and serializing them to JSON. It feels efficient
until an internal rename leaks into your public API, a caller calls `->delete()` on
something it should only read, or you need two different JSON shapes from the same model and
have nowhere to put them. A boring mapping step buys you a stable contract. Write the DTO.

## FAQ

### What is a DTO

A Data Transfer Object is a small, plain object that carries data across a boundary and has
no behavior - just typed fields. Commands into a use case and response objects out of it are
both DTOs.

### Why not return Eloquent models from a use case

Because it leaks your database schema to every caller and hands them methods like `save()`
and `delete()` that let them bypass the use case. A response DTO exposes only the fields you
choose and keeps the model private.

### Is a command a DTO

Yes. A command is a DTO travelling into the application layer - it carries the data an
action needs. The difference from a response DTO is only direction and intent.
