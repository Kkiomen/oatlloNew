---
title: "Forking a repository"
slug: forking-a-repository
seo_title: "Fork a Repo on GitHub: Clone, upstream, Sync, PR"
seo_description: "Learn to fork a GitHub repository: clone your fork, add an upstream remote, keep your fork in sync with the original, and open a pull request from a fork."
---

## Why fork instead of branch?

In the [feature branch workflow](/course/git-basics/collaborating/the-feature-branch-workflow)
you branch inside a repository you have write access to. But what about a project you
*don't* own - say an open-source library you want to contribute to? You can't push
branches to it.

You **fork the GitHub repository** instead. A fork is your own copy of someone else's
repository, living under your GitHub account. You have full control over your fork, and you
contribute back through a pull request. This is how most open-source contributions happen.

## Fork and clone

On GitHub, open the original repository and click **Fork** (top right). GitHub creates a
copy at `github.com/you/project`. Now clone *your fork* to your machine:

```bash
git clone https://github.com/you/project.git
cd project
```

You met [cloning](/course/git-basics/remotes-and-github/cloning-a-repository) in Chapter 4.
Cloning your fork gives you a remote named `origin` that points at *your* copy - which is
exactly what you want to push to.

## Add an upstream remote

Your fork doesn't automatically know about the original project. To pull in future
changes from it, add a second [remote](/course/git-basics/remotes-and-github/what-is-a-remote)
pointing at the original repository. By convention it's called `upstream`:

```bash
git remote add upstream https://github.com/original-owner/project.git
git remote -v
```

You now have two remotes:

```text
origin    https://github.com/you/project.git (your fork)
upstream  https://github.com/original-owner/project.git (the original)
```

`origin` is where you push. `upstream` is where you pull the latest project changes from.

## Keep your fork in sync

Open-source projects keep moving, so before starting new work, refresh your `main` from
`upstream`:

```bash
git switch main
git pull upstream main
git push origin main
```

That pulls the original project's latest `main` into your local copy, then pushes it up to
your fork so `origin` stays current too.

## Contribute with a pull request from your fork

Working on a fork is the same loop you already know. Branch, commit, push to your fork:

```bash
git switch -c fix-typo-in-docs
git commit -am "Fix typo in installation docs"
git push -u origin fix-typo-in-docs
```

Then, on GitHub, open a [pull request](/course/git-basics/collaborating/pull-requests).
GitHub is smart about forks: it offers to open the PR against the **original** repository,
with your fork's branch as the source. The maintainers review it and, if they like it,
merge it into the real project.

The PR form has an "Allow edits by maintainers" checkbox, ticked by default. Leave it on:
it lets a maintainer push a small fix straight to your branch instead of bouncing the PR
back for a one-line change. They're pushing to *your* fork's branch, so it stays your
contribution.

## A common mistake

**Forgetting to sync before starting new work.** If your fork's `main` is months behind
`upstream`, every branch you cut starts from stale code and your PRs conflict. Run
`git pull upstream main` before each new contribution.

## FAQ

### What's the difference between a fork and a clone?

A fork is a copy of the repository on *GitHub*, under your account. A clone is a copy on
*your computer*. Typically you fork on GitHub first, then clone your fork locally.

### Why is it called "upstream"?

Because the original project is "up the stream" from your fork - changes flow down from it
to you. `upstream` is just the conventional name; you could call the remote anything, but
sticking to the convention makes tutorials and teammates easier to follow.

### Do I need a fork if I have write access to the repo?

No. If you can push branches to the repository directly, use the plain feature branch
workflow. Forks are for contributing to projects you don't have write access to.
