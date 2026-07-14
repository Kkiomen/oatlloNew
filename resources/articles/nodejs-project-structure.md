---
name: "How to Structure a Node.js Project That Scales"
slug: nodejs-project-structure
short_description: "A practical guide to nodejs project structure: feature-based folders, separating routes, controllers and services, config, and avoiding circular deps."
language: en
published_at: 2026-08-07 09:00:00
is_published: true
tags: [nodejs, architecture, backend, express]
---

There is no such thing as the one correct **nodejs project structure**. Node ships you an entry file and a `package.json`, and then it steps out of the way. That freedom is great on day one and painful on day two hundred, when three developers are all editing the same `utils.js` and nobody can say where a new feature is supposed to live. This guide is about the layout I keep coming back to after scaling a handful of real apps from a single route file to something with dozens of modules.

I'll be blunt about the trade-offs, show a directory tree you can copy, and explain *why* each decision holds up as the codebase grows. Examples lean on Express because it's the common denominator, but the structure is framework-agnostic — the same reasoning applies to Fastify, Koa, or a plain HTTP server.

## Layered vs feature-based structure

Most tutorials teach the **layered** (technical) layout. You group files by what they *are*:

```text
src/
  controllers/
  services/
  models/
  routes/
  middlewares/
```

It reads cleanly in a blog post. It also falls apart quietly. To touch one feature (say, "orders") you open `controllers/orders.js`, `services/orders.js`, `models/order.js`, and `routes/orders.js`, four folders that share nothing but a name. Delete a feature and you're hunting through every folder hoping you got all the pieces.

The **feature-based** (module) layout groups files by what they *do*:

```text
src/
  modules/
    orders/
      orders.routes.js
      orders.controller.js
      orders.service.js
      orders.model.js
      orders.test.js
    users/
      users.routes.js
      users.controller.js
      users.service.js
      users.model.js
  shared/
    middleware/
    errors/
    db/
  config/
  app.js
  server.js
```

My recommendation for anything beyond a weekend project: **go feature-based.** Here's the reasoning, not just the assertion.

- **Cohesion.** Everything that changes together lives together. A ticket that says "add a discount field to orders" touches one folder. You're not context-switching across the tree.
- **Deletion is honest.** Removing a feature means deleting `modules/orders/`. If the app still boots, you got it all. With layered folders, dead code lingers for months.
- **Fewer cross-imports.** When files sit next to each other, imports stay local (`./orders.service`). Layered layouts push you toward long relative paths and encourage grabbing from anywhere, which is exactly how spaghetti starts.
- **Onboarding.** A new dev reads `modules/` and understands the *domain* (orders, users, billing) instead of a taxonomy of Node concepts.

Layered structure isn't wrong; it's just optimized for small apps. If your whole backend is 500 lines, the technical layout is less ceremony and that's fine. The pain only shows up at scale, and by then restructuring is expensive. Pick the layout that ages well.

## Separating routes, controllers, and services

Whatever the top-level shape, keep these three responsibilities distinct *inside* each module. This is the single highest-leverage habit for a maintainable **Node.js project structure**.

- **Routes** map URLs to handlers. No logic. They wire HTTP verbs and paths to controller functions and attach middleware.
- **Controllers** speak HTTP. They read `req`, validate input shape, call a service, and shape the `res`. They should not know about SQL or business rules.
- **Services** hold the business logic. They know nothing about `req`/`res`. Given plain arguments, they do the work and return plain data or throw.

That last point matters more than it looks. A service that never touches `req` can be called from an HTTP route today, a cron job next month, and a test in between, without dragging Express along.

```javascript
// orders.routes.js
import { Router } from 'express';
import * as controller from './orders.controller.js';

const router = Router();
router.post('/', controller.createOrder);
export default router;
```

```javascript
// orders.controller.js
import * as service from './orders.service.js';

export async function createOrder(req, res, next) {
  try {
    const order = await service.placeOrder(req.user.id, req.body);
    res.status(201).json(order);
  } catch (err) {
    next(err); // let centralized error middleware decide the status
  }
}
```

```javascript
// orders.service.js
import { orders } from './orders.model.js';
import { ValidationError } from '../../shared/errors/index.js';

export async function placeOrder(userId, payload) {
  if (!payload.items?.length) {
    throw new ValidationError('An order needs at least one item');
  }
  // pure business logic — no req, no res, no HTTP status codes
  return orders.insert({ userId, items: payload.items, status: 'pending' });
}
```

Notice the controller catches and forwards with `next(err)` rather than writing error responses inline. That funnels every failure through one place, which is the next topic.

## The data layer

Keep database access behind the model/repository file, never scattered through services with raw queries. The service asks `orders.insert(...)`; it doesn't know or care whether that's Postgres, an ORM, or an in-memory fake during tests.

This boundary is what lets you swap a query builder for an ORM, add caching, or mock the DB in unit tests without rewriting business logic. When people say a codebase is "hard to test," nine times out of ten it's because the data access is welded directly into the logic.

## Config and environment

Centralize configuration into one module that reads `process.env` **once**, validates it, and exports a typed object. Do not sprinkle `process.env.WHATEVER` across twenty files.

```javascript
// config/index.js
import 'dotenv/config';

function required(name) {
  const value = process.env[name];
  if (!value) throw new Error(`Missing required env var: ${name}`);
  return value;
}

export const config = {
  port: Number(process.env.PORT ?? 3000),
  databaseUrl: required('DATABASE_URL'),
  jwtSecret: required('JWT_SECRET'),
  env: process.env.NODE_ENV ?? 'development',
};
```

Two payoffs. First, the app **crashes on boot** if a required variable is missing, instead of dying at 2 a.m. on the one code path that needed it. Second, every other file imports `config`, so there's a single source of truth and your editor autocompletes it. Commit a `.env.example` with the keys (and no values) so teammates know what to set.

## Separating app from server

Split the Express app definition from the code that starts listening:

```javascript
// app.js — builds and returns the app, does not listen
import express from 'express';
import ordersRoutes from './modules/orders/orders.routes.js';
import { errorHandler } from './shared/errors/error-handler.js';

export function createApp() {
  const app = express();
  app.use(express.json());
  app.use('/api/orders', ordersRoutes);
  app.use(errorHandler); // must be last
  return app;
}
```

```javascript
// server.js — the only file that calls listen()
import { createApp } from './app.js';
import { config } from './config/index.js';

createApp().listen(config.port, () => {
  console.log(`Listening on :${config.port}`);
});
```

This tiny split pays off immediately in tests: supertest can hit `createApp()` in memory without ever binding a port, so your test suite runs fast and never fights over `EADDRINUSE`.

## Avoiding circular dependencies

Circular imports are the classic Node scaling bug. Module A imports B, B imports A, and one of them ends up `undefined` at runtime depending on which loaded first. The symptom is bizarre: a function that's clearly exported reads as `undefined`, and it costs an afternoon to trace.

How I keep them out:

- **Depend downward, not sideways.** Routes depend on controllers, controllers on services, services on models. Never the reverse. A model importing a service is a red flag.
- **No module should import a sibling's internals.** If `orders` needs something from `users`, go through the users *service* interface, not its model.
- **Put shared types and pure helpers in `shared/`.** If two modules both need a value, it belongs to neither — move it down into `shared/`, which imports nothing upward.
- **Use `madge` to catch cycles.** `npx madge --circular src/` in CI fails the build the moment a cycle sneaks in. Cheap insurance.

When a cycle appears, it's almost always telling you a responsibility is in the wrong place. Fix the design, don't paper over it with lazy `require()` calls inside functions.

## Common pitfalls

- **A `utils.js` junk drawer.** It becomes a dependency magnet that everything imports, which quietly couples the whole app. Name helpers by purpose (`date.js`, `money.js`) and keep them in `shared/`.
- **Business logic in controllers.** The fastest way to an untestable app. Push it into services.
- **Fat models.** Models that validate, send emails, and call other services stop being a data layer. Keep them thin.
- **Deep folder nesting.** `modules/orders/domain/entities/impl/...` is architecture cosplay. Flat-ish beats deep for finding files.
- **Reaching into another module's files.** The moment you import `../users/users.model.js` from the orders module, you've created a hidden coupling. Go through the public service.
- **No error boundary.** Without one central error handler, you get inconsistent status codes and leaked stack traces. Add it once, as the last middleware.

## FAQ

### Is there an official Node.js project structure?
No. Unlike some frameworks, Node enforces nothing beyond `package.json` and an entry point. Any "standard" folder layout you read about is a community convention, not a rule. Choose based on your app's size and stick to it consistently.

### When should I switch from layered to feature-based?
As soon as you have more than a couple of distinct domains, or more than one or two developers. If editing one feature already makes you jump between three folders, you're past the point where feature-based would help. It's cheaper to restructure at 5 features than at 50.

### Where do things that don't belong to any feature go?
In `shared/` (or `common/`): cross-cutting middleware, the error handler, the database connection, generic helpers. The rule: `shared/` may be imported by feature modules, but it must never import from a feature module. That one-way arrow is what keeps the dependency graph acyclic.

### Do I need TypeScript for this to work?
No. The structure is identical in plain JavaScript. TypeScript adds compile-time checks that catch a broken import path or a wrong service argument earlier, but the folder layout and the routes/controllers/services separation are language-agnostic.

## Conclusion

A good **nodejs project structure** isn't about following a blessed template — there isn't one. It's about a few decisions that compound as the codebase grows: group by feature so related code stays together, keep routes, controllers, and services in their lanes, hide the database behind a model, load config once, and point every dependency in the same direction so cycles can't form.

Start with the feature-based tree above, run `madge` in CI, and revisit the layout when a change starts feeling awkward — that friction is the most reliable signal you have that something needs to move. Structure is a means to shipping features without fear, nothing more.