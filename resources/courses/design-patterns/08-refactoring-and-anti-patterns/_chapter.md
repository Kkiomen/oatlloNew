---
title: "Refactoring and anti-patterns"
slug: refactoring-and-anti-patterns
description: "Spot bad design on sight and fix it safely - a catalog of code smells, the classic anti-patterns and why they emerge, the core refactoring techniques, and a full before-and-after case study that ties the whole course together."
---

The rest of this course taught you what good design looks like - principles, SOLID, and the
Gang of Four patterns. This final chapter is about the other direction: recognizing **bad**
design and repairing it without breaking anything.

You'll start with a catalog of **code smells**, the small warning signs that something needs
attention, each with a one-line tell and a pointer to the principle or pattern that fixes it.
Then come the named **anti-patterns** - the big, well-known failure shapes like the God Object
and the Big Ball of Mud - and why decent teams still drift into them.

After that you'll learn the **refactoring techniques** themselves: concrete, named moves you
apply in small, safe steps, ideally behind tests. The chapter - and the course - ends with a
**capstone case study**: one ugly PHP service refactored step by step, pulling together SRP,
Strategy and dependency injection so you can see every idea working at once.
