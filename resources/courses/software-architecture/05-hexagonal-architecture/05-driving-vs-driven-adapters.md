---
title: "Driving vs driven adapters"
slug: driving-vs-driven-adapters
seo_title: "Driving vs Driven Adapters (Primary & Secondary)"
seo_description: "Primary/driving adapters call the app (HTTP, CLI, tests); secondary/driven adapters are called by the app (DB, mail, queue). The flow of control explained."
---

Adapters sit on two opposite sides of the hexagon, and the difference is simply **who
starts the conversation**. A **driving** adapter calls into the application. A **driven**
adapter is called by the application. Getting this direction straight is the clearest way
to understand how control flows through a hexagonal system.

## Two sides, two roles

```text
   DRIVING SIDE                              DRIVEN SIDE
   (primary)                                 (secondary)
   they call the app                         the app calls them

   HTTP controller  --\                    /--  MysqlOrderRepository
   CLI command  ------> [ APPLICATION ] --->    Mailer
   Test  ------------/                     \--  Queue publisher
```

- **Driving adapters** (also called *primary*) are on the left. They represent an actor
  starting an action: a user's HTTP request, a scheduled CLI command, a test. They call a
  driving port to make the app do something.
- **Driven adapters** (also called *secondary*) are on the right. They represent things the
  app makes use of: the database, a mail server, a message queue. The app calls a driven
  port; the adapter carries out the request against real technology.

## Follow the flow of control

Trace one order being placed and the roles fall into place:

```text
1. HTTP controller  (driving)   receives the request
2. calls PlaceOrder (driving port)
3. the use case runs the domain rules
4. the use case calls OrderRepository (driven port)
5. MysqlOrderRepository (driven) writes to the database
```

Control enters from the left through a driving adapter, passes through the application in
the center, and exits to the right through a driven adapter. The driving side pushes; the
driven side gets pushed. The application is always in the middle, never at either end.

## Both sides use interfaces, but define them differently

A subtle but important point: both ports are interfaces, yet they are owned differently.

- A **driving port** is offered by the application and *implemented by the application*. The
  driving adapter (a controller) merely calls it.
- A **driven port** is needed by the application and *implemented by an outside adapter*. The
  application only calls it.

```php
<?php

// driving: the controller CALLS this; the app implements it
interface PlaceOrder
{
    public function handle(PlaceOrderCommand $command): OrderId;
}

// driven: the app CALLS this; an outside adapter implements it
interface OrderRepository
{
    public function save(Order $order): void;
}
```

So the dependency arrows still point inward on both sides. On the driving side the outside
depends on the app's use case. On the driven side the outside implements the app's
interface. The center never depends outward either way.

## Why the split matters

Separating the two sides tells you where a change belongs. A new way to *trigger* the app -
a REST endpoint, a GraphQL resolver, a queue consumer - is a new driving adapter, and the
domain does not move. A new *dependency* - switching mailers, adding a cache - is a new
driven adapter behind an existing port. The center stays still while both edges evolve
independently.

It also shapes testing. Driving adapters let a test act as the actor and call the app.
Driven ports let a test replace the database or mailer with a fake (the [in-memory
repository from the previous lesson](/course/software-architecture/hexagonal-architecture/adapters)).
You can exercise the whole use case with nothing real attached to either side.

Worth knowing where practice diverges from the diagram: many teams skip the driving-port
*interface* and let the controller call the use-case class directly. It is a defensible
shortcut, because the app both defines and implements a driving port - there is no outside
technology to invert away from, so the interface buys little. Driven ports are the opposite:
skip those and the domain reaches straight for MySQL, so those you almost never drop.

## Common mistake: mixing the two directions in one class

Trouble starts when a single class both receives the request and talks to the database - a
controller running its own SQL. Now it is a driving and a driven adapter at once, the flow
of control loops through the framework instead of the domain, and the center is bypassed
entirely. Keep the roles apart: driving adapters call in and stop; driven adapters are
called and do the outside work. The application in the middle connects them.

## FAQ

### What is the difference between primary and secondary adapters?

They are just other names. Primary equals driving (they call the app: HTTP, CLI, tests).
Secondary equals driven (the app calls them: database, mail, queue). Direction of the call
is the only distinction.

### Is a controller driving or driven?

Driving (primary). It represents an actor initiating a request and calls into the
application. The database repository it eventually reaches is driven (secondary).

### Which side owns the interface?

The application owns both port interfaces, but implements only the driving ones. Driving
ports are implemented by the app and called by outside adapters; driven ports are defined
by the app and implemented by outside adapters.
