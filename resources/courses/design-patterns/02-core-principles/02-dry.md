---
title: "DRY - Don't Repeat Yourself"
slug: dry
seo_title: "DRY Principle in PHP - and When DRY Goes Wrong"
seo_description: "The DRY principle done right: one source of truth per piece of knowledge - plus the trap where a wrong abstraction costs more than a little duplication."
---

The **DRY principle** - "Don't Repeat Yourself" - is more precise than "never copy-paste":
every piece of *knowledge* in a system should have a single, unambiguous source. When a
business rule lives in one place, you change it once. When it's scattered, you change it
five times and miss the sixth.

## Knowledge, not lines

DRY is about knowledge, not identical-looking lines. Picture a tax rate written directly
into three different files:

```php
$invoiceTotal = $net * 1.23;   // in the invoice code
$cartTotal    = $net * 1.23;   // in the cart code
$reportTotal  = $net * 1.23;   // in the reporting code
```

The rule "VAT is 23%" is one piece of knowledge, copied three times. When the rate
changes, you have to find every `1.23`. Give it one home instead:

```php
final class Tax
{
    public const RATE = 0.23;

    public static function withVat(float $net): float
    {
        return $net * (1 + self::RATE);
    }
}
```

Now the knowledge lives in exactly one place. Every caller asks `Tax::withVat($net)`, and a
rate change is a one-line edit.

## The trap: DRY misused

Here's where DRY hurts people. Developers see two blocks of code that *look* alike and
merge them on sight. But looking alike is not the same as being the same knowledge.

Imagine two rules that happen to be identical today:

```php
// How many days a customer has to return a product
function returnWindowDays(): int
{
    return 14;
}

// How many days we keep a draft order before deleting it
function draftExpiryDays(): int
{
    return 14;
}
```

Both return `14`, so it's tempting to replace them with one `fourteenDays()` helper. Don't.
These are two separate business rules that share a number by coincidence. The day the
return window becomes 30 but draft expiry stays 14, your shared helper has to be torn back
apart - and by then a dozen callers depend on it.

Watch how the pressure builds. Nobody rips the helper out; they add a parameter. First
`fourteenDays(bool $forDraft = false)`, then a second flag, then a branch inside. Each patch
looks smaller than the un-merge, so the bad abstraction survives by accreting arguments -
which is exactly how you spot one in review. A helper sprouting boolean flags is usually two
ideas that were never one.

## Common mistake

The mistake is deduplicating by *appearance* instead of by *meaning*. Before merging two
similar blocks, ask one question: **do these change for the same reason?** If yes, they're
the same knowledge - unify them. If they only look alike, leave them apart.

As the saying goes, **a wrong abstraction is worse than a little duplication**. Duplication
is cheap to fix later; a bad shared abstraction spreads its wrong shape into every caller,
and untangling it is far more work than the copy-paste ever was.

If two things drift for different reasons, that's a sign they were never one concern to
begin with - a theme separation of concerns picks up later in this chapter.

## FAQ

### So is a little duplication OK?

Sometimes, yes. A small amount of duplication that keeps two unrelated rules independent is
healthier than a shared abstraction that forces them to change together. Don't chase zero
duplication for its own sake.

### How do I tell "same knowledge" from "looks the same"?

Ask what would make each piece change. Two prices that both come from the same VAT rate
change together - that's one piece of knowledge. A return window and a draft expiry that
happen to both be 14 days change for completely different reasons - that's two.

### Doesn't this contradict "remove duplication"?

No. DRY removes duplication of *knowledge*. It was never a rule against every repeated line.
The goal is one source of truth per fact, not the fewest possible characters.
