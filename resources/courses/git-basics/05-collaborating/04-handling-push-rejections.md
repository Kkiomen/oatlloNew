---
title: "Handling push rejections (non-fast-forward)"
slug: handling-push-rejections
seo_title: "Fix Git Push Rejected: non-fast-forward Error"
seo_description: "Git push rejected with '! [rejected] ... (non-fast-forward)'? Someone pushed first. Learn to git pull then push, and why you should never force-push shared branches."
---

## The error you just hit

A **Git push rejected** with `non-fast-forward` is one of the first errors every
collaborator meets. You run `git push` and Git refuses:

```text
! [rejected]        main -> main (non-fast-forward)
error: failed to push some refs to 'github.com:you/project.git'
hint: Updates were rejected because the remote contains work that you do
hint: not have locally. This is usually caused by another repository pushing
hint: to the same ref. You may want to first integrate the remote changes
hint: (e.g., 'git pull ...') before pushing again.
```

Nothing is broken. This is Git protecting you.

## What it means

**Someone pushed to this branch before you did.** The remote has commits you don't have
locally. If Git accepted your push, it would have to throw their commits away to make room
for yours - and Git will never silently discard someone's work.

"Non-fast-forward" is the technical name: your push can't just move the branch pointer
forward in a straight line, because the histories have split. Your local branch and the
remote branch both moved on from the same starting point.

## The fix: pull, then push

Bring the remote commits into your branch, then push the combined result:

```bash
git pull origin main
git push origin main
```

The `git pull` fetches the remote commits and merges them with yours (you saw this in
[Chapter 4](/course/git-basics/remotes-and-github/fetching-and-pulling)). Now your local
branch contains *both* sets of work, so the push fast-forwards cleanly.

If the two of you edited the same lines, `git pull` will report a merge conflict. That's
expected - resolve it the same way you did in
[resolving merge conflicts](/course/git-basics/branching-and-merging/resolving-merge-conflicts),
commit, and push again.

Want to see what you're about to merge before you merge it? Run
[git fetch](/course/git-basics/remotes-and-github/fetching-and-pulling) first and look at
`git log origin/main`. That downloads the teammate's commits without touching your branch,
so you know exactly what landed ahead of you before the merge happens.

## Never blindly force-push a shared branch

You may have seen `git push --force` suggested online as a way to "make the error go
away." It does make the error go away - by **overwriting the remote with your version and
deleting the commits your teammate just pushed.** On a shared branch like `main`, that's
destroying someone's work.

The rule is simple:

- **Shared branch** (`main`, a branch someone else is using): never force-push. Pull and
  merge instead, like above.
- Force-pushing is only reasonable on *your own* feature branch that nobody else has
  pulled - and even then there are safer options. Chapter 7 covers when and how to do it
  properly.

If pulling shows a conflict, resolving it is the correct amount of work. Reaching for
`--force` to skip that step is how teams lose commits.

## A common mistake

**Force-pushing to escape the conflict.** The rejection feels like a wall, and `--force`
looks like the door. It isn't - it's a bulldozer. Always `git pull` first. The extra
minute of merging is the price of not deleting a teammate's work.

## FAQ

### Why does Git reject my push instead of just merging?

Because a push is meant to be a simple fast-forward - moving the branch pointer forward.
When the remote has commits you lack, that's no longer simple, and Git wants *you* to
decide how to combine the histories with a pull, rather than guessing.

### I pulled and now there's a merge commit I didn't want. Is that bad?

No, it's normal when two branches move in parallel. If your team prefers a straight-line
history, that's what rebase is for - covered in Chapter 7. For now, the merge commit is
harmless and honest about what happened.

### Is force-push ever okay?

Yes, on a private branch only you use, when you deliberately want to rewrite its history.
Never on a branch other people have pulled. When in doubt, don't force-push - pull first.
