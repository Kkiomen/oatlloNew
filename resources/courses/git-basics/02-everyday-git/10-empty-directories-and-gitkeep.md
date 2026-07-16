---
title: "Empty directories and .gitkeep"
slug: empty-directories-and-gitkeep
seo_title: "Git Empty Directory: Commit One With .gitkeep"
seo_description: "Git ignores empty folders because it tracks files, not directories. Learn how to commit an empty directory with .gitkeep, plus .gitkeep vs .gitignore."
---

Create a folder, run `git status`, and Git says nothing changed. Commit, clone the
repository somewhere else, and the folder is gone. This is not a bug: **Git tracks files,
not directories**, so an empty folder has nothing for Git to record. This lesson shows how
to commit an empty directory with a `.gitkeep` placeholder, and clears up how it differs
from `.gitignore`.

## Why Git ignores empty folders

A Git commit is a snapshot of *files* and their paths. A directory only exists in Git as
part of a file's path (`logs/app.log` implies a `logs/` folder). Remove every file and the
path has nothing to hang on to, so the folder simply isn't in the snapshot.

That is a problem when your project *needs* a folder to exist - an app that writes to
`storage/logs/` expects the folder to be there on a fresh clone, even though it starts
empty.

## The fix: add a placeholder file

Give the folder one file so Git has something to track. The convention is a hidden file
named `.gitkeep`:

```bash
mkdir -p storage/logs
touch storage/logs/.gitkeep
git add storage/logs/.gitkeep
git commit -m "Keep the storage/logs directory"
```

Now the folder travels with the repository. Anyone who clones it gets `storage/logs/` with
the `.gitkeep` file inside.

## .gitkeep is a convention, not a Git feature

This trips people up: Git has no special knowledge of `.gitkeep`. It is just an ordinary
file with an agreed-on name. You could commit a `README.md` or a file called `.keep` and
the folder would survive exactly the same way. The name `.gitkeep` is popular only because
it signals intent to the next developer - "this file exists so Git keeps the folder".

## .gitkeep vs .gitignore

These sound similar and do opposite jobs:

- **`.gitignore`** tells Git which files to *leave out* of version control (see
  [gitignore](/course/git-basics/everyday-git/gitignore)).
- **`.gitkeep`** is a placeholder that *keeps* an otherwise-empty folder *in* version
  control.

They combine in a common pattern: keep a folder but ignore the files it will fill up with.
Put a `.gitignore` *inside* the folder that ignores everything except itself:

```text
# storage/logs/.gitignore
*
!.gitignore
```

Now the folder is committed (because `.gitignore` is a tracked file in it), but the log
files written at runtime are ignored. This is exactly how frameworks like Laravel keep
`storage/` directories in the repo while ignoring their generated contents - so you often
do not need a separate `.gitkeep` at all.

## Common mistake

Trying to `git add` the folder itself and expecting it to stick:

```bash
git add storage/logs/
# nothing happens - Git has no files in there to stage
```

Git stages files, so an empty folder adds nothing and `git status` stays clean. Add a
placeholder file instead. The reverse mistake is committing the folder *full* of generated
files (logs, cache, uploads) - keep the folder with a placeholder and ignore its contents.

## FAQ

### How do I commit an empty folder in Git?

You cannot commit a truly empty folder, because Git tracks files, not directories. Add a
placeholder file - by convention `.gitkeep` - then `git add` and commit it. The folder now
exists in the repository.

### What is a .gitkeep file?

A `.gitkeep` is an empty placeholder file you put in a folder so Git keeps that folder
under version control. It has no special meaning to Git; the name is just a shared
convention that signals why the file is there.

### Is .gitkeep an official Git feature?

No. Git treats `.gitkeep` like any other file. Any filename would work to keep the folder;
developers chose `.gitkeep` because it is self-explanatory.

### What is the difference between .gitkeep and .gitignore?

`.gitignore` excludes files from version control. `.gitkeep` is a placeholder that keeps an
empty folder in version control. They are unrelated jobs that are often used together -
keep a folder, ignore what it fills with.
