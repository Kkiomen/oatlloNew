---
title: "A sensible Git workflow"
slug: a-sensible-git-workflow
seo_title: "A sensible everyday Git workflow (solo and team)"
seo_description: "A practical everyday Git workflow for beginners, solo or on a team: main plus feature branches, small commits, pull before push, pull requests, merge vs rebase."
---

## A sensible everyday Git workflow

By now you've met a lot of Git commands. What ties them together is a workflow - the order
you actually reach for them each day. This capstone lesson stitches the pieces into one
loop you can follow whether you work alone or on a team. No new commands here; just the ones
you already know, arranged the way you'll use them.

The core idea is simple: **`main` always stays working, and every piece of work happens on
its own short-lived branch.**

## The everyday loop

Here's the cycle you'll repeat for almost every task.

**1. Start from an up-to-date main.** Before anything else, sync with the remote so you
branch off the latest code:

```bash
git switch main
git pull
```

Pulling first ([fetching and pulling](/course/git-basics/remotes-and-github/fetching-and-pulling))
avoids branching off a stale copy and heading straight into conflicts.

**2. Create a feature branch.** One branch per task, named for what it does:

```bash
git switch -c fix-login-bug
```

This is [the feature branch workflow](/course/git-basics/collaborating/the-feature-branch-workflow):
`main` stays clean while you experiment freely on your own branch.

**3. Work in small commits.** Make a focused change, stage it, and commit it with a clear
message. Do this often rather than saving up one giant commit:

```bash
git add .
git status
git commit -m "Fix null check on login form"
```

Check [git status](/course/git-basics/everyday-git/checking-status) before you commit so
you know exactly what you're recording, and follow the
[good commit messages](/course/git-basics/everyday-git/good-commit-messages) habits. Small
commits are easier to review, easier to revert, and easier to understand later.

**4. Pull before you push.** Others may have moved `main` while you worked. Keep your
branch current so your eventual merge is smooth:

```bash
git switch main
git pull
git switch fix-login-bug
git merge main
```

This is [keeping your branch up to date](/course/git-basics/collaborating/keeping-your-branch-up-to-date).
If a [push gets rejected](/course/git-basics/collaborating/handling-push-rejections), it's
almost always because you skipped this step - pull, then push.

**5. Push and open a pull request.** Send your branch to the remote and open a
[pull request](/course/git-basics/collaborating/pull-requests) for review:

```bash
git push -u origin fix-login-bug
```

**6. Review, merge, delete.** A teammate (or you, on a solo project) reviews the changes.
Once approved, merge it into `main` and [delete the branch](/course/git-basics/branching-and-merging/deleting-branches) -
it has served its purpose:

```bash
git switch main
git pull
git branch -d fix-login-bug
```

That lowercase `-d` is a small safety net worth appreciating: it refuses to delete a branch
whose commits aren't merged anywhere, so you can't accidentally throw away unmerged work.
The uppercase `-D` forces it through. When `-d` complains, treat it as a question ("did this
really merge?"), not an obstacle - pull main and check before reaching for `-D`.

Then start the loop again for the next task.

## Merge or rebase?

Both combine work; the difference is what your history looks like afterward (covered in
[rebase vs merge](/course/git-basics/rewriting-history/rebase-vs-merge)). A simple, safe
rule for beginners:

- **Merge to bring finished branches into `main`.** It's non-destructive and preserves
  exactly what happened.
- **Rebase only to tidy your own branch before sharing it** - for example, cleaning up
  messy commits with [interactive rebase](/course/git-basics/rewriting-history/interactive-rebase)
  before opening the pull request.
- **Never rebase or rewrite commits others have already pulled**
  ([when not to rewrite history](/course/git-basics/rewriting-history/when-not-to-rewrite-history)).
  This is the one rule that keeps you out of the worst trouble.

When in doubt, merge. You can go a long way with merge alone.

## Solo vs team - what changes

The workflow is the same; a few habits shift:

- **Solo:** you can commit straight to `main` for tiny throwaway projects, but the branch
  plus pull request habit is still worth keeping - it gives you a place to see your own
  changes before they land, and it's exactly how teams work, so you stay in practice.
- **Team:** never commit directly to `main`; always go through a branch and a reviewed pull
  request. Pull often. Communicate before any force-push. Assume other people are building
  on the history you share.

## When things go wrong

They will, and that's fine - this whole chapter and
[Chapter 6](/course/git-basics/undoing-things/git-reset-explained) exist for exactly that:

- Committed on the wrong branch? [Move the commit.](/course/git-basics/real-world-and-troubleshooting/committed-to-the-wrong-branch)
- Need to undo the last commit? [Three ways here.](/course/git-basics/real-world-and-troubleshooting/undo-last-commit)
- Not ready to commit but need a clean branch? [Stash it.](/course/git-basics/undoing-things/stashing-work)
- Think you lost a commit? [The reflog usually has it.](/course/git-basics/undoing-things/recovering-with-reflog)

## The whole thing in one place

```bash
git switch main && git pull          # start fresh
git switch -c my-task                # branch per task
# ... edit files ...
git add . && git commit -m "..."     # small, clear commits
git switch main && git pull          # sync
git switch my-task && git merge main # keep branch current
git push -u origin my-task           # push and open a PR
# ... review and approve ...
git switch main && git pull          # after merge
git branch -d my-task                # clean up
```

That's it. Branch, commit small, pull before push, review, merge, delete. Master this loop
and you'll use Git confidently every day - and when something breaks, you now know where to
look.

## FAQ

### What's a good Git workflow for beginners?

Keep `main` always working and do each task on its own feature branch. Commit small and
often, pull before you push, open a pull request for review, then merge and delete the
branch. Repeat for every task.

### Should I use merge or rebase?

Merge to bring finished branches into `main` - it's safe and preserves history. Use rebase
only to tidy your own not-yet-shared branch. Never rebase commits other people have already
pulled.

### Do I need branches and pull requests when working alone?

Not strictly, but it's a good habit. Branches give you a safe place to work and a chance to
review your own changes before they hit `main`, and it keeps you fluent in the same workflow
teams use.

### Why pull before pushing?

Because others may have updated `main` while you worked. Pulling first merges their changes
into yours locally, so your push goes through cleanly instead of being rejected.
