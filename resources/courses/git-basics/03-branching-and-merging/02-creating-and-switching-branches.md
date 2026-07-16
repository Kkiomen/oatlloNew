---
title: "Creating and switching branches"
slug: creating-and-switching-branches
seo_title: "Create and Switch Git Branches with git switch -c"
seo_description: "Create a branch with git branch, move onto it with git switch, do both at once with git switch -c, and list your branches. Plus how git checkout fits in."
---

A branch is a movable pointer to a commit. Now you'll make one and start working on
it. Modern Git uses `git switch` to move between branches, and `git switch -c` to
create and switch in a single step.

## Create a branch with git branch

To create a new branch based on where you are right now, use `git branch` with a
name:

```bash
git branch new-feature
```

This makes a branch called `new-feature` pointing at your current commit. But it
does **not** move you onto it - you're still on `main`. Creating and switching are
two separate actions here.

Branch names have no spaces. Use hyphens or slashes, like `fix-login` or
`feature/signup`.

## Switch to a branch with git switch

To move onto the branch you just made, use `git switch`:

```bash
git switch new-feature
```

Now `git status` will say `On branch new-feature`. Any commits you make from here
move `new-feature` forward and leave `main` where it was.

To go back to `main`, switch again:

```bash
git switch main
```

One shortcut worth knowing early: `git switch -` jumps back to the branch you were on
last, the same way `cd -` returns to your previous directory. When you're bouncing
between a feature branch and `main`, you rarely need to type the name.

## Create and switch in one step

Most of the time you want to create a branch and jump onto it immediately. The `-c`
flag (for "create") does both at once:

```bash
git switch -c new-feature
```

That's the same as running `git branch new-feature` followed by
`git switch new-feature`. This is the command you'll use most.

## The older way: git checkout

Before `git switch` existed (added in Git 2.23), people used `git checkout` for this,
and you'll still see it everywhere in tutorials and answers online:

```bash
git checkout new-feature
git checkout -b new-feature
```

`git checkout -b` is the old equivalent of `git switch -c`. Both still work today.
`git checkout` does many other things too (it can also change files), which is exactly
why the clearer, single-purpose `git switch` was introduced. Prefer `git switch` for
moving between branches.

## List your branches

To see all your branches, run:

```bash
git branch
```

Git prints one branch per line and marks the one you're currently on with an
asterisk:

```text
* new-feature
  main
```

## Commit your changes first

Before you switch away from a branch, commit your work. If you have uncommitted
changes that would collide with the branch you're switching to, Git stops you with an
error rather than losing anything. A clean `git status` (see
[checking status](/course/git-basics/everyday-git/checking-status)) means you're safe
to switch.

## Common mistake

A very common trip-up is running `git branch new-feature` and assuming you're now on
it. You aren't - `git branch` only creates. You keep committing to `main` by accident.
Use `git switch -c` to create and move in one go, and glance at `git status` to
confirm which branch you're on before you start working.

## FAQ

### What's the difference between git switch and git checkout?

`git switch` only changes branches, which makes it clear and safe. `git checkout` is
older and does several unrelated jobs, including switching branches and restoring
files. Both switch branches; `git switch` is the modern, less error-prone choice.

### How do I create and switch in one command?

Use `git switch -c branch-name`. The `-c` flag creates the branch and moves you onto
it. The older equivalent is `git checkout -b branch-name`.

### How do I see which branch I'm on?

Run `git branch` and look for the asterisk, or run `git status` and read the first
line. Both tell you your current branch.
