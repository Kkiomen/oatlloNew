---
title: "Hexagonal vs onion vs clean architecture"
slug: hexagonal-vs-onion-vs-clean
seo_title: "Hexagonal vs Onion vs Clean Architecture Compared"
seo_description: "Hexagonal, onion and clean architecture are three names for one idea: dependencies point inward and the domain stays isolated. The differences and shared core."
---

Read enough architecture articles and you will meet three names that seem to compete:
**hexagonal**, **onion**, and **clean** architecture. The good news is that they are not
three things to choose between. They are three drawings of the **same core idea** you have
already learned: dependencies point inward, and the domain stays isolated.

## The shared essence

Strip away the diagrams and all three say the same two things:

- The **domain** (business rules) sits at the center and depends on nothing technical.
- All **dependencies point inward**; the outside depends on the inside, never the reverse.

That is the [dependency rule from lesson 2](/course/software-architecture/hexagonal-architecture/the-domain-at-the-center).
Every one of these architectures exists to enforce it. If you have understood ports and
adapters, you already understand the heart of onion and clean too.

```text
   hexagonal        onion              clean
   -----------      -----------        -----------
   center + edge    nested rings       nested rings
   ports/adapters   layers as rings    layers as circles
   many sides in    domain innermost   domain innermost

   same rule: outer depends on inner, never the reverse
```

## Hexagonal (ports and adapters), 2005

Alistair Cockburn's version, the subject of this whole chapter. Its emphasis is the
**boundary**: the domain talks to the outside only through ports, and adapters plug into
them. It draws a shape with an inside and an outside rather than a stack, to show that
there is no privileged "top." It says little about how to subdivide the inside.

## Onion architecture, 2008

Jeffrey Palermo drew the same idea as **concentric rings**, like an onion. The innermost
ring is the domain model. Around it: domain services, then application services, then the
outer ring of infrastructure and UI. The rule is that a ring may depend only on rings
further in.

Compared to hexagonal, onion adds opinion about the **inner structure** - it names the
rings and their order. Hexagonal mostly says "domain in, technology out"; onion says "and
here is how to layer the inside."

## Clean architecture, 2012

Robert C. Martin's version, again concentric circles. From the center out: entities, use
cases, interface adapters, frameworks and drivers. Its headline is the **dependency rule**,
stated exactly as we did: source-code dependencies cross boundaries only inward. Clean
architecture also stresses crossing those boundaries with interfaces (the same trick as
ports) and folds in the ideas of the two earlier models.

Clean is the most detailed and the most prescriptive about the inner circles, but its core
is identical to the other two.

## So which do I use?

Use whichever vocabulary your team knows; the code comes out nearly the same. In practice
people mix the terms: "we do clean architecture with ports and adapters" is a normal
sentence and not a contradiction. What matters is not the label but whether the two shared
rules hold in your codebase. This course uses the hexagonal vocabulary because "ports and
adapters" names the pieces most plainly.

A caution when you read a codebase: the label on the README rarely predicts the folders. A
project that calls itself "clean architecture" often ships plain `Domain/`, `Application/`
and `Infrastructure/` directories - the same three you would draw for hexagonal. Trust the
direction of the dependencies over the name in the [ADR](/course/software-architecture/evolving-the-architecture/documenting-architecture-adr-c4).

## Common mistake: treating them as rival frameworks to pick between

Beginners waste time hunting for the "best" of the three, as if picking wrong will cost
them. There is nothing to install and little to decide - they are teaching diagrams for one
principle. Learn the principle (domain isolated, dependencies inward), and you can read any
of the three and recognize your own code. Arguing hexagonal vs clean is arguing about which
picture of the same house is prettier.

## FAQ

### Hexagonal vs clean architecture - what is the real difference?

Mostly emphasis and detail. Hexagonal stresses the boundary (ports and adapters) and stays
quiet about the inside. Clean adds named inner circles (entities, use cases) and states the
inward dependency rule explicitly. The enforced rule is the same.

### Is onion architecture the same as hexagonal?

Nearly. Onion draws concentric rings and prescribes how to layer the inside; hexagonal
draws a center with ports and focuses on the boundary. Both put the domain in the center
and point dependencies inward.

### Which one should a beginner learn first?

Learn the shared rule through hexagonal, because "ports and adapters" names the parts most
clearly. Once that clicks, onion and clean read as the same idea with extra detail, and you
will not need to study them separately.
