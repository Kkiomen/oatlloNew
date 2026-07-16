---
title: "What are code smells?"
slug: what-are-code-smells
seo_title: "What Are Code Smells? A Beginner's Guide with Examples"
seo_description: "A code smell is a hint that something may be off in your code, not a rule you broke. What smells are, with named examples like long method and duplicated code."
---

A **code smell** is a surface sign that something *may* be wrong in your code. Like a
smell in a kitchen, it doesn't prove there's a problem - it tells you to go look.

## A hint, not a rule

A smell is not a rule you broke. Your code still compiles and runs. Nothing is
technically "illegal". A smell just says *this spot is worth a second glance*.

That matters because beginners often turn smells into strict laws - "methods must never
be longer than 10 lines" - and then contort good code to satisfy the rule. Smells don't
work that way. They point; you decide.

Sometimes you look and the code is fine as it is. Sometimes you look and find a real
problem hiding. Both are correct outcomes. The value of a smell is that it made you look
at all.

## Some smells you'll hear named

You don't need to fix these yet. Right now, just learn to recognise the names.

**Long method.** A single function that goes on for screens and screens. It usually
means several jobs are crammed into one place - a cohesion problem, in the language of
the last lesson.

**Duplicated code.** The same logic copy-pasted in several spots. When the rule changes,
you have to remember every copy, and you'll miss one.

**Large class.** A class that has grown to hold far too much - many fields, many methods,
many responsibilities.

**Long parameter list.** A method that takes a fistful of arguments, so calling it
correctly is hard and easy to get wrong.

**Magic numbers.** A bare value like `if ($status === 3)` with no name to say what `3`
means.

Here's the long-method and magic-number smell together:

```php
// Smells: this method does several things, and what is 3?
function process(array $order): void
{
    // validate...
    // calculate totals...
    // apply discounts...
    if ($order['status'] === 3) {
        // ...ship...
    }
    // send email...
    // write log...
}
```

Nothing here is broken. But the length and the bare `3` are hints worth following.

A note the naming hides: the real danger of a magic number isn't the missing name in one
spot. It's that the same `3` gets copied into other files, and later someone changes the
meaning of status 3 in one place and forgets the rest. That's duplicated code and a magic
number feeding each other - smells rarely travel alone, and finding one is a good reason
to look for its neighbours.

## Why we don't fix them now

Fixing a smell well means knowing the principle or pattern that replaces it, and you
haven't met those yet. Reaching for a solution before you understand the problem is how
people over-engineer.

So this course does it in the right order: learn the ideas first, then come back to
smells at the end. **The final chapter has a full catalogue of code smells** and the
refactorings that clear them up. For now, it's enough to smell that something's off.

## Common mistake

Treating every smell as a defect to be eliminated on sight. A smell is an invitation to
investigate, not a verdict. Over-reacting to smells produces its own mess - lots of tiny
classes and indirection that are harder to follow than the "smelly" original.

## FAQ

### Are code smells the same as bugs?

No. A bug is behaviour that's wrong - the program does the wrong thing. A smell is about
the *shape* of the code while it may work perfectly. Smells make bugs more likely later,
but they aren't bugs themselves.

### Who invented the term code smell?

It was popularised by Kent Beck and Martin Fowler in the book *Refactoring*. The idea is
deliberately informal: a nose for trouble, not a checklist.

### Should I fix a smell as soon as I see it?

Not automatically. Look first. If the code is about to change anyway, or the smell hides
a real problem, clean it up. If it's stable and clear enough, leaving it alone is a valid
choice.
