---
slug: nodejs-project-structure-carousel
type: carousel
language: en
title: "Structure that survives"
topic: node
source_type: article
source: nodejs-project-structure
link: https://oatllo.com/nodejs-project-structure
publish_at: 2026-10-16 19:00
status: ready
formats: [post]
hashtags: [nodejs, javascript, architecture, express, backend]
caption: |
  Delete a feature. If the app still boots, your structure was honest.

  Layered folders make you hunt through four directories that share nothing
  but a name, and dead code lingers for months. Feature folders put
  everything that changes together in one place.

  Full guide linked in bio.

  Layered or feature based in your repo?
---

## Deleting a feature folder is the real test of your structure.

Node ships you an entry file and a `package.json`, then steps out of the
way. Great on day one, painful on day two hundred.

<!-- slide -->

## Four folders that share nothing but a name

```text
src/
  controllers/orders.js
  services/orders.js
  models/order.js
  routes/orders.js
```

To touch "orders" you open four directories. Delete the feature and you
are hunting for pieces, hoping you got them all.

<!-- slide -->

## Cohesion beats taxonomy

```text
src/modules/orders/
  orders.routes.js
  orders.controller.js
  orders.service.js
  orders.model.js
```

`rm -rf modules/orders/`. If the app still boots, you got it all. A new
dev reads `modules/` and learns the domain, not a taxonomy of Node concepts.

<!-- slide -->

## A service that never touches req is testable

```javascript
// no req, no res, no status codes
export async function placeOrder(id, body) {
  if (!body.items?.length) {
    throw new ValidationError('No items');
  }
}
```

Then it runs from an HTTP route today, a cron job next month and a test in
between, without dragging Express along.

<!-- slide -->

## Read process.env once, or die at 2 a.m.

```javascript
export const config = {
  port: Number(process.env.PORT ?? 3000),
  databaseUrl: required('DATABASE_URL'),
};
```

`required()` throws on boot when a var is missing, instead of dying on the
one code path that needed it. Everything else imports `config`.

<!-- slide role="cta" -->

## A cycle is a design smell, not a require bug

`npx madge --circular src/` in CI fails the build the moment one sneaks
in. Depend downward: routes, controllers, services, models. Never sideways.
Full guide linked in bio.
