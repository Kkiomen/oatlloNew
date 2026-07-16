---
title: "The three areas"
slug: the-three-areas
seo_title: "Git's Three Areas: Working Directory, Staging, Repository"
seo_description: "Git's core mental model: the working directory, the staging area (index) and the repository, plus how a single change moves through all three."
---

If one lesson in this chapter is worth rereading, it's this one. Almost everything
you'll do with Git makes sense once you understand the **three areas** a change
passes through: the working directory, the staging area, and the repository. Get this
model and the rest of the course clicks into place.

## The three areas: working directory, staging, repository

Every Git project has three places your work can live:

- **Working directory** - the folder you actually edit. These are the real files you
  open, change, and save in your editor.
- **Staging area** (also called the **index**) - a holding area where you gather the
  exact changes you want to save next.
- **Repository** - the saved history, stored in the hidden `.git` folder. This is
  where snapshots (commits) live permanently.

You can picture them left to right:

```text
working directory   ->   staging area   ->   repository
   (you edit)            (you gather)         (you save)
```

Work flows in that direction: you change files, choose which changes to stage, then
commit them into history.

## How a change moves from edit to commit

Let's follow one edit through all three areas. (You'll run these exact commands in
the next chapter - here we're just building the picture.)

**1. You edit a file.** Say you change `notes.txt` in your editor. That change now
exists only in the **working directory**. Git sees the file is different but hasn't
been told to do anything with it.

**2. You stage the change.** You tell Git "this change is part of the next snapshot":

```bash
git add notes.txt
```

The change is now in the **staging area**. It's selected, but not yet saved into
history.

**3. You commit.** You save everything that's staged as a permanent snapshot:

```bash
git commit -m "Add notes"
```

The change moves into the **repository**. It's now part of your project's history,
and you can always come back to it.

## Why does Git have a staging area?

Beginners often ask why there's a middle step. Why not go straight from editing to
saving?

The staging area lets you **choose exactly what goes into each commit**. Maybe you
changed three files but only two of them belong together. You stage those two, commit
them with a clear message, then stage and commit the third separately.

The result is a clean history where each commit is one focused change, instead of a
messy pile of unrelated edits saved together. You'll practice this in the next
chapter, including staging specific files.

## Why isn't my latest edit in the commit?

A very common early mix-up: editing a file *after* you've staged it, and expecting the
new edit to be in the commit. It won't be.

`git add` takes a snapshot of the file *as it is right then*. If you edit it again
afterward, that newer change stays in the working directory until you `git add` it
once more. So the rule is: stage, then commit - and if you edit again, stage again.

## FAQ

### What's the difference between the staging area and the index?

Nothing - they're two names for the same thing. Older Git documentation says
"index", newer material says "staging area". When you see either word, think of that
middle holding area.

### Do I always have to stage before I commit?

Yes, something has to be staged for a commit to include it. There are shortcuts later
that stage and commit in one step, but under the hood the change still passes through
the staging area.

### Where is the staging area stored?

Inside the hidden `.git` folder you created with `git init`. You don't edit it
directly - `git add` and `git commit` move things through it for you.
