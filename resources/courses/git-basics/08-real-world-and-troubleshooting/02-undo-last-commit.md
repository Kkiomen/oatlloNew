---
title: "Undo last commit"
slug: undo-last-commit
seo_title: "How to undo the last commit in Git (3 ways)"
seo_description: "Undo your last Git commit: keep the changes with git reset --soft, discard them with git reset --hard, or safely undo a pushed commit with git revert."
---

## The problem: undo the last commit

You ran `git commit` and instantly regretted it - wrong files, wrong moment, or you just
want to redo it cleanly. Here's the catch: "undo the last commit" means three different
things, and the wrong one throws away work you can't get back. Below are all three, ordered
from safest to most destructive.

## Keep the changes: git reset --soft HEAD~1

This is what people usually want. It removes the commit but keeps every change staged, as
if you never committed:

```bash
git reset --soft HEAD~1
```

`HEAD~1` means "one commit before the current one." After this, your files are untouched
and your changes are staged, ready to be committed again (perhaps with a better message
or split into smaller commits). Nothing is lost.

Worth knowing: `--soft` leaves everything staged. If you'd rather review the changes and
re-stage them piece by piece, use `git reset HEAD~1` with no flag - that's `--mixed`, the
default, which keeps the same files but unstages them. Same commit removed, different
starting point for your next commit.

## Discard the changes: git reset --hard HEAD~1

If you want the commit **and** its changes gone completely, back to the previous state:

```bash
git reset --hard HEAD~1
```

**DESTRUCTIVE:** `--hard` throws away the changes in that commit permanently. Anything
that wasn't committed anywhere else is gone. Only use this when you're sure you don't
want those changes. If you run it by accident, [the reflog](/course/git-basics/undoing-things/recovering-with-reflog)
can often bring the commit back - but don't rely on it.

## Safe on shared branches: git revert HEAD

The two commands above **rewrite history** - they delete the commit. That's fine while
the commit lives only on your machine, but dangerous once you've pushed it, because
everyone else still has the old commit. On a shared branch, undo differently:

```bash
git revert HEAD
```

`git revert` doesn't delete anything. It creates a **new** commit that reverses the
changes of the last one. History stays intact and grows forward, so pushing it never
conflicts with what your teammates already have. This is the correct choice for any
commit you've already pushed.

## Which one should I use?

- Not pushed, want to redo the commit: `git reset --soft HEAD~1`.
- Not pushed, want the changes gone: `git reset --hard HEAD~1` (destructive).
- Already pushed / shared branch: `git revert HEAD`.

These build directly on [Chapter 6](/course/git-basics/undoing-things/git-reset-explained),
where reset's three modes and revert are covered in depth. If you only need to fix the
message or add a file, [amending](/course/git-basics/undoing-things/amending-the-last-commit)
is lighter than undoing the whole commit.

## FAQ

### How do I undo the last commit but keep my changes?

Run `git reset --soft HEAD~1`. The commit is removed and all its changes stay staged, so
you can immediately commit again with a better message or in smaller pieces.

### What's the difference between reset --soft and reset --hard?

`--soft` keeps your changes (staged); `--hard` deletes them. Soft is safe and reversible
in practice; hard permanently discards the work in that commit, so use it carefully.

### How do I undo a commit I already pushed?

Use `git revert HEAD`. It adds a new commit that undoes the last one without rewriting
history, so it's safe on branches other people share. Avoid `reset` on pushed commits.

### What does HEAD~1 mean?

`HEAD` is your current commit; `HEAD~1` is the one right before it. So resetting to
`HEAD~1` moves you back by exactly one commit.
