---
title: "gitignore not working"
slug: gitignore-not-working
seo_title: "gitignore not working? Untrack the file to fix it"
seo_description: "Your .gitignore is not working because the file is already tracked by Git. Fix it with git rm --cached, then commit. Full explanation and examples."
---

## The problem: gitignore not working

You added `.env` or `node_modules/` to `.gitignore`, but Git keeps showing the file in
`git status` and committing it anyway. The spelling checks out. The pattern looks right.
So why is your gitignore not working?

Almost every time it comes down to one rule: **`.gitignore` only applies to files Git
isn't already tracking.** Commit a file once, and Git keeps following it forever after -
adding it to `.gitignore` later changes nothing on its own.

## The fix: stop tracking the file, then commit

Tell Git to forget the file (remove it from tracking) while keeping it on your disk, then
commit that change:

```bash
git rm --cached .env
git commit -m "Stop tracking .env"
```

For a whole folder, add `-r` (recursive):

```bash
git rm -r --cached node_modules
git commit -m "Stop tracking node_modules"
```

The `--cached` flag is the important part: it removes the file from Git's index but
**leaves the actual file untouched on your disk.** After this commit, your `.gitignore`
entry finally takes effect and the file stops showing up.

## DESTRUCTIVE flag warning

Do not run `git rm .env` without `--cached`. Without that flag, `git rm` deletes the file
from your working directory too - so `git rm .env` would erase your real `.env` file, not
just untrack it. Always keep `--cached` when your goal is only to stop tracking.

## Why this happens

Back in [the gitignore lesson](/course/git-basics/everyday-git/gitignore) you learned
that `.gitignore` lists paths Git should never stage. But there's a catch it can't get
around: ignore rules are only consulted for **untracked** files. The moment a file has
been committed even once, Git considers it "tracked" and assumes you want to keep seeing
its changes. Adding it to `.gitignore` afterwards changes nothing on its own.

`git rm --cached` breaks that link. It records a change that says "Git, stop following
this file from here on." Once that's committed, the file becomes untracked, and now
`.gitignore` matches it like you expected all along.

The lesson: **add things to `.gitignore` before you ever commit them.** If they slipped
through, untrack them once and you're set.

One thing that trips people up even after the pattern is correct: a `!file` negation can't
rescue a file whose parent directory is already ignored. Ignore `logs/` and no `!logs/keep.txt`
rule will bring `keep.txt` back, because Git never descends into an ignored directory in the
first place. Ignore `logs/*` instead, and the negation works.

## FAQ

### Why is my .gitignore being ignored?

Because the file was already committed before you ignored it. `.gitignore` only affects
untracked files. Run `git rm --cached <file>` and commit, then the ignore rule works.

### Does git rm --cached delete my file?

No. The `--cached` flag removes the file only from Git's tracking, not from your disk.
Your local copy stays exactly where it is. Only `git rm` without `--cached` deletes it.

### How do I stop tracking a whole folder like node_modules?

Run `git rm -r --cached node_modules`, make sure `node_modules/` is in your `.gitignore`,
then commit. The `-r` makes the removal recursive so it covers everything inside.

### Do I need to do this on every machine?

No. Once you commit the untracking change, everyone who pulls it gets the same result -
the file stops being tracked for the whole repository.
