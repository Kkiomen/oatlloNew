---
title: "Seeing changes with git diff"
slug: seeing-changes
seo_title: "git diff & git diff --staged: See What Changed"
seo_description: "See exactly what changed in Git: git diff shows unstaged edits, git diff --staged shows what's ready to commit. Read the diff output line by line."
---

## What git diff answers

[git status](/course/git-basics/everyday-git/checking-status) tells you *which* files
changed. `git diff` tells you *what* changed inside them, line by line. Before you stage
or commit, it's how you review your own work.

```bash
git diff
```

## Working directory vs staged

This is the key idea, and it trips people up. Plain `git diff` compares your working
directory against the **staging area**. In other words, it shows changes you have **not
staged yet**.

Once you `git add` a change, it disappears from plain `git diff` - because it's now
staged, not "unstaged work". To see staged changes, you ask for them explicitly:

```bash
git diff --staged
```

So the two views are:

- `git diff` - what you've changed but **not** staged yet.
- `git diff --staged` - what you've staged and are **about to commit**.

(`git diff --cached` is the same as `--staged` - just an older name for it.)

## Reading git diff output

Say you changed one line in `greeting.txt`. Plain `git diff` shows:

```text
diff --git a/greeting.txt b/greeting.txt
index e69de29..b6fc4c6 100644
--- a/greeting.txt
+++ b/greeting.txt
@@ -1 +1 @@
-Hello world
+Hello there
```

Focus on the last lines:

- A line starting with `-` (red) was **removed**.
- A line starting with `+` (green) was **added**.
- The `@@ ... @@` line marks which line numbers the change touches.

So here, `Hello world` became `Hello there`. Editing a line shows up as one removal plus
one addition - Git doesn't track "edits", only lines gone and lines added.

One blind spot to remember: `git diff` says nothing about brand-new untracked files.
There's no earlier version to compare them against, so a diff has nothing to show. That's
what `git status` is for - it lists them as untracked even when the diff looks empty.

## Review the staged diff before committing

Make this a habit: before committing, glance at the staged diff to confirm you're
committing exactly what you think:

```bash
git diff --staged
```

If a stray change or a debug line shows up, you'll catch it here instead of in history.
It pairs naturally with
[committing specific files](/course/git-basics/everyday-git/committing-specific-files).

## FAQ

### Why does git diff show nothing after I ran git add?

Because plain `git diff` only shows **unstaged** changes. Once staged, your change moves
out of that view. Use `git diff --staged` to see it.

### What's the difference between --staged and --cached?

Nothing - they're two names for the same option. `--staged` is the clearer, more modern
spelling; `--cached` is older but still works.

### How do I quit the diff view?

Press `q`, just like with `git log`. Long diffs open in the same scrollable pager.
