---
title: "Recover lost commits with git reflog"
slug: recovering-with-reflog
seo_title: "git reflog: recover lost commits in Git"
seo_description: "Use git reflog to find and recover a commit that seems lost after a bad git reset, then restore it with git reset or a new branch. Git's safety net."
---

## How do I recover a lost commit with git reflog?

You ran `git reset --hard` on the wrong commit and the commits you had are nowhere in
`git log`. It feels like the work is gone. Usually it isn't - Git rarely deletes commits
outright. It just stopped pointing at them. The **reflog** is how you find them again.
This is Git's safety net.

## What the reflog records

Every time your branch tip moves - a commit, a reset, a checkout, a merge, a rebase - Git
writes it down in the reflog. So even after a commit disappears from `git log`, the reflog
still remembers where `HEAD` was before you moved it.

```bash
git reflog
```

```text
9c4d5e6 HEAD@{0}: reset: moving to HEAD~1
3f1a2b4 HEAD@{1}: commit: Add login validation
7b2c8d9 HEAD@{2}: commit: Add login form
```

Read this top to bottom as "what happened, most recent first". Here, `HEAD@{1}` (`3f1a2b4`)
is the commit that the `--hard` reset threw away. That hash is your way back.

Those `HEAD@{N}` labels are real references you can use directly, not just row numbers -
`git show HEAD@{1}` inspects that entry without hunting for the hash. Git even understands
time: `git reflog show HEAD@{yesterday}` if you know roughly when things went wrong.

## Recover the lost commit

Once you've spotted the commit you want in the reflog, you have two good options.

**Option 1 - move your branch back to it** with a reset:

```bash
git reset --hard 3f1a2b4
```

This puts your current branch right back on the lost commit. (Yes, `--hard` again - here
it's the tool that *rescues* you, because you're moving *to* the work you want. Make sure
your working tree is clean first, as `--hard` still discards uncommitted changes.)

**Option 2 - create a new branch at it**, which is safer because it doesn't move your
current branch:

```bash
git branch recovered 3f1a2b4
```

Now the lost commit is reachable again on a branch called `recovered`, and you can inspect
it before deciding what to do. When you're unsure, prefer this option.

## Why this is the safety net

The reflog is why a bad reset or rebase is usually recoverable, and why those commands are
less scary than they first seem. As long as the work was **committed**, the reflog
remembers it. This is also the key limit: the reflog only tracks commits. Changes you never
committed - like those wiped by
[git restore](/course/git-basics/undoing-things/discarding-changes) or a `--hard` reset
over uncommitted edits - are not in the reflog and can't be recovered this way.

## Common mistake

Assuming the reflog is shared or permanent. It is **local to your machine** - it isn't
pushed, so a teammate's reflog can't rescue your commit and yours can't rescue theirs. It
also expires: unreachable entries are eventually cleaned up (typically after weeks). So if
you realise you lost a commit, check `git reflog` sooner rather than later.

## FAQ

### How do I recover a commit after git reset --hard?

Run `git reflog`, find the hash of the lost commit, then either `git reset --hard <hash>`
to move your branch back to it, or `git branch <name> <hash>` to save it on a new branch.

### Can git reflog recover changes I never committed?

No. The reflog only tracks commits and branch movements. Uncommitted changes - for example
discarded with `git restore` - were never recorded, so the reflog can't bring them back.

### Is the reflog shared with my team?

No, the reflog is stored locally and never pushed. Each clone has its own reflog, so it can
only recover work that happened on your own machine.
