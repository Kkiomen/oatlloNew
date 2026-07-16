---
title: "Cherry-picking commits"
slug: cherry-picking
seo_title: "Git Cherry-Pick: Copy a Single Commit"
seo_description: "Git cherry-pick copies one specific commit onto your current branch by its hash. When to use it, how to handle a cherry-pick conflict, and common mistakes."
---

Some days you don't want a whole branch, just **one specific commit** off it. A bug fix
landed on a feature branch and you need it on `main` right now, without dragging the
half-built rest along. `git cherry-pick` copies a single commit onto your current branch.

## The command

Find the commit's hash (from `git log`), switch to the branch that should receive it,
and cherry-pick it:

```bash
git switch main
git cherry-pick a1b2c3d
```

Git takes the changes from commit `a1b2c3d` and applies them as a **new commit** on
`main`. The original commit stays where it was, untouched.

## What actually happens

Say you have a fix on a feature branch that `main` doesn't have:

```text
A - B - C                 <- main (you are here)
     \
      D - E - F           <- feature (E is the fix you want)
```

You only want commit `E`, not `D` or `F`. Cherry-pick it onto `main`:

```bash
git cherry-pick E
```

```text
A - B - C - E'            <- main (E' is a copy of E)
     \
      D - E - F           <- feature (unchanged)
```

`E'` has the same changes as `E` but a **new commit ID**, because it has a different
parent. This is the same idea you saw with rebase: copying changes onto a new base
makes new commits.

Because `E'` is a brand-new commit, nothing on it points back to the original. Six months
later, staring at `main`, you can't tell `E'` came from the feature branch at all. That's
what `git cherry-pick -x` fixes: it appends a `(cherry picked from commit ...)` line to
the message, so the trail survives. Handy the moment you cherry-pick a fix across release
branches and later need to prove where it started.

## When cherry-picking is useful

- **Hotfix to a release.** A critical fix landed on your development branch, and you
  need just that one commit on a stable release branch, without pulling in unfinished
  work.
- **You committed to the wrong branch.** You made a commit on `main` that belonged on a
  feature branch. Cherry-pick it onto the feature branch, then remove it from `main`
  with a reset (see [git reset explained](/course/git-basics/undoing-things/git-reset-explained)).
- **Grabbing one useful commit** from a branch you don't want to merge in full.

## Picking more than one

You can cherry-pick several commits at once by listing them, or a continuous range:

```bash
git cherry-pick a1b2c3d f4e5d6c
git cherry-pick a1b2c3d..f4e5d6c
```

The range form `A..B` picks everything after `A` up to and including `B`.

## Handling conflicts

Cherry-pick can hit a conflict if the surrounding code has changed - just like a merge.
The workflow is the same one you learned in
[resolving merge conflicts](/course/git-basics/branching-and-merging/resolving-merge-conflicts):
edit the files, stage them, then continue.

```bash
# fix the conflicted files, then:
git add .
git cherry-pick --continue
```

To bail out and leave your branch as it was:

```bash
git cherry-pick --abort
```

## Common mistake

Cherry-picking a commit that later gets **merged** into the same branch anyway. Now the
same change exists as two different commits, which can cause confusing conflicts when
the branches finally meet. Cherry-pick is for one-off copies; if you find yourself
picking many commits, you probably want a merge or a rebase instead.

## FAQ

### Does cherry-pick move the commit or copy it?

It copies it. The original commit stays on its branch; a new commit with the same
changes (and a new ID) is added to your current branch.

### How do I find the commit hash?

Run `git log` (or `git log --oneline`) on the branch that has the commit. The short
hash at the start of each line is what you pass to `git cherry-pick`. You saw this in
[viewing history](/course/git-basics/everyday-git/viewing-history).

### Can I cherry-pick from another branch without switching to it?

Yes. You cherry-pick **onto** your current branch, but the source commit can live on any
branch, as long as you have its hash.

### What if the same change is already on my branch?

Git may report that the result is empty because there's nothing new to apply. You can
skip it with `git cherry-pick --skip`.
