---
title: "Amend the last commit with git commit --amend"
slug: amending-the-last-commit
seo_title: "git commit --amend: fix your last Git commit"
seo_description: "Fix your last Git commit message or add a forgotten file with git commit --amend - and why you should never amend a commit you already pushed."
---

## How do I fix my last commit with git commit --amend?

You just committed, and something's off. A typo in the message. A file you forgot to
stage. You don't want a second commit that says "fix typo" - you want the last commit
correct in the first place.

That's the job of `git commit --amend`. It replaces your most recent commit with a new
one.

## Fix the last commit message

To change only the message of the commit you just made:

```bash
git commit --amend -m "The correct message"
```

This takes the changes from your last commit and re-commits them with the new message.
The old commit is gone, replaced by a corrected one.

## Add a forgotten file to the last commit

Say you committed, then realised a file should have been part of that commit. Stage the
file first, then amend:

```bash
git add forgotten-file.txt
git commit --amend
```

Because you didn't pass `-m`, Git opens your editor with the existing message so you can
keep it or edit it. Save and close, and the file is now folded into that commit. To keep
the message exactly as it was, add `--no-edit`:

```bash
git add forgotten-file.txt
git commit --amend --no-edit
```

`--no-edit` is the one you'll reach for most: staged fix, amend, done, message
untouched. It's the everyday move for "I forgot something in the commit I just made".

## What actually happens (and the warning)

**Amending does not edit a commit - it creates a brand-new one that replaces the old
one.** The new commit has a different hash. This is a form of rewriting history.

That's completely fine for a commit that lives only on your machine. It is a problem the
moment the commit has been **pushed or shared**: your amended commit no longer matches
the one your teammates (or the remote) already have, and a normal `git push` will be
rejected. Pushing the amended version forces everyone else into a messy sync.

**Rule of thumb: only amend commits you have not pushed yet.** If a bad commit is already
shared, use [git revert](/course/git-basics/undoing-things/reverting-a-commit) instead,
which is safe on shared branches.

## Common mistake

A frequent surprise: running `git commit --amend` when you have **other** changes staged
that you didn't mean to include. Amend folds in *everything* currently staged, not just
the file you had in mind. Before amending, run `git status` and check that only what you
intend is staged.

## FAQ

### How do I change my last commit message in Git?

Run `git commit --amend -m "New message"`. This rewrites the most recent commit with the
message you provide. Only do it if you haven't pushed the commit yet.

### How do I add a file I forgot to my last commit?

Stage the file with `git add`, then run `git commit --amend --no-edit`. The file joins
the last commit and the message stays the same.

### Can I amend a commit that I already pushed?

You can, but you shouldn't in most cases. Amending changes the commit's hash, so your
history no longer matches the remote and a plain push is rejected. On a shared branch,
prefer `git revert` to undo a pushed commit safely.
