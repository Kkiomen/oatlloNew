---
title: "When not to rewrite history"
slug: when-not-to-rewrite-history
seo_title: "When Not to Rewrite Git History: The Golden Rule"
seo_description: "The golden rule of Git: never rewrite commits others have pulled. Force-push dangers explained, plus why --force-with-lease is safer than --force."
---

You've spent this chapter learning to rewrite git history - rebase, interactive rebase,
amend, cherry-pick. All powerful, all useful. Knowing when **not** to rewrite git history
matters just as much, and it fits in one rule. If you remember nothing else from this
chapter, keep this:

> **Never rewrite commits that other people have already pulled.**

This lesson explains why, and how to stay safe.

## Why rewriting shared commits breaks things

Every time you rewrite history, Git creates **new commits with new IDs**. You saw this
with rebase in [rebase vs merge](/course/git-basics/rewriting-history/rebase-vs-merge):
`D` and `E` became `D'` and `E'`.

That's harmless while the commits live only on your machine. But once you've pushed and
a teammate has pulled, **they have the old commit IDs**. If you now rewrite those
commits and force them onto the remote, two histories exist:

```text
You (after rewrite):     A - B - C'    (new IDs)
Your teammate:           A - B - C     (old IDs they already have)
```

When your teammate pulls, Git can't tell that `C` and `C'` are "the same" work. It sees
two divergent histories and tries to reconcile them, producing duplicated commits and
ugly conflicts - for everyone, not just you. The shared branch becomes a mess.

## The safe line: private vs public

The rule in practice comes down to one question: **has anyone else seen these commits?**

```text
Safe to rewrite                  Not safe to rewrite
-------------------------------  -------------------------------
Commits only on your machine     Commits pushed to a shared branch
Your own feature branch that     main / master / develop
nobody else has pulled           A branch a teammate is using
Local work before first push     Anything on a release branch
```

So cleaning up your own feature branch with `git rebase -i` before opening a pull
request is perfectly safe - it's still yours. Rebasing `main` after the team has been
building on it is not.

## Force-push: the danger sign

Normally `git push` refuses to overwrite the remote if your history doesn't build
cleanly on top of it. That refusal is a **safety feature** - it's Git telling you your
push would erase commits.

After rewriting history, your local branch no longer matches the remote, so a plain
push is rejected. The only way through is a **force push**:

```bash
git push --force
```

`--force` says "throw away whatever is on the remote and replace it with mine". On a
shared branch, that can silently delete a teammate's commits that were pushed after you
last pulled. There is no undo button on the remote for the people who lose work.

## Use --force-with-lease instead

If you genuinely must force-push your **own** branch (for example, after squashing your
feature branch before a PR), use the safer version:

```bash
git push --force-with-lease
```

`--force-with-lease` only overwrites the remote if it still looks the way you last saw
it. If someone else pushed in the meantime, the push is **rejected** instead of
destroying their work. Think of it as "force, but not if I'd be clobbering something new
I don't know about".

```text
--force               overwrite the remote, no questions asked (dangerous)
--force-with-lease    overwrite only if nobody else pushed since I last looked (safer)
```

Make `--force-with-lease` your default whenever a force push is unavoidable.

One catch that surprises people: the lease compares against your **remote-tracking**
ref, not the live remote. So if you (or an editor plugin, or a `git fetch` you forgot you
ran) update that ref right before pushing, the lease now "sees" the newest state and
waves the push through, wiping the very commit it was meant to guard. Push soon after you
rebase, and don't fetch in between if you want the protection to mean anything.

## Common mistake

Rebasing or amending a branch, seeing "push rejected", and reaching for `git push
--force` to "make it work". If that branch is shared, you may have just erased a
colleague's commits. Stop and ask: is this branch only mine? If yes, prefer
`--force-with-lease`. If it's shared, don't rewrite it at all - use a
[revert](/course/git-basics/undoing-things/reverting-a-commit) instead, which undoes a
change by adding a new commit rather than rewriting old ones.

## The safe alternative for shared branches

When you need to undo something that's already public, don't rewrite - **revert**.
`git revert` creates a new commit that cancels out an old one, leaving history intact
and everyone's clones happy. It's the polite, non-destructive way to fix a shared
mistake, and you already met it in
[reverting a commit](/course/git-basics/undoing-things/reverting-a-commit).

## FAQ

### Is it ever OK to force-push?

Yes - onto your own branch that nobody else has pulled, typically after cleaning it up
before a pull request. Use `--force-with-lease` even then, so you can't accidentally
overwrite work you didn't know about.

### What's the difference between --force and --force-with-lease?

`--force` overwrites the remote unconditionally. `--force-with-lease` overwrites only if
the remote still matches what you last fetched, so it refuses to destroy commits someone
else pushed in the meantime.

### I already rebased a shared branch - what now?

Tell your team before anyone pulls again, and coordinate. The cleanest recovery is
usually to agree on one correct version and have everyone reset to it. In future, reach
for revert on shared branches instead of rewriting.

### Why does Git reject my push after a rebase?

Because rewriting gave your commits new IDs, so your branch no longer builds on top of
the remote's history. The rejection is a safeguard. Only force-push (ideally
`--force-with-lease`) if the branch is truly yours alone.
