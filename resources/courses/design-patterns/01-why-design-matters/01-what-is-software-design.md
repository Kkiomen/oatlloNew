---
title: "What is software design?"
slug: what-is-software-design
seo_title: "What Is Software Design? Beyond Code That Just Works"
seo_description: "What software design really means: readable code, easy change and low maintenance cost. The difference between code that runs and code you can safely change."
---

**Software design** is how you arrange code so it stays easy to read and change, not
just whether it runs. Two programs can produce the same output while one is a joy to
work with and the other a nightmare.

## Code that works is only half the job

When you're learning to program, there's one goal: make it work. The tests pass, the
page loads, the bug is gone. That's real, and it matters.

But most code is not written once and left alone. It gets read, extended and fixed
long after it was first written - often by someone who isn't you, or by you months
later with no memory of it. Working code that nobody can safely change is a liability,
not an asset.

Design is the part of programming that decides how that later work will feel.

## Three things good design gives you

Good design shows up as three practical qualities, not one vague feeling of "clean".

**Readability.** Someone can open the file and understand what it does without a guided
tour. Names say what they mean. The flow is easy to follow.

**Ease of change.** When a requirement changes, you touch a small, obvious part of the
code. You're not afraid that fixing one thing will break three others.

**Low maintenance cost.** Bugs are easier to find. New people get productive faster.
The code doesn't rot into something everyone avoids.

## A quick example

Both of these work. Only one is easy to live with.

```php
// Works, but hard to read and change
function h($u): string
{
    if ($u['t'] === 1) {
        return $u['n'] . ' (admin)';
    }
    return $u['n'];
}
```

Now the same idea, designed for a human reader:

```php
function displayName(User $user): string
{
    if ($user->isAdmin) {
        return "{$user->name} (admin)";
    }

    return $user->name;
}
```

The second version doesn't do anything cleverer. It just tells you what it means. When
the rules change - say, admins should show a badge instead - you know exactly where to
look.

Here's the uncomfortable part: nothing forced the better version. Both pass the tests,
both ship, both bill the client the same. No compiler error ever punishes a cryptic
name. That's precisely why the first kind piles up in real codebases - the pressure to
fix it only arrives later, when someone has to change it and can't.

## The cost of maintenance

Here's the key insight this whole course rests on: code is read far more often than it
is written, and changed far more often than it is thrown away. Most of the money and
time a codebase costs is spent *after* the first version ships.

Design is how you keep that ongoing cost low. That's why it's worth learning as a skill
of its own, separate from "getting it to work".

## FAQ

### Is software design the same as architecture?

They're related but not the same. Architecture is design at the big scale - how whole
systems and services fit together. In this course "design" mostly means the smaller,
everyday scale: how you shape classes, methods and their relationships.

### Do I need design for small projects?

Even small projects grow. The habits are the same at any size, and they're cheaper to
learn on small code. You don't need heavy structure early - you need clear, honest code.

### Isn't "clean code" just personal taste?

Some of it is taste. But readability, ease of change and maintenance cost are real and
measurable in how long tasks take and how often changes break things. This course
focuses on those, not on style preferences.
