---
title: "Deleting branches"
slug: deleting-branches
seo_title: "Delete a Git Branch: git branch -d vs -D"
seo_description: "Delete a Git branch safely with git branch -d, force-delete an unmerged branch with -D, and see why clearing out merged branches keeps your repo tidy."
---

You delete a Git branch once it has done its job - usually right after its feature
lands in `main`. Old branches pile up in your list and make it harder to see what's
actually in progress, so clearing them out is a normal part of the workflow, not an
afterthought.

## Delete a merged branch with -d

Once you've merged `new-feature` into `main` (and switched back to `main`), delete the
feature branch with `-d`:

```bash
git switch main
git branch -d new-feature
```

Remember, a branch is just a pointer to a commit. Deleting the branch only removes the
label - the commits themselves are already part of `main`, so nothing is lost. The
work lives on; you're just tidying up the name.

Cleaning up several at once is fine too: `git branch -d feature-a feature-b fix-typo`
deletes them all in one line, and because `-d` checks each branch individually, any
that aren't fully merged are skipped with a warning while the safe ones still go.

## You can't delete the branch you're on

Git won't let you delete the branch you're currently sitting on - that would leave
HEAD pointing at nothing. If you try, you'll see an error. Switch to another branch
first (usually `main`), then delete.

## The safety check behind -d

The lowercase `-d` is deliberately cautious. It only deletes a branch whose commits
are already merged into your current branch. If they aren't, Git refuses and warns
you:

```text
error: The branch 'new-feature' is not fully merged.
```

That message is a feature, not an obstacle. It's stopping you from throwing away work
that exists **only** on that branch. If you see it, the branch has commits that aren't
anywhere else yet.

## Force delete with -D

Sometimes you really do want to delete an unmerged branch - an experiment that didn't
work out, for example. The uppercase `-D` forces the deletion regardless of merge
state:

```bash
git branch -D throwaway-experiment
```

Use `-D` with care. Because those commits aren't merged anywhere, deleting the branch
can make them very hard to find again. A simple rule: reach for `-d` by default, and
only use `-D` when you're certain you want the work gone.

## Why delete branches at all?

- **A clean list.** `git branch` should show what you're actually working on, not
  months of finished features.
- **Clarity for everyone.** On a shared project, a short branch list tells your team
  what's live and what's history.
- **No cost to the history.** Merged commits stay in `main` forever. The branch name
  was only ever a temporary handle.

The normal rhythm is: branch, work, merge, delete. Then start the next piece of work
on a fresh branch.

## Common mistake

The usual scare is running `git branch -d` and seeing "not fully merged", then
reaching for `-D` to make the error go away. Don't do that reflexively. The warning
means the branch holds commits that exist nowhere else - force-deleting really can
lose them. Merge the branch first if you want the work, and only force-delete when you
genuinely intend to discard it.

## FAQ

### What's the difference between git branch -d and -D?

Lowercase `-d` deletes a branch only if its commits are already merged, protecting you
from losing work. Uppercase `-D` force-deletes regardless. Use `-d` normally and `-D`
only when you truly want to discard unmerged commits.

### Does deleting a branch delete its commits?

Not if they're merged. The commits are part of the branch you merged into, so deleting
the branch just removes the label. Deleting an unmerged branch with `-D`, however, can
make its commits very hard to recover.

### Why can't I delete the branch I'm on?

Deleting your current branch would leave Git with nowhere to point HEAD. Switch to
another branch first, such as `git switch main`, and then delete the one you no longer
need.
