---
title: "Rebase vs merge"
slug: rebase-vs-merge
seo_title: "Git Rebase vs Merge: The Difference Explained"
seo_description: "Git rebase vs merge, explained with diagrams: merge keeps history and adds a merge commit, rebase replays commits for a linear history. When to use each."
---

Both **git merge** and **git rebase** answer the same question: I have commits on my
branch, and `main` has moved on, so how do I bring them together? The git rebase vs merge
choice comes down to one thing, the shape of the history you're left with. Merge records
that two lines of work met. Rebase pretends they never diverged.

## The setup

Say you branched off `main` to build a feature and made two commits, `D` and `E`.
While you worked, a teammate pushed two commits of their own, `X` and `Y`, to `main`.
The two lines have now diverged:

```text
        D - E            <- feature (your two commits)
       /
A - B - C - X - Y        <- main (moved on without you)
```

Both merge and rebase reunite these lines. They just leave you with a different-shaped
history.

## Merge: keep both lines, add a merge commit

`git merge` ties the two lines together with a new commit that has **two parents** -
one from each side. Your original commits stay exactly where they were.

```bash
git switch feature
git merge main
```

After the merge, history looks like this:

```text
        D - E
       /       \
A - B - C - X - Y - M     <- feature (M is the merge commit)
```

`M` is the merge commit. Nothing was rewritten - `D` and `E` keep their original IDs.
The history is truthful: it shows that work genuinely happened in parallel and was
joined at point `M`.

## Rebase: replay your commits on top, linear history

`git rebase` takes your commits (`D`, `E`), sets them aside, moves your branch to the
tip of `main`, and then **replays** your commits one by one on top.

```bash
git switch feature
git rebase main
```

After the rebase, history is a straight line:

```text
A - B - C - X - Y - D' - E'     <- feature
```

Notice `D'` and `E'` have a prime mark. They are **new commits** with new IDs - Git
rebuilt them on the new base. The content is the same, but they are technically not
the same commits anymore. That detail matters a lot (see the last lesson of this
chapter). See also [git reset explained](/course/git-basics/undoing-things/git-reset-explained)
for the related idea that history is just movable pointers.

One thing the diagram hides: rebase replays commits one at a time, so a
[conflict gets resolved](/course/git-basics/branching-and-merging/resolving-merge-conflicts)
per commit, not once. Rebase ten commits across a big change on `main` and you
can end up fixing the same clash ten times over. A merge stops once and you settle it in
a single merge commit. That is the practical tax of a linear history.

## Before and after, side by side

```text
MERGE                          REBASE
        D - E                  A - B - C - X - Y - D' - E'
       /       \
A - B - C - X - Y - M
```

Merge preserves the fork and records the join. Rebase erases the fork and pretends
your work was always built on the latest `main`.

## Comparison table

```text
                     Merge                      Rebase
History shape        branching, with a          straight line
                     merge commit
Commit IDs           unchanged                  rewritten (new IDs)
Extra commit         yes, the merge commit      no
Records reality      yes (parallel work         no (looks sequential)
                     is visible)
Conflicts            resolved once, in          may resolve per commit
                     the merge commit
Safe on shared       yes                        no - never rebase
branches                                        commits others pulled
```

## When to choose each

Choose **merge** when:

- You're pulling `main` into a shared, long-lived branch that others also use.
- You want an honest record of when work branched and joined.
- You're merging a finished feature branch into `main` (a pull request "merge").

Choose **rebase** when:

- You want to
  [update **your own** feature branch](/course/git-basics/collaborating/keeping-your-branch-up-to-date)
  on top of the latest `main` before opening a pull request, so it applies cleanly.
- You want a clean, linear, easy-to-read history without noisy merge commits.
- The commits you're rebasing are still private - only on your machine, not pulled by
  anyone else.

A common team workflow combines both: **rebase your feature branch locally** to keep
it tidy and current, then **merge it into `main`** through a pull request.

Worth knowing early: plain `git pull` is a fetch plus a merge, so it quietly sprinkles
merge commits into your branch every time `main` moves. If you prefer the linear look,
`git pull --rebase` fetches and rebases instead, and `git config --global pull.rebase true`
makes that the default everywhere.

## Common mistake

Rebasing a branch that other people have already pulled. Because rebase creates new
commit IDs, everyone else still has the old ones, and Git can no longer line the two
histories up. The result is duplicated commits and painful conflicts for the whole
team. The rule is simple and it has its own lesson at the end of this chapter: rewrite
only history that is still yours alone.

## FAQ

### Does rebase delete my commits?

No. It replays them as new commits with new IDs on a new base. The old versions still
exist for a while and are reachable through the reflog if something goes wrong, which
you saw in [recovering with reflog](/course/git-basics/undoing-things/recovering-with-reflog).

### Which one should a beginner use?

Start with merge - it's safe and never rewrites anything. Reach for rebase once you're
comfortable, and only on your own unpushed (or unshared) branches.

### Why does rebase give my commits new IDs?

A commit's ID is a hash of its content plus its parent. Rebase gives your commits a
new parent (the tip of `main`), so the hash changes and you get brand-new commits.

### Is a merge commit a bad thing?

Not at all. It's an honest record that two lines of work were joined. Some teams love
a linear history and rebase to avoid merge commits; others value the record. Both are
valid.
