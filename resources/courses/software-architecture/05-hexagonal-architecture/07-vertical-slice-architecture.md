---
title: "Vertical slice architecture"
slug: vertical-slice-architecture
seo_title: "Vertical Slice Architecture vs Layers (Explained)"
seo_description: "Vertical slice architecture organizes code by feature, not by layer: a full slice from request to persistence. When it beats layered or hexagonal, honestly."
---

**Vertical slice architecture** organizes code by **feature** instead of by layer. Each
use case - "place order," "cancel order" - gets its own self-contained slice holding
everything that feature needs, from handling the request down to touching the database. For
many apps that is a simpler cut than layering.

## The problem with slicing by layer

Layered and hexagonal code is usually grouped by **technical role**: all controllers here,
all repositories there, all domain classes somewhere else. To add one feature you open five
folders and add a piece to each.

```text
   Layered layout (by role)

   Controllers/   PlaceOrderController, CancelOrderController, ...
   Domain/        Order, OrderLine, ...
   Repositories/  MysqlOrderRepository, ...
   Services/      PlaceOrderService, CancelOrderService, ...
```

A feature is spread thin across every folder. Understanding "place order" means hopping
between directories, and changing it risks touching files shared with unrelated features.
The code that changes together does not live together.

## Slicing the other way

A vertical slice groups by feature, so everything for one use case sits in one place.

```text
   Vertical slice layout (by feature)

   PlaceOrder/    Controller, Command, Handler, (its own data access)
   CancelOrder/   Controller, Command, Handler, (its own data access)
   ViewOrder/     Controller, Query, Handler
```

Now "place order" is one folder. You read it top to bottom - request, validation, the
domain work, saving - without leaving. The slice runs the full height of the stack, which
is where "vertical" comes from: it cuts down through the layers rather than along them.

Here a slice keeps its own handler close to its own entry point:

```php
<?php

// app/Features/CancelOrder/CancelOrderHandler.php
final class CancelOrderHandler
{
    public function __construct(
        private OrderRepository $orders,
    ) {}

    public function handle(CancelOrderCommand $command): void
    {
        $order = $this->orders->findById($command->orderId);

        $order?->cancel();

        if ($order !== null) {
            $this->orders->save($order);
        }
    }
}
```

Nothing here is new - it is the same use case shape from earlier lessons. Only the **folder
it lives in** changed: beside the rest of the cancel-order feature, not in a shared
`Services/` bucket.

## When it beats layered or hexagonal

Vertical slices shine when:

- features are mostly independent and evolve on their own
- the team wants to add a feature by adding a folder, not editing five
- much of the app is straightforward request-to-database work with modest shared rules

They cut coupling **between features** and let each slice use exactly the amount of
structure it needs: a trivial slice can be one handler, a rich one can pull in the full
domain model.

## The honest trade-off

Slices are not free. Because each is free to do its own thing, discipline matters more:

- **Duplication vs coupling.** Slices may repeat small bits of logic. Often that is
  healthier than a shared class every feature is chained to, but it can drift.
- **Where do shared rules go?** A genuinely central domain rule used by many slices still
  needs a shared home. Pure slices with no common domain can scatter the same rule around.
- **Consistency.** Ten slices built ten different ways are hard to learn. Teams usually
  agree on a light convention for what a slice looks like.

The discipline is easy to state and easy to lose. The failure mode is one slice importing
another slice's handler for a shortcut - now they are coupled through the back door, and the
"independent feature" promise is gone. Teams that care about this enforce it mechanically: a
namespace/dependency rule (Deptrac or similar) that forbids one `Features/*` folder from
referencing another, so the boundary is checked in CI instead of trusted to review.

Vertical slice and hexagonal are not enemies. A common blend keeps a shared domain core
(hexagonal, with ports and adapters) and organizes the application layer around it as
vertical slices per use case. You get isolated features on the outside and one protected
domain in the middle.

## Common mistake: assuming you must pick one architecture forever

Slicing by feature and slicing by layer are organizational choices, not a lifelong vow. A
codebase can keep its domain hexagonal and its features sliced, or start sliced and grow a
shared core when real duplication appears. Choose per area based on how independent the
features are, and let the structure follow the code rather than a rule picked on day one.

## FAQ

### What is vertical slice architecture in simple terms?

It organizes code by feature instead of by technical layer. Each use case gets a folder
holding everything it needs, from the request handler down to data access, so related code
lives together.

### Vertical slice vs layered architecture - which is better?

Neither is universally better. Layered groups by role and suits apps with heavy shared
logic; vertical slice groups by feature and suits apps with many independent use cases.
Slices reduce coupling between features at the cost of some duplication.

### Can I use vertical slices with hexagonal architecture?

Yes, and it is a popular mix. Keep a shared domain core with ports and adapters, and shape
the application layer as vertical slices per use case. Isolated features outside, one
protected domain inside.
