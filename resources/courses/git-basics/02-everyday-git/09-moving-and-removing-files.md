---
title: "Moving and removing files"
slug: moving-and-removing-files
seo_title: "git rm and git mv: Remove and Move Files in Git"
seo_description: "Remove and move files in Git: git rm to delete, git rm --cached to untrack while keeping the file on disk, and git mv to rename or relocate."
---

## Delete and rename files with git rm and git mv

Delete or rename a file with your file explorer and Git notices - it'll show up as a
change next time you check status. Doing it *through* Git with `git rm` and `git mv`
just saves a step: the change lands staged and ready to commit.

## Removing a file with git rm

To delete a tracked file **and** stage that deletion at the same time:

```bash
git rm old-notes.txt
```

This removes the file from your disk and stages the removal. Run
[git status](/course/git-basics/everyday-git/checking-status) and you'll see it under
"Changes to be committed" as `deleted`. Commit as usual and the file is gone from the
project going forward.

There's a built-in guard here worth knowing. If the file has edits you haven't committed,
`git rm` refuses and warns you, rather than throwing away work you might still want. It's
one of the few places Git stops and asks before destroying something.

## Untracking a file but keeping it: git rm --cached

Here's the more interesting case. Sometimes you want Git to **stop tracking** a file, but
you don't want to delete it from your disk. The classic example: you accidentally
committed a `.env` file full of secrets, or a `debug.log`, and now you want Git to forget
it while the file stays on your machine.

```bash
git rm --cached .env
```

The `--cached` flag means "remove it from Git only". The file stays right where it is;
Git just stops tracking it. This is exactly the fix for the problem from the
[.gitignore lesson](/course/git-basics/everyday-git/gitignore) - a file already tracked
before you ignored it. Untrack it with `--cached`, and the ignore rule finally applies:

```bash
git rm --cached debug.log
git commit -m "Stop tracking debug.log"
```

From now on, with `debug.log` in your `.gitignore`, Git leaves it alone.

## Renaming and moving with git mv

To rename or move a tracked file and stage the change in one step:

```bash
git mv notes.txt docs/notes.txt
```

This moves `notes.txt` into the `docs` folder (creating the move) and stages it. A rename
in place works the same way:

```bash
git mv readme.txt README.md
```

Git is smart about renames - even the diff will often show it as a rename rather than a
delete plus an add.

## Common mistake

Reaching for `git rm` when you only want to **untrack** a file. Plain `git rm` deletes it
from your disk too. If your goal is "keep the file, just stop committing it", you need the
`--cached` flag. Forgetting it means losing the file's contents - so pause when a file
matters and you're removing it from Git.

## FAQ

### What's the difference between git rm and git rm --cached?

`git rm` deletes the file from disk and stops tracking it. `git rm --cached` only stops
tracking it - the file stays on your disk untouched.

### Do I have to use git mv to rename a file?

No. You can rename with your editor or OS, and Git will detect it as a delete plus an add
that it usually recognizes as a rename. `git mv` just does it in one clean, staged step.

### I removed a file with git rm - can I get it back?

If you haven't committed yet, the removal is only staged and recoverable. Undoing changes
is a whole chapter later; for now, commit deletions carefully.
