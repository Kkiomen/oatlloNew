---
title: "DDD: tactical patterns"
slug: ddd-tactical-patterns
description: "The code-level half of Domain-Driven Design: entities, value objects, aggregates and aggregate roots, domain events, repositories, domain services and factories - with small PHP 8.4 examples."
---

Strategic design gave you the map: a ubiquitous language and bounded contexts. This
chapter zooms in on the code. These are the **tactical patterns** - the building blocks
you use to turn a domain model into classes that are hard to misuse. You'll learn the
difference between an **entity** (identity that survives change) and a **value object**
(immutable, compared by value), how an **aggregate** groups objects behind a single
**root** that guards the rules, how **domain events** record what happened, and how
**repositories**, **domain services** and **factories** keep the model clean. Every
lesson has a short PHP 8.4 example you can adapt.
