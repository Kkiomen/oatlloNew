---
title: "Bounded contexts"
slug: bounded-contexts
seo_title: "Bounded Contexts in DDD Explained With Examples"
seo_description: "A bounded context is a boundary where a model and its language stay consistent. See how the same word like Customer means different things in different contexts."
---

A **bounded context** is a boundary within which a particular model and its language are
consistent. Inside the boundary, every term has one agreed meaning and the model holds
together. Cross the boundary, and you are in a different context with its own model - even
if some words look the same.

This is the idea that makes the [ubiquitous
language](/course/software-architecture/ddd-strategic-design/ubiquitous-language) work in a
real system. You do not need one language for the whole company; you need a clear language
inside each context, and clear seams between them.

## The same word, different meanings

The classic example is "Customer". It feels like one obvious concept - until you look at
what different parts of the business actually need.

```text
Sales context            Support context          Billing context
-----------------        --------------------      -------------------
Customer:                Customer:                 Customer:
 - lead score             - open tickets            - tax id
 - deal stage             - satisfaction            - payment method
 - assigned rep           - contact history         - invoices, balance
```

All three say "Customer", but they mean different things. Sales cares about turning a lead
into a deal. Support cares about tickets and history. Billing cares about tax and money.
Forcing all of that into one giant `Customer` class produces an object with 60 fields that
no single team fully understands - and every change risks breaking a use you did not know
about.

A bounded context lets each area keep its own `Customer`, shaped exactly for its needs. The
same real person is represented three times, on purpose, each version simple and correct
for its context.

## Why boundaries beat one big model

Early in a project it is tempting to build one unified model of everything - "the one true
Customer". It never survives contact with reality, because different parts of the business
genuinely think about the world differently. Trying to unify them creates a model that is
vague enough to please everyone and useful to no one.

Bounded contexts accept this. Each context is internally consistent and independently
understandable. Teams can work inside their own context without constantly coordinating
vocabulary with everyone else.

```php
// Sales context
namespace Sales;
final class Customer
{
    private LeadScore $score;
    private DealStage $stage;
}

// Billing context - same name, different class, different meaning
namespace Billing;
final class Customer
{
    private TaxId $taxId;
    private Money $outstandingBalance;
}
```

Two classes named `Customer`, in two namespaces, is not duplication to be removed - it is
two distinct concepts that happen to share a word. Merging them would be the mistake.

This is where a well-meaning reviewer can do real damage. Someone spots the two `Customer`
classes, flags "duplication", and the teams dutifully extract a shared `Customer` to please
the linter or the DRY reflex. Six months on it has 40 fields and belongs to no one. The
shared word is a coincidence, not a contract - resist the urge to fold them together.

## How this maps to code

In practice a bounded context often lines up with a **module** (in a modular monolith,
which you met in Chapter 2) or a **service** (in microservices). Each has its own domain
model, its own database tables, and ideally its own team. The boundary is where you decide
what stays private and what is shared.

Contexts still need to work together - a support agent needs to know who the customer is.
They connect through defined seams (an API, events, an id), not by reaching into each
other's tables. How those connections are structured is the subject of the next lesson,
context mapping.

## Common mistake

The most common mistake is chasing a **single shared model** across the whole system - one
`Customer`, one `Product`, one `Order` used everywhere. It starts clean and slowly becomes
a tangle: every field added for one team is a risk for another, and the class grows until
no one can change it safely. The fix is to let the same word mean different things in
different contexts, and keep each model small and sharp.

## FAQ

### What is a bounded context

It is a boundary within which a domain model and its ubiquitous language are consistent -
every term has one meaning inside it. Different bounded contexts can model the same real
thing differently, each correct within its own boundary.

### Can the same word mean different things in different contexts

Yes, and it usually should. "Customer" in Sales, Support and Billing are different models
with different fields and behavior. Sharing the word is fine; sharing one class across all
of them is what causes trouble.

### Is a bounded context the same as a microservice

Not exactly. A bounded context is a modeling boundary; a microservice is a deployment unit.
A context often becomes one module or one service, but you can have several contexts in a
single monolith. The boundary is about the model, not about how you deploy.
