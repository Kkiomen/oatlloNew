---
title: "Unstage a file in Git"
slug: unstaging-files
seo_title: "Unstage files in Git with git restore --staged"
seo_description: "Unstage a file in Git with git restore --staged (or the older git reset HEAD) without losing a single edit. Here is how, and the trap to avoid."
---

## How do I unstage a file in Git?

You ran `git add` on something, then realised it doesn't belong in this commit yet. Maybe
`git add .` swept up one file that should wait. You want it out of the staging area but
your edits kept. To unstage a file in Git is exactly that: it moves the file back to
"modified but not staged", and your changes stay untouched.

Remember the three areas from
[the three areas](/course/git-basics/getting-started/the-three-areas): working directory,
staging area, repository. Unstaging just moves a file from the staging area back to the
working directory. Nothing is deleted.

## Unstage a file with git restore --staged

The modern command is `git restore --staged`:

```bash
git restore --staged config.php
```

This removes `config.php` from the staging area. Your edits to the file are still there -
run `git status` and you'll see it listed under "Changes not staged for commit" again.

To unstage everything at once:

```bash
git restore --staged .
```

One case surprises people: unstage a brand-new file you just added for the first time,
and it goes back to being **untracked**, not modified - the file and its contents are
untouched, Git simply forgets you'd added it. Nothing is lost either way.

Git itself hints at this. After you stage a file, `git status` prints:

```text
Changes to be committed:
  (use "git restore --staged <file>..." to unstage)
        modified:   config.php
```

## The older command: git reset HEAD

Before `git restore` existed, people used `git reset` to unstage:

```bash
git reset HEAD config.php
```

Or, since `HEAD` is the default, simply:

```bash
git reset config.php
```

You'll still see this in older tutorials and answers online, and it does the same job for
unstaging. It's safe - this plain form of `git reset` only touches the staging area, not
your files. **Modern Git recommends `git restore --staged`** because its name says what it
does, so prefer that one going forward.

## Common mistake

Don't confuse **unstaging** with **discarding**. `git restore --staged file` keeps your
changes and only unstages them. `git restore file` (without `--staged`) throws your
changes away for good. It's an easy slip because the two commands look almost identical -
the `--staged` flag is the whole difference. Discarding is covered next in
[discarding changes](/course/git-basics/undoing-things/discarding-changes).

## FAQ

### How do I unstage a file in Git?

Run `git restore --staged <file>`. It removes the file from the staging area while keeping
all your edits. To unstage everything, use `git restore --staged .`.

### Does unstaging a file delete my changes?

No. Unstaging only moves the file out of the staging area. Your edits remain in the
working directory - you can re-stage or keep editing.

### What's the difference between git restore --staged and git reset?

For unstaging they do the same thing. `git reset HEAD <file>` is the older way; `git
restore --staged <file>` is the modern, clearer command. Prefer `git restore --staged`.
