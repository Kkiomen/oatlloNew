---
title: "Evolving the architecture"
slug: evolving-the-architecture
description: "The final chapter: avoid the anemic domain model, know when DDD and hexagonal are over-engineering, refactor a monolith into modules, test across boundaries, document decisions with ADRs and C4, and replace a legacy system with the strangler pattern."
---

You've learned the styles and patterns. This last chapter is about **living with them** -
the traps you'll hit, the judgment calls, and how to change a system that already exists.
You'll start with the most common way DDD goes wrong: the **anemic domain model**, where
entities hold data but no behaviour. Then the counter-weight: **when not to use DDD** at
all, because for simple software these patterns are pure cost. From there it's practical
evolution - **refactoring a monolith into modules** without a rewrite, **testing across
boundaries** (which a clean domain makes cheap), **documenting** decisions with ADRs and
the C4 model so they survive, and the **strangler pattern** for replacing legacy systems
slice by slice. The theme throughout: architecture is never finished, so the real skill is
changing it safely.
