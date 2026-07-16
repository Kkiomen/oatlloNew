---
title: "Merging branches"
slug: merging-branches
seo_title: "Git Merge: Fast-Forward vs Merge Commit Explained"
seo_description: "How git merge works: bring a feature branch into main, tell a fast-forward apart from a merge commit, and check the result with git log --oneline."
---

You've built a feature on its own branch. Now you want that work back in `main`.
Folding one branch into another is called **merging**, and `git merge` is the tool
that does it.

## The goal: bring a branch back into main

Say you branched off `main`, did some commits on `new-feature`, and you're happy with
it. You want `main` to include those commits so it becomes your new, improved main
line of work.

The rule to remember: **you merge into the branch you're currently on**. So to bring
`new-feature` into `main`, you first switch to `main`, then merge `new-feature` in.

## How to merge, step by step

First switch to the branch that should receive the changes:

```bash
git switch main
```

Then merge the other branch into it:

```bash
git merge new-feature
```

That's it. `main` now contains the work from `new-feature`. Depending on the history,
Git does this one of two ways, described below.

## Fast-forward: just move the pointer

If `main` hasn't changed since you branched off it, then `main` is simply "behind"
`new-feature` on the same line. Git doesn't need to do anything clever - it just
slides the `main` pointer forward to catch up.

```text
before:   A - B   <- main
               \
                C - D   <- new-feature

after:    A - B - C - D   <- main, new-feature
```

This is called a **fast-forward merge**. There's no new commit; `main` just points at
the same commit `new-feature` does. It's the cleanest possible outcome.

## A merge commit: joining two lines

If `main` *did* move on while you were working (say a teammate added a commit, or you
committed to `main` yourself), the history has actually split into two lines. Git
can't just slide a pointer forward, because both branches have new commits the other
doesn't.

Instead Git creates a special **merge commit** that ties the two lines back together.
A merge commit is unusual because it has two parents - one from each branch:

```text
A - B - E       <- main moved on
     \     \
      C - D - M   <- M is the merge commit
```

Git may open your editor to confirm a merge message; the default is fine, so save and
close it. Now `main` points at `M`, which contains both lines of work.

If you'd rather always get that merge commit - even when a fast-forward was possible -
merge with `git merge --no-ff new-feature`. It forces the extra commit so the feature
stays visible as its own group in the history instead of blending into a straight
line. Teams that want each feature to be an obvious unit reach for this a lot.

## Check the result

After merging, look at the history to see what happened:

```bash
git log --oneline
```

You'll see the commits from your feature branch now sitting in `main`. A merge commit,
if there was one, shows up as its own entry. For more on reading history, see
[viewing history](/course/git-basics/everyday-git/viewing-history).

## Common mistake

The most common merge mistake is being on the wrong branch. `git merge` pulls the
named branch **into your current branch**. If you're on `new-feature` and run
`git merge main`, you'll merge `main` into your feature, not the other way around.
Always run `git status` first and confirm you're on the branch that should *receive*
the changes (usually `main`).

## FAQ

### What's the difference between a fast-forward and a merge commit?

A fast-forward happens when the target branch hasn't moved, so Git just advances its
pointer with no new commit. A merge commit is created when both branches have new
commits; it joins the two lines and has two parents.

### Which branch do I need to be on to merge?

Be on the branch you want to receive the changes. To bring `new-feature` into `main`,
switch to `main` first, then run `git merge new-feature`.

### Does merging delete the feature branch?

No. After a merge the feature branch still exists, pointing where it did. You delete
it separately once you're done, which is covered later in this chapter.
