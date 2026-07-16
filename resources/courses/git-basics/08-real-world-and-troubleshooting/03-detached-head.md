---
title: "Detached HEAD"
slug: detached-head
seo_title: "You are in 'detached HEAD' state - how to fix it"
seo_description: "What 'You are in detached HEAD state' means in Git, how you ended up there, and how to get back to a branch with git switch - keeping or discarding your work."
---

## The problem: "You are in 'detached HEAD' state"

You ran a Git command and got a wall of text starting with:

```text
You are in 'detached HEAD' state. You can look around, make experimental
changes and commit them...
```

It reads like a warning, but nothing is broken and nothing is lost. Git has simply parked
you in an unusual spot. You just need to know how to get back to a branch.

## What detached HEAD means

Normally `HEAD` - Git's pointer to "where you are now" - points at a **branch** like
`main`. When you commit, the branch moves forward with you.

In detached HEAD, `HEAD` points **directly at a specific commit** instead of a branch.
You're standing on a single snapshot in history with no branch attached to it. You can
still look around and even make commits, but those commits don't belong to any branch -
so if you switch away, they're easy to lose.

## How you got there

You typically land in detached HEAD by checking out something that isn't a branch:

```bash
git checkout a1b2c3d        # a specific commit hash
git checkout v1.0           # a tag
git checkout origin/main    # a remote-tracking ref directly
```

Each of these points HEAD at a fixed commit rather than a movable branch. It's often
harmless - people do it to inspect an old version of the code.

## The fix: get back to a branch

If you were just looking around and don't need any changes you made here, switch back to
your branch:

```bash
git switch main
```

If you made commits while detached and want to **keep** them, create a branch right where
you are first, so those commits get a home:

```bash
git switch -c my-experiment
```

`git switch -c my-experiment` creates a new branch at your current commit and moves you
onto it. Your work is now safely on a branch and won't be lost. From there you can merge
it, push it, or open a pull request like any other branch (see
[creating and switching branches](/course/git-basics/branching-and-merging/creating-and-switching-branches)).

Already switched away before branching? Don't panic. As long as you still have the commit
hash - Git usually prints it in the "you left commits behind" message, and
[the reflog](/course/git-basics/undoing-things/recovering-with-reflog) keeps it for a while -
you can run `git branch my-experiment <hash>` after the fact to give those commits a home.

## DESTRUCTIVE warning

If you made commits in detached HEAD and switch away with `git switch main` **without**
creating a branch first, those commits are left with nothing pointing to them and Git
will eventually clean them up. If that happens by accident,
[the reflog](/course/git-basics/undoing-things/recovering-with-reflog) can usually recover
them - but the safe move is to branch first with `git switch -c`.

## FAQ

### What does "detached HEAD" mean in Git?

It means HEAD points at a specific commit instead of a branch. You're viewing a fixed
point in history rather than working on a branch, so any new commits aren't attached to a
branch until you create one.

### How do I get out of detached HEAD state?

To discard and return, run `git switch main`. To keep commits you made while detached,
run `git switch -c new-branch` first so your work lands on a real branch.

### Is detached HEAD an error?

No. It's a normal state Git uses when you check out a commit or tag. It only becomes a
problem if you make commits and leave without saving them onto a branch.
