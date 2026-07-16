---
title: "Pushing changes"
slug: pushing-changes
seo_title: "git push -u origin main: Push Commits to GitHub"
seo_description: "What git push does, how git push -u origin main sets the upstream branch, and why every push after that is just a plain git push on its own."
---

Your commits live on your machine until you **push** them. Pushing uploads the commits
from your local branch to the matching branch on the remote, so GitHub - and anyone else
- can see them.

## What pushing actually does

A push sends any commits you have that the remote doesn't, and moves the remote's branch
forward to match yours. It does not push uncommitted changes or staged files - only
finished commits. So the usual rhythm is: stage, commit, then push.

```bash
git add .
git commit -m "Add login form"
git push
```

If your branch is already connected to the remote, plain `git push` is all you need.
Setting up that connection is the one extra step the first time.

## The first push: git push -u origin main

The very first time you push a new branch, Git doesn't yet know which remote branch it
belongs to. You tell it once:

```bash
git push -u origin main
```

Read it as: "push my `main` branch to the remote called `origin`, and remember this
pairing". Breaking it down:

- **`origin`** is the remote (from the previous lessons).
- **`main`** is the branch you're pushing.
- **`-u`** (short for `--set-upstream`) records that your local `main` **tracks**
  `origin/main`. This link is called the **upstream** branch.

After this, Git knows where `main` goes. From now on you can just run:

```bash
git push
```

and Git pushes `main` to `origin/main` without you spelling it out. You only need the
`-u` form once per new branch.

One thing that surprises people: a push is per-branch. `git push` sends the branch you're
currently on, not everything you've committed everywhere. Switch to a feature branch you
made in the branching chapter and it sits unpushed until you check it out and push it too.
That's usually what you want, but it explains why a branch you "finished" is missing from
GitHub - you pushed `main`, not it.

## Checking before you push

`git status` tells you whether you have commits waiting to go up:

```text
Your branch is ahead of 'origin/main' by 2 commits.
  (use "git push" to publish your local commits)
```

"Ahead by 2 commits" means you have two local commits the remote doesn't. After a
successful push, status goes back to "up to date".

## Common mistake

Beginners often expect their files to appear on GitHub the moment they save them, or
right after `git add`. They won't. GitHub only ever shows **committed** work that has
been **pushed**. If you don't see a change online, check two things: did you commit it
(`git status`), and did you push (`git status` again, looking for "ahead of origin").

## FAQ

### Do I have to push after every commit?

No. Commit as often as you like locally; push when you want to back up or share. Many
people push once they've finished a small unit of work, not after every single commit.

### What does -u do exactly?

It sets the upstream branch - the remote branch your local branch is paired with. Once
set, `git push` and `git pull` know where to go without arguments. You need it only on
the first push of a new branch.

### My push was rejected. What now?

That usually means the remote has commits you don't have locally, so Git stops to avoid
losing them. You pull first, then push. The next lesson,
[fetching and pulling](/course/git-basics/remotes-and-github/fetching-and-pulling),
covers exactly that.
