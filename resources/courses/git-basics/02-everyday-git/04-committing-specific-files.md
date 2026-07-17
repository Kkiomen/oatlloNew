---
title: "Committing only specific files"
slug: committing-specific-files
seo_title: "Commit Only Specific Files in Git, Not Everything"
seo_description: "Commit only chosen files in Git: stage named files, commit a path directly, or use git add -p to stage selected hunks within a single file."
---

## Why you shouldn't commit everything at once

Real work is messy. You fix a bug, tweak some config, and jot a note in a scratch file,
all in one sitting. Those three things don't belong in one commit. To commit only
specific files in Git, the rule is simple: good history means each commit is one focused
change.

You already own the tool for this - `git add`. The trick is to stage narrowly and never
let a "commit everything" shortcut slip in.

## Stage the files you want, by name

The cleanest approach: stage exactly the files that belong together, then commit.

```bash
git add src/login.js src/auth.js
git commit -m "Fix login redirect"
```

Your scratch file and unrelated config edits stay unstaged, out of this commit. Run
[git status](/course/git-basics/everyday-git/checking-status) to confirm only what you
intended is staged before committing.

## Commit a path directly

You can also hand paths straight to `git commit`. It commits the current changes in
those paths, even if you didn't stage them first:

```bash
git commit src/login.js -m "Fix login redirect"
```

This is a handy shortcut for "just commit this one file". It ignores whatever else is
staged and commits only the listed paths.

## Stage part of a file with git add -p

Sometimes two unrelated changes live in the **same file**. You don't want to commit the
whole file - just one section. The `-p` (patch) flag walks you through each change, or
"hunk", and asks whether to stage it:

```bash
git add -p src/app.js
```

Git shows one hunk at a time and prompts:

```text
Stage this hunk [y,n,q,a,d,s,e,?]?
```

The ones you'll use most:

- `y` - yes, stage this hunk
- `n` - no, skip it
- `s` - split into smaller hunks (great when two edits are close together)
- `q` - quit

Say `y` to the bug fix and `n` to the unrelated tweak. Now only the part you approved is
staged, and you can commit it on its own.

One limit worth knowing: `git add -p` only works on files Git already tracks. A brand-new
untracked file has no earlier version to diff against, so patch mode skips it - you stage
those the plain way, by name.

## Common mistake

The two commands that quietly commit **everything** are the ones to avoid here:

- `git add .` stages every change in the folder.
- `git commit -a` automatically stages every **tracked** file before committing.

Both are convenient, and both are the exact opposite of what this lesson is about. If
you're trying to commit only some of your changes, reaching for `-a` or `add .` will
sweep up the rest without warning. Stage by name (or by hunk) instead, and check
`git status` before you commit.

## FAQ

### What does git commit -a do?

It stages all **tracked, modified** files and commits them in one step. It does not
include untracked files. It's a shortcut for "commit everything I've already been
tracking" - useful, but not when you want a partial commit.

### What is a hunk?

A hunk is a contiguous block of changed lines that Git treats as one unit. `git add -p`
lets you accept or reject each hunk separately, so you can stage part of a file.

### Can I check what's staged before committing?

Yes - `git status` lists staged versus unstaged files, and `git diff --staged` (a
[later lesson](/course/git-basics/everyday-git/seeing-changes)) shows the exact staged
changes.
