---
title: "Context mapping"
slug: context-mapping
seo_title: "Context Mapping in DDD: Patterns and a Simple Map"
seo_description: "Context mapping in DDD: partnership, shared kernel, customer/supplier, conformist, anticorruption layer (ACL) and open host service, with a simple example map."
---

Once you have several
[bounded contexts](/course/software-architecture/ddd-strategic-design/bounded-contexts),
they have to work together. **Context mapping** is the practice of drawing those contexts
and, more importantly, the **relationships** between them. The map shows who depends on
whom, where translation happens, and where you are exposed to another team's decisions.

Eric Evans described a set of relationship patterns for these seams. Do not memorize them
for their own sake. Each one names a real political and technical situation between two
teams, and having the name makes it easier to decide how much to protect your model.

## A simple context map

```text
        [ Sales ] ---- partnership ---- [ Marketing ]
            |
     customer/supplier
            |
            v
        [ Billing ] ---- open host service ---> [ Reporting ]
            ^
            | anticorruption layer (ACL)
            |
    [ Legacy CRM ]  (external, cannot change)
```

Read it as: Sales and Marketing move together (partnership); Billing depends on Sales as a
downstream customer; Reporting reads Billing through a stable published interface; and
Billing shields itself from an old CRM it cannot change with an anticorruption layer. The
arrows show the direction of dependency and where translation lives.

## The main relationship patterns

**Partnership.** Two contexts (and their teams) succeed or fail together, so they
coordinate closely and plan changes jointly. It only works when the teams genuinely
cooperate; it is a commitment, not a default.

**Shared kernel.** Two contexts share a small, explicitly agreed piece of the model - a bit
of code or schema both use. It reduces duplication but couples the teams tightly: neither
can change the shared part alone. Keep it small and change it together.

**Customer/supplier.** One context is downstream (the customer) and depends on an upstream
one (the supplier). The customer's needs are a real input to the supplier's plans - the
supplier agrees to serve them. It is a cooperative relationship with a clear direction.

**Conformist.** Also downstream, but here the upstream team will not accommodate you. You
give up and simply conform to their model, adopting it as-is. You accept the coupling
because negotiating is not worth it.

**Anticorruption layer (ACL).** A downstream context builds a translation layer that
converts the other model into its own terms, so the foreign model does not leak in and
"corrupt" your clean model. This is the standard defense against a messy legacy system or a
third-party API you do not control. It costs work but keeps your domain pure.

**Open host service.** An upstream context publishes a well-defined, stable interface (often
with a documented, shared "published language") that many downstream contexts can consume.
Instead of a custom integration per consumer, you offer one public protocol. A public API
is the everyday example.

## A tiny anticorruption layer

An ACL is just a translator you own, sitting at your boundary:

```php
// Our Billing domain speaks its own language.
// The old CRM speaks a different, messier one.
final class CrmCustomerTranslator
{
    public function toBillingCustomer(array $crmRow): Customer
    {
        // Map the foreign shape into OUR model, here and only here.
        return new Customer(
            taxId: new TaxId($crmRow['vat_no']),
            balance: Money::fromCents((int) $crmRow['bal_cents']),
        );
    }
}
```

Because all translation lives in one class, the rest of Billing never sees `vat_no` or
`bal_cents`. If the CRM changes, you fix the translator, not the whole domain.

The layer erodes in a predictable way. Under deadline pressure someone needs one field the
translator doesn't expose yet, so they read `$crmRow['vat_no']` directly "just this once,"
and the leak spreads from there. An anticorruption layer only protects you as long as it
stays the *only* door to the foreign system - so the field to watch in review is any
reference to the outside model that isn't inside the translator.

## Common mistake

The common mistake is having no map at all - contexts reach directly into each other's
tables or copy each other's models, and one team's change silently breaks another. The
second mistake is skipping the anticorruption layer against a legacy or third-party system:
its odd shapes and names spread through your codebase until your clean model looks just like
the mess you were integrating with. Draw the map, and defend the boundaries you care about.

## FAQ

### What is context mapping in DDD

It is the practice of identifying your bounded contexts and the relationships between them -
who depends on whom and where translation happens - usually drawn as a simple diagram so
the integration and team dynamics are explicit.

### What is an anticorruption layer

An anticorruption layer (ACL) is a translation layer a downstream context builds so a
foreign or legacy model is converted into its own terms at the boundary, preventing the
outside model from leaking in and corrupting the clean domain.

### What is the difference between conformist and customer/supplier

In customer/supplier the upstream team cooperates and treats the downstream needs as real
input. In conformist the upstream will not accommodate you, so the downstream simply adopts
the upstream model as-is and accepts the coupling.
