---
title: "Committed to the wrong branch"
slug: committed-to-the-wrong-branch
seo_title: "Committed to the wrong branch? How to move the commit"
seo_description: "Committed to main instead of a feature branch? Move the commit onto a new branch with git switch -c, then reset main back - safe, step-by-step fix."
---

## The problem: committed to the wrong branch

You meant to be on a feature branch, but you were on `main` the entire time and only
noticed after the commit landed. Now your work sits on `main` when it belongs on its own
branch. Good news first: nothing is lost, your commits are safe, and moving them takes
three short commands.

This assumes you **haven't pushed** yet. If you have, jump to the warning at the end.

## The fix: carry the commits to a new branch, then rewind main

The trick is that your commits are already right here. Create a branch at your current
position, then move `main` back to where it should be.

Step 1 - create the feature branch at your current commit and switch to it:

```bash
git switch -c feature
```

Your commits are now on `feature`. But they're still on `main` too, because both branches
point at the same commit. Step 2 - go back to `main`:

```bash
git switch main
```

Step 3 - reset `main` back to match the remote, removing the commits from it:

```bash
git reset --hard origin/main
```

Now `main` matches what's on the server, and your commits live only on `feature` where
they belong. Switch back with `git switch feature` and carry on.

One quiet gotcha: `reset --hard origin/main` only rewinds to whatever your local
`origin/main` last saw. If you haven't fetched in a while, that reference can be stale and
you'd reset `main` to an old point. Run `git fetch` first so `origin/main` reflects the
real remote before you reset against it.

## DESTRUCTIVE warning

`git reset --hard origin/main` throws away anything on `main` that isn't on `origin/main`.
That's exactly what you want here, because those commits were safely copied to `feature`
in step 1. **Do step 1 first.** If you reset `main` before creating `feature`, you lose
the commits (though [the reflog](/course/git-basics/undoing-things/recovering-with-reflog)
can often recover them).

## Just one commit? Cherry-pick instead

If only a single commit landed on the wrong branch and the rest of `main` is fine, you can
[cherry-pick](/course/git-basics/rewriting-history/cherry-picking) it onto the right branch
instead. Note its hash from `git log`, switch to the correct branch, then:

```bash
git switch feature
git cherry-pick a1b2c3d
```

Then remove it from `main` with `git reset --hard HEAD~1` while on `main`.

## If you already pushed to main

Then `reset --hard` on `main` would rewrite shared history, which is unsafe (see
[when not to rewrite history](/course/git-basics/rewriting-history/when-not-to-rewrite-history)).
Create your `feature` branch and push it, then undo the change on `main` with
[git revert](/course/git-basics/undoing-things/reverting-a-commit) rather than resetting.

## FAQ

### I committed to main instead of my feature branch. How do I fix it?

Run `git switch -c feature` to copy the commits onto a new branch, then `git switch main`
and `git reset --hard origin/main` to rewind main. Do the branch step first so nothing is
lost.

### What if I only committed once to the wrong branch?

Use `git cherry-pick <hash>` to copy that one commit onto the correct branch, then remove
it from the wrong branch with `git reset --hard HEAD~1`.

### What if I already pushed the commit to main?

Don't reset a pushed branch. Create and push your feature branch, then undo the change on
main with `git revert`, which is safe because it doesn't rewrite shared history.
