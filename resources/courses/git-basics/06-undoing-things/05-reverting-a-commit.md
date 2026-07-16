---
title: "Revert a commit with git revert"
slug: reverting-a-commit
seo_title: "git revert: safely undo a pushed commit"
seo_description: "How to revert a commit with git revert - a new commit that cancels an old one, safe on shared branches, plus how git revert differs from git reset."
---

## How do I revert a commit that's already shared?

A commit has to go, but it's already pushed and your teammates have it. Rewriting that
history with reset would clash with everyone else's copy. What you need is to undo the
*effect* of the commit without deleting it from the timeline. That's `git revert`.

## How git revert works

`git revert` doesn't remove a commit. Instead it creates a **new commit** that applies the
exact opposite changes, cancelling out the original. The old commit stays in history; the
new one undoes it.

```bash
git revert <commit-hash>
```

You can get the hash from `git log`. Git opens your editor with a pre-filled message like
`Revert "the original message"` - save and close to finish. To undo the most recent
commit:

```bash
git revert HEAD
```

Now your history has two commits: the original, and a new one that reverses it. The net
effect on your files is as if the original never happened, but the record is honest and
nothing is rewritten.

Revert isn't always a clean, automatic step. If later commits changed the same lines the
old commit touched, Git can't cleanly undo it and you'll get a merge conflict to resolve
by hand - the same kind of conflict you'd hit in a merge. Fix the files, `git add` them,
then finish with `git revert --continue`.

## Why it's safe on shared branches

Because revert **adds** a commit rather than removing one, it never rewrites history.
Everyone who already pulled the original commit can simply pull the revert on top - no
forced pushes, no conflicts with their copies. This makes `git revert` the correct tool
for undoing anything on `main` or any branch other people share.

## revert vs reset

Both undo commits, but in opposite ways:

| | `git revert` | `git reset` |
|---|---|---|
| What it does | Adds a new commit that cancels the old one | Moves the branch pointer back, dropping commits |
| History | Preserved (nothing rewritten) | Rewritten (commits removed) |
| Safe on shared branches | **Yes** | No |
| Best for | Undoing pushed/shared commits | Undoing local, unpushed commits |

The short version: **reset for private history, revert for shared history.** If in doubt
on a branch other people use, revert. See
[git reset explained](/course/git-basics/undoing-things/git-reset-explained) for the reset
side.

## Common mistake

Expecting `git revert` to erase the original commit from `git log`. It won't - both the
original and the revert remain visible. That's the point: the change is undone while the
history stays truthful. If you truly need the commit gone from history and it was never
shared, reset is the tool instead.

## FAQ

### How do I undo a commit that I already pushed?

Use `git revert <commit-hash>`. It creates a new commit that reverses the changes, which
is safe to push because it doesn't rewrite the shared history.

### What's the difference between git revert and git reset?

`git revert` adds a new commit that cancels an old one and keeps history intact - safe for
shared branches. `git reset` moves the branch back and removes commits, rewriting history -
only safe for local, unpushed work.

### Can I revert a revert?

Yes. A revert is just a normal commit, so you can revert *it* to bring the original change
back. This is handy if you undid something and later decided you needed it after all.
