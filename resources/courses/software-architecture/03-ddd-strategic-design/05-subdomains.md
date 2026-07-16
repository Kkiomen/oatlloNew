---
title: "Subdomains: core, supporting, generic"
slug: subdomains
seo_title: "Core, Supporting and Generic Subdomains in DDD"
seo_description: "DDD subdomains explained: core, supporting and generic. Learn where to invest your best design effort, and what to buy or simplify instead of building."
---

Not every part of your system deserves the same effort. **Subdomains** are the smaller
areas that make up a domain, and DDD sorts them into three kinds so you know where to spend
your best people and where to keep things cheap. This is a strategic decision - it shapes
where you apply the tactical patterns of the next chapter, and where you deliberately do
not.

Vaughn Vernon stresses this classification because getting it wrong is expensive both
ways. Gold-plate a boring subdomain and you waste effort; cut corners on the important one
and you damage the business.

## The three kinds of subdomain

**Core domain.** This is what makes your business different and hard to copy - the reason
customers choose you. For a logistics company it might be the route-optimization engine;
for a lender, the risk-scoring model. The core domain is where your competitive advantage
lives, so it is where you invest your best design, your best developers, and full DDD.

**Supporting subdomain.** Necessary for the business to run, but not a differentiator. It is
specific to you (so you cannot just buy it off the shelf), yet it does not win you
customers. Something like a custom back-office workflow. Build it, but keep it simple and do
not over-engineer.

**Generic subdomain.** A solved problem that every business has - authentication, sending
email, payments, PDF generation, accounting. It is not special to you at all. Here you
should **buy, adopt or integrate** an existing solution rather than build your own, and move
that effort to the core.

```text
                 special to you?      wins customers?
core domain          yes                  yes        -> build with your best effort
supporting           yes                  no         -> build, keep it simple
generic              no                   no         -> buy / use off-the-shelf
```

## Invest in the core, buy the rest

The central lesson is about **where effort goes**. Teams love building things, so they often
lavish attention on a generic subdomain (a hand-rolled auth system, a bespoke email queue)
while the actual core domain - the thing that should be excellent - gets whatever time is
left. That is backwards.

DDD's advice: put your strongest design and your deepest domain-expert conversations into
the **core domain**. For generic subdomains, prefer a library, a SaaS or a boring
off-the-shelf tool. For supporting subdomains, build a straightforward version and resist
polishing it.

In a Laravel app this maps neatly onto choices you already make:

```text
Payments (generic)   -> integrate Stripe / a payment gateway, do not build a processor
Auth (generic)       -> use the framework's authentication, do not invent your own
Emailing (generic)   -> use the mail component + a provider
Your pricing engine  -> CORE: your model, your rules, your best code and tests
```

The framework itself is, in effect, a pile of solved generic subdomains. Leaning on it for
the generic parts is exactly right; the mistake is dragging your **core** down to the level
of framework defaults instead of modeling it carefully.

One nuance the three-box picture hides: the labels are not permanent. A subdomain that was
core five years ago can quietly become generic once the market catches up and a vendor
sells it as a product - fraud scoring and full-text search both went that way. The trap is
a team still lavishing its best people on a "core" the rest of the world now buys for a
monthly fee. Re-ask "is this still what makes us different?" now and then, not just at the
start.

## How subdomains relate to bounded contexts

Subdomains and
[bounded contexts](/course/software-architecture/ddd-strategic-design/bounded-contexts) are
related but not the same. A subdomain is a part of the **problem space** - an area of the
business. A bounded context is part of the **solution space** - a boundary you draw in
software. In a clean design they often line up one-to-one (one context per subdomain), but
they can diverge, especially around legacy systems. Keep the distinction: subdomains are
about the business, contexts are about your model.

## Common mistake

The common mistake is treating every part of the system as equally important - full DDD
everywhere, or bespoke code everywhere. The result is a custom authentication system as
lovingly crafted as the pricing engine, and a team out of time for the part that actually
matters. Identify the core domain first, spend your effort there, and buy or simplify the
generic and supporting subdomains without guilt.

## FAQ

### What is a core domain

The core domain is the part of the system that gives your business its competitive
advantage - the reason customers choose you. It deserves your best design effort, your best
developers, and full Domain-Driven Design.

### What is the difference between supporting and generic subdomains

A supporting subdomain is specific to your business but not a differentiator, so you build a
simple version. A generic subdomain is a solved problem common to everyone (auth, email,
payments), so you buy or integrate an existing solution instead of building it.

### Where should I spend my best engineering effort

On the core domain. Buy or use off-the-shelf tools for generic subdomains and keep
supporting subdomains simple, so your strongest work goes into the part that actually
differentiates the business.
