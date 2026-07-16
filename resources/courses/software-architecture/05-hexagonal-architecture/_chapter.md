---
title: "Hexagonal architecture"
slug: hexagonal-architecture
description: "Hexagonal architecture (ports and adapters): put the domain at the center, point every dependency inward, and let interfaces separate business logic from the database, HTTP and the framework."
---

Chapters 3 and 4 taught you to model the business - the language, the boundaries, and the
building blocks like entities, aggregates and repositories. This chapter answers a
different question: **where does all that code physically sit, and what is it allowed to
depend on?** Hexagonal architecture (also called **ports and adapters**) gives one clear
answer. The domain goes in the center, everything technical stays outside, and the two
meet only through interfaces called **ports**. You'll learn what a port and an **adapter**
are, the difference between the code that drives your app and the code your app drives, and
how the same idea shows up under the names Onion and Clean architecture. The chapter ends
with **vertical slice architecture**, an honest alternative that organizes by feature
instead of by layer.
