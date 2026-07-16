---
title: "Fetching and pulling"
slug: fetching-and-pulling
seo_title: "git fetch vs git pull: The Difference Explained"
seo_description: "The difference between git fetch and git pull: fetch downloads new commits without touching your files; pull fetches and merges them into your branch."
---

Pushing sends your commits up. Getting other people's commits *down* is the other half,
and Git gives you two commands for it: **`git fetch`** and **`git pull`**. The names look
interchangeable and they are not. The whole difference comes down to one question: does
the command touch the files you're editing, or leave them alone?

## Remote-tracking branches

First, one idea that makes everything else click. Your local repository keeps a
read-only bookmark of where each remote branch was the last time you talked to the
server. For `main` on `origin`, that bookmark is called **`origin/main`** - a
**remote-tracking branch**.

`origin/main` is *your* record of the remote, not the remote itself. It only updates
when you contact the server. Your own `main` and the remote's `main` are separate things;
`origin/main` sits in between as your local snapshot of the remote.

## git fetch: download, don't touch

```bash
git fetch
```

`git fetch` contacts the remote and downloads any new commits, updating `origin/main`.
It does **not** change your working files or your local `main`. Nothing you're editing
moves. It's the safe, no-surprises option: you learn what's new without anything shifting
under you.

After fetching, you can see whether you're behind:

```text
Your branch is behind 'origin/main' by 3 commits, and can be fast-forwarded.
```

You can inspect what changed (for example with `git log`) and then decide to merge those
commits into your branch when you're ready. A precise way to see exactly what's waiting:
`git log main..origin/main` lists the commits `origin/main` has that your `main` doesn't.
That's the review step `git pull` skips by merging immediately.

## git pull: fetch and merge

```bash
git pull
```

`git pull` is a shortcut for two steps done back to back: **fetch**, then **merge**
`origin/main` into your current branch. So it downloads the new commits *and*
immediately combines them with your work, updating the files in front of you.

You met merging in
[merging branches](/course/git-basics/branching-and-merging/merging-branches) - pull uses
the exact same machinery, just with a remote branch as the other side. If the incoming
commits don't overlap with yours, the merge is automatic. If they touch the same lines
you changed, you get a **merge conflict**, resolved the same way as in
[resolving merge conflicts](/course/git-basics/branching-and-merging/resolving-merge-conflicts).

## Which should you use?

- Use **`git pull`** for the everyday case: "get me up to date". Most of the time this
  is what you want, especially before you start new work.
- Use **`git fetch`** when you want to *see* what's on the remote before merging it -
  reviewing incoming changes, or just checking whether you're behind.

A common safe habit: `git fetch`, look at what came in, then `git pull` (or merge) once
you're happy.

## Common mistake

Do not run `git pull` with uncommitted changes sitting in your working directory,
expecting it to be harmless. If the incoming commits touch files you've edited but not
committed, Git refuses or things get messy. Commit (or stash, covered later) your work
first, *then* pull. A clean working directory makes pulling painless.

## FAQ

### Is git pull really just fetch plus merge?

Yes, by default. `git pull` runs `git fetch` and then merges the fetched commits into
your current branch. Knowing that makes its behaviour much easier to predict.

### Does git fetch ever change my files?

No. Fetch only updates your remote-tracking branches like `origin/main`. Your working
files and local branch stay exactly as they were until you merge or pull.

### How do I check if there's anything new without changing my work?

Run `git fetch`, then `git status` or `git log`. Fetch is read-only for your working
directory, so it's completely safe to run any time.
