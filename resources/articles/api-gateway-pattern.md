---
name: "The API Gateway Pattern"
slug: api-gateway-pattern
short_description: "What an API gateway actually centralizes, how it differs from a reverse proxy and load balancer, the BFF variant, and when you don't need one."
language: en
published_at: 2027-05-19 09:00:00
is_published: true
tags: [architecture, devops, api, microservices]
---

The first time it bit me, we had four services and every one of them re-implemented JWT validation. Slightly differently. One trimmed the `Bearer ` prefix, one didn't; one checked the `exp` claim in seconds, another in milliseconds. A token that worked against the orders service got a 401 from billing, and the mobile team spent an afternoon proving it wasn't their bug. That afternoon is the whole argument for the pattern this article is about — pulling the cross-cutting stuff out of every service and putting it in one place clients talk to first.

I'll go through what a gateway centralizes, why people confuse it with a reverse proxy and a load balancer (they're related but not the same), the backends-for-frontends variant, and the two costs nobody mentions on the marketing pages: it's a single point of failure and it adds a network hop. And I'll be direct about when you shouldn't reach for one at all.

## The problem: clients talking to services directly

Break a system into services and the client's life gets harder, not easier. A mobile app that used to hit one API now needs to know the address of orders, billing, catalog, and notifications. Each of those wants a valid token. Each should be rate-limited so one abusive client can't starve the others. Each terminates TLS. And the screen the app is rendering needs data from three of them, so it fires three requests over a mobile connection and stitches the results together on a device with a flaky radio.

Now multiply that by every client — web, mobile, a partner integration, an internal admin tool. The auth logic, the rate-limit counters, the TLS certificates, the aggregation glue: all of it gets copied into every service and every client. That's the duplication a gateway is meant to kill. It sits in front, and clients only ever talk to it.

## What a gateway actually centralizes

A gateway is a single entry point that takes a request, does the shared work, and forwards it to whichever service owns the answer. The concrete responsibilities:

- **Routing.** Map an incoming path or host to an upstream service. `/api/orders/*` goes to the orders service, `/api/catalog/*` to catalog. Clients see one API; internally it's many.
- **Authentication and authorization.** Validate the token once, at the edge. Downstream services can trust that a request reaching them is authenticated, and often receive the decoded identity as a header instead of re-parsing the JWT.
- **Rate limiting.** One place to enforce per-client quotas, so a single caller can't exhaust the whole system. If you want the mechanics of how these limits are actually counted, I wrote about that in [token bucket vs fixed window](/api-rate-limiting-token-bucket-vs-fixed-window).
- **Request aggregation.** Fan out to several services and return one combined response, so the client makes one call instead of five.
- **Protocol translation.** Speak REST or GraphQL to the outside world while talking gRPC or a message queue to the inside. The client never learns the internal protocol.
- **TLS termination, logging, tracing, response caching.** The unglamorous cross-cutting work that every service would otherwise reinvent.

Here's a trimmed routing block — this is Kong in declarative config, but the shape is the same everywhere:

```yaml
services:
  - name: orders
    url: http://orders.internal:8080
    routes:
      - name: orders-route
        paths:
          - /api/orders
    plugins:
      - name: jwt
      - name: rate-limiting
        config:
          minute: 120
          policy: redis
```

Two plugins — `jwt` and `rate-limiting` — and the orders service itself carries neither. It runs on a private address (`orders.internal`), never exposed to the public internet. That last part matters: with a gateway, your services stop being reachable directly, which shrinks the attack surface to a single audited front door.

The header-injection trick is worth spelling out, because it's where a lot of the real benefit lives:

```
# Client sends:
GET /api/orders/42
Authorization: Bearer eyJhbGciOi...

# Gateway validates the token, strips it, and forwards:
GET /orders/42
X-User-Id: 8814
X-User-Roles: customer
```

The orders service reads `X-User-Id` and gets on with its job. It never touches the signing key, never parses a JWT. One catch that has burned teams: if those trusted headers can also arrive from the public internet, a caller can forge `X-User-Id` and impersonate anyone. The gateway **must** strip inbound copies of its own trust headers before setting them. Treat that as non-negotiable.

## Gateway vs reverse proxy vs load balancer

These three get used interchangeably in conversation, and the blur causes real confusion in design reviews. They overlap, but they answer different questions.

| | Primary job | Operates on | Aware of |
|---|---|---|---|
| Load balancer | Spread traffic across identical instances | Connections / requests | Instance health |
| Reverse proxy | Front-end for backends, terminate TLS, cache | HTTP requests | Paths and hosts |
| API gateway | Application-level entry: auth, quotas, aggregation | API calls | Clients, tokens, routes, business shape |

A **load balancer** answers "which of my five identical order servers should this request go to?" It cares about health checks and distribution, not about what the request means.

A **reverse proxy** (nginx is the archetype) answers "how do I present several backends behind one hostname, terminate TLS, and cache responses?" It knows about paths and headers.

An **API gateway** answers "what should happen to this API call before it reaches a service?" — validate the caller, check their quota, maybe combine three backend calls into one. It understands your application's shape in a way the other two don't.

The reason they blur is that the same software often plays several roles. Nginx is a reverse proxy that also load-balances, and with enough Lua or the right modules it edges into gateway territory. Kong is literally built on top of nginx. So the boundaries are about responsibility, not about which binary you downloaded. My rule of thumb: if the box makes decisions based on *who the caller is and what they're allowed to do*, it's acting as a gateway. If it only cares about *where to send bytes*, it's a proxy or balancer.

## Backends-for-frontends (BFF)

A single gateway that serves every client eventually pulls in two directions. The mobile app wants small, aggregated payloads to save bytes and round-trips. The web dashboard wants richer data and doesn't care about size. A partner API needs a stable, versioned contract that never shifts under them. Cram all three into one gateway and its config becomes a knot of `if client == mobile` branches.

The **backends-for-frontends** pattern splits it: one gateway *per client type*. A mobile BFF, a web BFF, a partner BFF. Each is owned by the team that owns that client, and each is free to aggregate and shape responses exactly for its consumer.

```
                 ┌─────────────┐
   Mobile app ──▶│ Mobile BFF  │──┐
                 └─────────────┘  │   ┌──────────┐
                 ┌─────────────┐  ├──▶│ orders   │
   Web app    ──▶│  Web BFF    │──┤   ├──────────┤
                 └─────────────┘  ├──▶│ catalog  │
                 ┌─────────────┐  │   ├──────────┤
   Partner    ──▶│ Partner BFF │──┘   │ billing  │
                 └─────────────┘      └──────────┘
```

The upside is real: the mobile team ships a payload change without a cross-team meeting, because the aggregation lives in *their* BFF. The downside is equally real: you now maintain three gateways, and shared concerns like auth risk drifting apart again — the exact problem you started with. BFF earns its keep when client needs genuinely diverge. If mobile and web want the same responses, one gateway is less to run. Don't split on principle; split when a single config is fighting itself.

## The costs nobody prints on the box

**It's a single point of failure.** Every request goes through the gateway, so if it's down, everything is down — not one service, all of them. This is not a reason to avoid the pattern; it's a reason to treat the gateway as your most critical, most redundant component. Run at least two instances behind a load balancer (yes, a load balancer in front of your gateway — the roles compose). Health-check it aggressively. Keep its config boring and its custom plugins few, because clever code at the choke point fails for everyone at once.

**It adds a network hop.** The gateway sits between client and service, so every call pays one extra hop of latency. Usually that's single-digit milliseconds and nobody notices. It stops being free when the gateway does synchronous aggregation: if it calls three services in sequence to build one response, the client waits for the slowest of the three plus the overhead. Fan out in parallel, set per-upstream timeouts, and decide up front whether a slow billing service should fail the whole aggregated response or return partial data. That decision is business logic wearing an infrastructure costume, and it should be made on purpose.

## When a monolith does not need one

Here's the part the microservices content usually skips: if you have one deployable application, you probably don't need a gateway, and adding one is complexity you'll maintain forever for benefits you don't have yet.

A Laravel monolith already centralizes every single thing a gateway offers. Authentication is middleware. Rate limiting is middleware. Routing is the router. Aggregation is a controller calling several internal services in the same process — no network hop at all.

```php
// routes/api.php — the "gateway" concerns already live here
Route::middleware(['auth:sanctum', 'throttle:120,1'])
    ->prefix('api')
    ->group(function () {
        Route::get('/orders/{order}', [OrderController::class, 'show']);
        Route::get('/dashboard', [DashboardController::class, 'index']); // aggregates in-process
    });
```

`auth:sanctum` is your edge authentication. `throttle:120,1` is your rate limiting. The dashboard endpoint aggregates by calling internal services directly — a method call, not an HTTP round-trip. Putting a separate gateway process in front of this buys you nothing and costs you an extra hop plus another thing to run, patch, and page someone about at 3am.

Reach for a real gateway when the shared concerns are physically spread across separate deployables that can't share a middleware stack — that's the condition, not team size or a slide that says "we do microservices now." A three-service system with genuinely independent deployments benefits. A modular monolith does not, no matter how many modules it has.

## Concrete tools, briefly and without hype

- **Nginx** — a reverse proxy first; with `njs`/Lua or OpenResty it handles gateway duties. Great when your needs are routing, TLS, and basic rate limiting and you already run it. It's not a full gateway out of the box, and that's fine.
- **Kong** — built on nginx, a proper gateway with a plugin ecosystem (auth, rate limiting, transformations) and declarative config. The example above is Kong. Solid when you want gateway features without writing them.
- **Cloud-managed gateways** — AWS API Gateway, Google Cloud API Gateway, Azure API Management. You trade config control and some cost predictability for not running the thing yourself. Reasonable when you're already deep in one cloud and don't want another stateful service to babysit.

None of these is a default. The right pick depends on what you already operate and how much of the logic you want to own versus rent. A gateway you understand and can debug beats a fancier one you can't.

## FAQ

**Do I need an API gateway for microservices?**
Not automatically. You need a way to centralize auth, rate limiting, and routing across services that can't share a middleware stack. A gateway is the common answer, but a service mesh covers some of the same ground (service-to-service concerns) and a monolith covers all of it in-process. Adopt the gateway when the duplication is real and hurting, not because the architecture has a name.

**What's the difference between an API gateway and a reverse proxy?**
A reverse proxy routes and forwards HTTP based on paths and hosts. A gateway adds application awareness on top: it validates who the caller is, enforces what they're allowed to do, aggregates responses, and translates protocols. Every gateway is a reverse proxy; not every reverse proxy is a gateway.

**Won't the gateway become a bottleneck?**
It can, on two axes. Availability — it's a single point of failure, so run it redundantly behind a load balancer. Latency — it adds a hop, negligible for pass-through, real for synchronous aggregation, so fan out in parallel and set timeouts. Both are managed with standard practice; neither is a reason to skip the pattern once you actually need it.

**Is BFF just multiple gateways?**
Essentially, yes — one gateway per client type instead of one shared gateway. The point is letting each client's team own its aggregation and response shaping without stepping on the others. The cost is more gateways to run and the risk of shared logic (like auth) drifting apart, so only split when client needs genuinely diverge.

## The one-line version

A gateway is worth it exactly when the same cross-cutting work — auth, quotas, aggregation, TLS — would otherwise be copied into every service and every client. That copying is the pain; the gateway is the fix. If you have one deployable, you already have the fix and don't need the box. If you have several and you're duplicating that afternoon-of-mismatched-JWTs work, put a gateway in front — and design it as your most redundant component, because now everything depends on it. Next step: list your cross-cutting concerns, check whether one deployable already centralizes them, and only then go shopping for a gateway.
