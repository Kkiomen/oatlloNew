---
title: "Discard changes in Git"
slug: discarding-changes
seo_title: "Discard changes in Git with git restore"
seo_description: "How to discard uncommitted changes in Git with git restore. A destructive command with no undo - the changes are gone for good, so read first."
---

## How do I discard uncommitted changes in Git?

You've been editing a file and the changes turned out to be a mistake. You don't want to
commit them - you want the file back exactly as it was at the last commit. That's how you
discard changes in Git, and the tool is `git restore`.

One warning up front. Unlike unstaging, discarding actually **deletes** your uncommitted
work.

## DESTRUCTIVE: git restore throws changes away

To reset one file back to its last committed state:

```bash
git restore config.php
```

After this, all your uncommitted edits to `config.php` are **gone**. The file matches the
last commit again.

**This is a destructive command. There is no undo.** The changes were never committed, so
Git has no copy of them - the reflog and every other recovery tool in this chapter work on
*commits*, and uncommitted work isn't a commit. Once discarded, it's gone. Only run this
when you are sure you don't want the changes.

## Discard everything with git restore .

To throw away all uncommitted changes in the whole working directory:

```bash
git restore .
```

This is even more destructive - it wipes uncommitted edits across **every** tracked file
at once. Run `git status` first to see exactly what you're about to lose:

```bash
git status
```

Everything listed under "Changes not staged for commit" will be discarded.

Worth knowing: `git restore .` only touches files Git already tracks. Brand-new files
Git isn't watching yet (your untracked ones) survive it - `git restore` has nothing
committed to restore them to. Clearing those out is a separate, even sharper command
(`git clean`), not covered here.

## A safer habit before discarding

Since there's no way back, get in the habit of pausing before you run `git restore`. Two
quick safety checks:

- Run `git diff` to see the exact changes you're about to delete.
- If you're even slightly unsure, [stash](/course/git-basics/undoing-things/stashing-work)
  the changes instead. `git stash` tucks them away safely and you can bring them back later
  if you change your mind. It's the non-destructive alternative to discarding.

## Common mistake

Confusing `git restore file` with `git restore --staged file`. They read almost the same,
but they do opposite-risk things: `--staged` only unstages and keeps your work; without
`--staged`, it deletes your work. If a file is both staged and modified, you may need to
unstage first, then discard. When in doubt, check `git status` - it tells you which state
the file is in.

## FAQ

### How do I discard uncommitted changes in Git?

Run `git restore <file>` for one file, or `git restore .` for everything. This is
destructive - the uncommitted changes are permanently deleted, so be sure first.

### Can I get back changes I discarded with git restore?

No. Discarded changes were never committed, so Git kept no copy. Unlike a bad reset, there
is nothing in the reflog to recover. This is why stashing is safer when you're unsure.

### What's the difference between discarding and unstaging?

Unstaging (`git restore --staged file`) keeps your changes and only removes them from the
staging area. Discarding (`git restore file`) deletes your changes entirely.
