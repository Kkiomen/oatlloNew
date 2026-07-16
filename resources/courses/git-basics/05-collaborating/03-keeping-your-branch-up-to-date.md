---
title: "Keeping your branch up to date"
slug: keeping-your-branch-up-to-date
seo_title: "Keep Your Git Feature Branch Up to Date With main"
seo_description: "main moved while you worked? Learn to update your feature branch by merging the latest main into it so your pull request stays current and mergeable."
---

## The problem: main moved while you worked

Keeping your Git feature branch up to date with `main` is a chore you'll repeat on every
change of any length. Picture it: you branched off `main` on Monday and worked all week.
Meanwhile teammates merged their own pull requests, so `main` on GitHub is now ahead of
where you started. Your branch is built on an old version of `main`.

This isn't an error, but it's worth fixing before you open (or finish) your pull request:

- Your PR may show conflicts if others touched the same files.
- You want to test your feature against the *current* code, not last week's.

## Update your local main first

Bring your local `main` in line with GitHub:

```bash
git switch main
git pull origin main
```

You met `git pull` in [Chapter 4](/course/git-basics/remotes-and-github/fetching-and-pulling) -
it fetches the latest commits and merges them into your current branch.

## Bring those changes into your feature branch

Now switch back to your feature branch and merge the updated `main` into it:

```bash
git switch add-login-page
git merge main
```

This is the same [merge](/course/git-basics/branching-and-merging/merging-branches) you
learned in Chapter 3, just pointed at `main`. Your branch now contains all the new work
from `main` *plus* your own commits.

Run this often and most days Git will just print `Already up to date.` and do nothing -
which is exactly why it's safe to run before every work session. The merge only has real
work to do on the days `main` actually moved, and those are the days you want to catch it.

If the same lines were changed in both places, you'll get a merge conflict. That's normal
- resolve it exactly as you learned in
[resolving merge conflicts](/course/git-basics/branching-and-merging/resolving-merge-conflicts),
then commit the result.

## A shortcut when your branch is pushed

If your feature branch already tracks a remote, you can pull `main` straight into it
without switching back and forth:

```bash
git switch add-login-page
git pull origin main
```

This fetches `main` from GitHub and merges it into your current branch in one step.

## A note on rebase

There's another way to do this: **rebase**, which replays your commits on top of the
latest `main` instead of merging. It produces a cleaner, straight-line history, but it
rewrites your commits, so it comes with rules. We cover it properly in Chapter 7 - for
now, merging `main` into your branch is perfectly correct and safe.

## A common mistake

**Waiting until the very end to update.** If you go two weeks without pulling `main`, you
face one enormous conflict at merge time. Update your branch every day or two while the
change set is small and conflicts are easy to resolve.

## FAQ

### Should I merge main into my branch, or my branch into main?

While you're still working, merge `main` *into* your branch to stay current. Merging your
branch *into* `main` is the final step, and on a team that usually happens through the
pull request, not a manual merge.

### Will updating my branch mess up my pull request?

No - it improves it. When you push the updated branch, the PR refreshes automatically and
now merges cleanly against the latest `main`.
