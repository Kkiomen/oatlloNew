---
title: "The feature branch workflow"
slug: the-feature-branch-workflow
seo_title: "Git Feature Branch Workflow: Branch, PR, Merge"
seo_description: "Learn the Git feature branch workflow: branch off main per feature, commit, push, open a pull request and merge back - and why you never commit to main."
---

## Why not just commit to main?

The **Git feature branch workflow** is the habit every team eventually lands on, and it
starts with a question. When you work alone on a toy project, committing straight to `main`
feels fine. On a real project - especially with other people - it causes problems fast:

- Half-finished work sits on the branch everyone depends on.
- There's no natural place for someone to review your change before it ships.
- If two people push to `main` at the same time, they trip over each other.

The fix is a simple, universal habit: **one branch per feature**. You do your work on
its own branch, and `main` always stays in a known-good state.

## The workflow, step by step

Say you're adding a login page. Start from an up-to-date `main`.

```bash
git switch main
git pull origin main
git switch -c add-login-page
```

You already met [branches](/course/git-basics/branching-and-merging/what-is-a-branch)
and [switching](/course/git-basics/branching-and-merging/creating-and-switching-branches)
in Chapter 3. `git switch -c` creates the branch and moves you onto it in one step.

Now do the work: edit files, then stage and commit as usual. Commit in small,
meaningful steps rather than one giant commit.

```bash
git add .
git commit -m "Add login form and validation"
```

When the feature is ready (or you just want it backed up), push the branch to GitHub:

```bash
git push -u origin add-login-page
```

The `-u` sets the upstream, so future pushes on this branch are just `git push`. You
saw pushing in [Chapter 4](/course/git-basics/remotes-and-github/pushing-changes).

## Open a pull request and merge back

Once the branch is on GitHub, you open a **pull request** (PR): a request to merge your
branch into `main`. Teammates review it, and when it's approved it gets merged. The next
lesson covers pull requests in detail.

After the merge, `main` contains your feature. You update your local `main` and delete
the branch you no longer need:

```bash
git switch main
git pull origin main
git branch -d add-login-page
```

That's the whole loop: branch, commit, push, PR, merge, clean up. Repeat it for every
feature.

One detail worth knowing: lowercase `git branch -d` refuses to delete a branch whose work
hasn't been merged yet, and prints a warning instead. That's a safety net, not an obstacle -
if the delete goes through cleanly, it's confirmation the merge actually landed.

## A common mistake

**Branching off an outdated main.** If your local `main` is days behind, your new branch
starts from old code, and merging later is messier. Always `git pull origin main` right
before you create a feature branch, so you branch off the latest work.

## FAQ

### What should I name my branch?

Something short and descriptive: `add-login-page`, `fix-checkout-bug`,
`update-readme`. Many teams add a prefix like `feature/` or `fix/`. Consistency matters
more than the exact scheme.

### How big should one feature branch be?

Small enough to review comfortably. A branch that changes 15 files across three
features is hard to review. One focused change per branch keeps PRs quick to approve.

### Do I have to push before opening a pull request?

Yes. A pull request is created from a branch that exists on GitHub, so the branch has to
be pushed first. Pushing also backs up your work off your laptop.
