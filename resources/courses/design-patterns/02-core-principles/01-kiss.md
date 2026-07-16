---
title: "KISS - Keep It Simple"
slug: kiss
seo_title: "KISS Principle in PHP: Keep It Simple, Stupid"
seo_description: "The KISS principle in plain terms: prefer the simplest code that works. Why clever one-liners are a liability, shown with a simple vs over-clever PHP example."
---

The **KISS principle** - short for "Keep It Simple, Stupid" - gives you one instruction:
prefer the simplest thing that solves the problem in front of you. Simple code is easier to
read, easier to change, and easier to trust.

## Why simple wins

Most of a developer's time is spent reading code, not writing it. Every clever trick you
add is a trick the next reader - often future you - has to decode before they can safely
change anything. Clever code feels smart to write and expensive to own.

Simplicity is not about writing less code at any cost. It's about writing code whose
intent is obvious. A few extra lines that read like plain instructions beat one dense
line that needs a comment to explain what it does.

## A clever version

Say we need the names of the active users from a list. Here's a version that tries to be
impressively compact:

```php
$names = array_values(array_filter(array_map(
    fn ($u) => $u->active ? $u->name : null,
    $users
)));
```

It works, but read it again. It maps every user to a name-or-null, filters out the nulls,
then re-indexes the array. You have to run all three transformations in your head, in the
right order, to see what it does.

## The simple version

```php
$names = [];

foreach ($users as $user) {
    if ($user->active) {
        $names[] = $user->name;
    }
}
```

This is longer, and it is better. Anyone can read it top to bottom and know exactly what
happens: for each user, if they're active, keep their name. There's no null to reason
about and no re-indexing. When the rule changes next month, you edit one obvious `if`.

There's a quiet bonus that only shows up later. Change the rule in the loop and the diff is
one line; a reviewer sees precisely what moved. The stacked `array_filter` version would
surface as a rewritten blob even for a one-word change, and `git blame` would pin the whole
expression to that commit. Readable code stays readable in the tools you use to review it.

## Common mistake

The usual trap is treating "clever" and "good" as the same thing. A one-liner that packs
three operations together is not a sign of skill - it's a bill the team pays every time
they read it. Reach for the compact form only when it is genuinely clearer, not because it
looks more advanced.

The other trap is solving problems you don't have yet. Adding layers, options and
abstractions "just in case" is the opposite of KISS. That habit has its own name, YAGNI,
and gets its own lesson later in this chapter.

## FAQ

### Does KISS mean I should avoid functions like `array_map`?

No. Those functions are fine and often the clearest choice. KISS is about the *result*
being easy to read. If a single `array_map` says what you mean, use it. The problem in the
example was stacking three transformations into one unreadable expression.

### Isn't more code a bad thing?

Not on its own. Fewer characters is not the goal - fewer things to understand is. A little
more code that reads plainly is simpler, in the sense that matters, than a dense line you
have to decode.

### How do I know if my code is "simple enough"?

A good test: could a teammate who has never seen this code explain what it does after one
read, without asking you? If yes, it's simple enough. If they need a walkthrough, it's a
candidate for simplifying.
