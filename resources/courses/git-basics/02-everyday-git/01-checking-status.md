---
title: "Checking status with git status"
slug: checking-status
seo_title: "git status: Read Untracked, Modified & Staged Files"
seo_description: "Read git status output line by line: tell untracked, modified and staged files apart so you always know your Git repository's exact state."
---

## Why git status is your home base

What changed? Before every stage and every commit, that's the question, and
`git status` answers it. It's the safest command in Git. It only reads, never writes,
so run it as often as you like.

```bash
git status
```

One quick payoff: run it in a folder that isn't a repository and Git replies "not a git
repository". That's a fast way to confirm your `git init` actually took.

## Reading git status output

In [the previous chapter](/course/git-basics/getting-started/the-three-areas) you met
the three areas: working directory, staging area, and repository. `git status` shows
you where each changed file currently sits.

A file falls into one of these groups:

- **Untracked** - a new file Git has never seen. Git isn't watching it yet.
- **Modified** - a tracked file you've edited, but the change isn't staged.
- **Staged** - a change you've marked to go into the next commit.

Here's a typical output on a clean repository:

```text
On branch main
nothing to commit, working tree clean
```

And here's one with changes:

```text
On branch main
Changes to be committed:
  (use "git restore --staged <file>..." to unstage)
        modified:   index.html

Changes not staged for commit:
  (use "git add <file>..." to update what will be committed)
  (use "git restore <file>..." to discard changes in working directory)
        modified:   style.css

Untracked files:
  (use "git add <file>..." to include in what will be committed)
        notes.txt
```

Read it top to bottom:

- **Changes to be committed** = staged. `index.html` will go into your next commit.
- **Changes not staged for commit** = modified but not staged. `style.css` was edited
  but won't be committed yet.
- **Untracked files** = brand new. `notes.txt` isn't tracked at all.

Notice Git spells out the commands to move files between groups. It's a helpful guide,
not just a report. The order is always the same too: staged, then unstaged, then
untracked, top to bottom.

## The short status format (git status -s)

Once you're comfortable, the short format packs the same info onto one line per file.

```bash
git status -s
```

```text
M  index.html
 M style.css
?? notes.txt
```

The two columns are staged (left) and unstaged (right). `??` means untracked. It's the
same story, just compact.

## FAQ

### How often should I run git status?

As often as you want. It changes nothing, so run it before staging, before committing,
and any time you're unsure what state you're in.

### What does "working tree clean" mean?

It means there are no changes at all - nothing untracked, modified, or staged. Your
working directory matches your last commit.
