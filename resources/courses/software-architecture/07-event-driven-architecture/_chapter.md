---
title: "Event-driven architecture"
slug: event-driven-architecture
description: "Go deeper on events: event-driven architecture and message brokers, events vs commands, choreography vs orchestration, event sourcing, read models with full CQRS, and the saga pattern for cross-service transactions."
---

Chapter 4 introduced **domain events** - a record of something that happened in the
business. Chapter 6 split writes from reads with a first look at **CQRS**. This chapter
puts events at the center of the design. You'll learn what **event-driven architecture**
is and how a **message broker** lets parts of a system talk without knowing about each
other, the crucial difference between a **command** (an intent) and an **event** (a fact),
and two ways to coordinate a multi-step process: **choreography** and **orchestration**.
Then we go deeper: **event sourcing** (store the events, not the current state), building
**read models** for full CQRS, and the **saga pattern** for transactions that span several
services. These are advanced ideas, so every lesson starts from the problem and keeps the
PHP small.
