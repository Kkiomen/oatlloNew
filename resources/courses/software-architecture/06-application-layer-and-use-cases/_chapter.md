---
title: "Application layer and use cases"
slug: application-layer-and-use-cases
description: "The thin layer that turns requests into use cases: one handler per action, commands and queries, DTOs across boundaries, orchestrating the domain, and transactions."
---

The domain holds your business rules; the outside world sends requests. Something has to
sit between them, take a request, run one piece of work, and hand back a result. That is
the **application layer**, and this chapter is about writing it well. You'll learn to model
each action as a single use case (one class, one method), to separate **commands** that
change state from **queries** that read it, to move data across boundaries with **DTOs**
instead of leaking Eloquent models, to **orchestrate** aggregates and repositories without
putting rules in the handler, and to treat each use case as one **transaction** with a
single commit at the end. The domain building blocks (aggregates, repositories, domain
events) come from Chapter 4; here we put them to work.
