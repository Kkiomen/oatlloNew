---
title: "Staging changes with git add"
slug: staging-changes
seo_title: "git add: Stage Changes to the Staging Area"
seo_description: "Use git add and the staging area to stage specific files or run git add . - and control exactly what goes into your next Git commit."
---

## What the staging area is for

Git never commits your changes automatically. You pick which ones go into the next
commit first, and that picking happens with `git add` in the **staging area** (also
called the index). Staging is how you say: these changes are ready, that other one isn't.

Think of it as a loading dock. Files wait there until you decide to ship them.

## Staging a specific file

Say you edited `index.html` and `style.css`, but only the HTML change is finished. Stage
just that one:

```bash
git add index.html
```

Run [git status](/course/git-basics/everyday-git/checking-status) again and you'll see
`index.html` move under "Changes to be committed", while `style.css` stays unstaged.
You just controlled exactly what your next commit will contain.

You can stage several named files at once:

```bash
git add index.html style.css
```

## Staging everything with git add .

When every change in the current folder should go in, the dot is a shortcut for "all
changes here and below":

```bash
git add .
```

This stages new files, edited files, and deletions in the current directory tree. Handy,
but be deliberate. It's easy to sweep up something you didn't mean to commit. One detail
that trips people: the dot means "here and below", so running it inside a subfolder skips
changes above that folder. From the repo root it catches everything. When in doubt, run
`git status` first to see what `git add .` would grab.

## git add . vs specific files

Both do the same kind of work; they differ in scope:

- `git add file.txt` - stage exactly one file. Precise and safe.
- `git add .` - stage everything changed under the current folder. Fast, but broad.

A good habit early on: name your files. It forces you to look at what you're committing.
Reach for `git add .` once you're confident everything changed belongs together.

## Staging is a snapshot, not a link

Here's a subtle point that surprises people. When you `git add` a file, Git records the
file **as it is right now**. If you edit that same file again afterward, the new edit is
**not** staged - only the earlier version is.

`git status` will then list the file in both groups at once: staged (the first edit) and
not staged (the later edit). That's not a bug. Just run `git add` again to update the
staged copy to the current version.

## FAQ

### Does git add commit my changes?

No. `git add` only stages - it prepares changes for the next commit. Nothing is saved to
history until you run `git commit` (the next lesson).

### Can I unstage a file I added by mistake?

Yes. `git status` shows the exact command, `git restore --staged <file>`. Undoing
changes gets a full chapter later; for now, know that staging is reversible.

### What's the difference between the staging area and the working directory?

The working directory is your actual files on disk. The staging area is Git's list of
what will go into the next commit. `git add` copies a change from one to the other.
