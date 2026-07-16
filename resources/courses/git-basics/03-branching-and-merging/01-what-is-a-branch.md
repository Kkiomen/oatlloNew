---
title: "What is a branch?"
slug: what-is-a-branch
seo_title: "What Is a Git Branch? A Pointer to a Commit"
seo_description: "What a Git branch really is: a lightweight, movable pointer to one commit. Understand HEAD, why main is just a branch, and why branches cost almost nothing."
---

A Git branch is not a copy of your project. It's a lightweight, movable pointer to
one commit - nothing more. Hold onto that single sentence and the rest of branching
stops feeling scary.

## The problem branches solve

So far you've been committing in a straight line, one commit after another. That's
fine until you want to try something without risking your working code - a new
feature, a risky refactor, an experiment.

You don't want to break the version that already works while you tinker. A branch
lets you split off, do your work in isolation, and only combine it back when you're
happy with it. Meanwhile the original stays untouched.

## A branch is a pointer to a commit

Every commit you make has a unique ID and points back to the commit before it. That
chain of commits is your history.

A branch is simply a **name that points to one commit** - the latest one on that
line of work. When you add a new commit, the branch pointer automatically moves
forward to it. That's the whole idea.

```text
A - B - C   <- main
```

Here `main` is a branch pointing at commit `C`. It's just a label sitting on a
commit, nothing more.

## main is just a branch

When you created your first repository, Git made a branch for you called **`main`**.
There's nothing magic about it - it's an ordinary branch that happens to be the
default. (You may see `master` in older projects; it's the same idea with an older
name.)

You can check which branch you're on any time with:

```bash
git status
```

The first line tells you, for example, `On branch main`.

## HEAD: where you are right now

Git needs to know which branch you're currently working on. It tracks that with a
special pointer called **HEAD**. Think of HEAD as "you are here".

Normally HEAD points at a branch, and that branch points at a commit:

```text
HEAD -> main -> C
```

When you make a new commit, `main` moves to the new commit, and because HEAD points
at `main`, you move along with it. When you switch branches (next lesson), HEAD moves
to point at the other branch instead.

## Why branches are so cheap

Older tools made a branch by copying every file - slow and heavy. In Git, a branch
is just a tiny file containing one commit ID. Creating one is instant and costs
almost no space.

That file is real and you can look at it. A branch lives at
`.git/refs/heads/<name>`, and running `cat .git/refs/heads/main` prints the 40-character
commit ID it points at - the entire branch, on disk. Once you've seen that, "a branch
is a pointer" stops being an abstraction.

So the normal Git workflow is to make branches freely: one per feature or fix, use
it, merge it, delete it. They're meant to be created and thrown away without a second
thought.

## Common mistake

Beginners often imagine a branch as a separate folder or a full duplicate of the
project. It isn't. There's only one set of files on disk, and switching branches
just changes what those files contain. Once you picture a branch as a movable label
on a commit, the rest of this chapter falls into place.

## FAQ

### Is a branch a copy of my files?

No. A branch is a pointer to a commit, not a copy. Git keeps a single working
directory and updates it when you switch branches. You don't get extra folders.

### What is HEAD?

HEAD is Git's "you are here" marker. It usually points at the branch you're currently
on, which in turn points at your latest commit on that branch.

### What is the main branch?

`main` is the default branch Git creates in a new repository. It's an ordinary branch;
the name is just a convention for the primary line of work. Older repositories often
call it `master`.
