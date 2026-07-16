---
title: "Putting it together in Laravel"
slug: putting-it-together-in-laravel
description: "Everything so far, applied to a real Laravel 11 app: organize code by domain, map DDD onto Eloquent and the container, keep the domain framework-free, build one module end to end, and know when the defaults are already enough."
---

Seven chapters gave you the ideas: boundaries, Domain-Driven Design, hexagonal ports and
adapters, the application layer, event-driven design. This chapter grounds all of it in the
framework you actually ship with. You'll reorganize a Laravel app **by domain** instead of
by file type, map the DDD building blocks onto Laravel's own tools (**Eloquent** as an
adapter, the **service container** as the wiring, **jobs and events** for domain events),
and see why keeping the domain as **plain PHP** pays off - and what it costs. Then we build
one module, Billing, from the aggregate down to the controller, so the pieces click
together. The last lesson is the counterweight: for many apps, Laravel's defaults are the
right answer, and this chapter is as much about **when not to reach for this** as when to.
