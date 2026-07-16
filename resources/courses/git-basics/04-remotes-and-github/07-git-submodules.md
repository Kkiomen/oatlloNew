---
title: "Git submodules"
slug: git-submodules
seo_title: "Git Submodules: Add, Clone and Update Them"
seo_description: "Git submodules explained: add another repository inside yours, clone a project with submodules, update them, and avoid the empty-folder trap."
---

Sometimes you want to include another Git repository inside your own - a shared library, a theme, a set of configs that lives in its own repo. Copying the files loses the connection to that project's history and updates. A **Git submodule** solves this: it links another repository into a folder of yours, pinned to a specific commit, while each repo keeps its own history.

## What a Git submodule is

A submodule is a repository nested inside another repository. The outer repo (the **parent**) does not store the submodule's files. Instead it stores two things:

- a `.gitmodules` file that records the submodule's URL and folder path,
- a pointer to **one exact commit** in the submodule (called a gitlink).

That "pinned to one commit" part is the whole idea. Your parent repo does not track the submodule's `main` branch - it tracks a specific commit, so everyone who clones your project gets the exact same version of the nested repo.

## Adding a submodule

Use `git submodule add` with the other repo's URL and the folder it should live in:

```bash
git submodule add https://github.com/acme/shared-lib.git libs/shared
```

This clones the repo into `libs/shared`, creates (or updates) `.gitmodules`, and stages both. Finish with a normal commit:

```bash
git commit -m "Add shared-lib submodule"
```

Now the parent repo records where the submodule comes from and which commit it points at.

## Cloning a project that has submodules

This is where almost everyone gets caught. A plain `git clone` (from the [cloning lesson](/course/git-basics/remotes-and-github/cloning-a-repository)) copies the parent repo but leaves the submodule folders **empty**. To pull the submodules too, add `--recurse-submodules`:

```bash
git clone --recurse-submodules https://github.com/acme/app.git
```

If you already cloned without it, fill the empty folders afterward:

```bash
git submodule update --init --recursive
```

`--init` sets them up from `.gitmodules` and `--recursive` handles submodules that themselves contain submodules.

## Updating a submodule

A submodule stays on its pinned commit until you move it on purpose. To advance it to the latest commit of its remote branch:

```bash
git submodule update --remote libs/shared
```

Then - and this is the step people forget - the parent repo now points at a new commit, so you must commit that pointer:

```bash
git add libs/shared
git commit -m "Bump shared-lib submodule"
```

Without that commit, the update lives only on your machine and nobody else gets it.

## Common mistake: the empty-folder trap

If a teammate clones the project and the submodule folder is empty, the fix is almost always the missing recurse step: run `git submodule update --init --recursive`. Remember that the parent tracks a **commit**, not a branch, so pulling the parent does not automatically move the submodule - you update it explicitly and commit the new pointer. Treating a submodule like a normal folder (editing its files and expecting the parent to just save them) is the usual source of confusion.

## FAQ

### How do I clone a repository with all its submodules?

Use `git clone --recurse-submodules <url>`. If you already did a plain clone, run `git submodule update --init --recursive` to populate the empty submodule folders.

### Why is my submodule folder empty after cloning?

A plain `git clone` does not fetch submodule contents. It only records the pointer. Run `git submodule update --init --recursive` to download them, or clone with `--recurse-submodules` next time.

### Why doesn't my submodule update when I pull the main project?

Because the parent repo pins the submodule to a specific commit, not a branch. Pulling the parent only changes the submodule if someone committed a new pointer. To move it yourself, run `git submodule update --remote <path>` and commit the change.

### How do I remove a submodule?

Run `git rm <path>` to remove it and stage the change, then commit. Git deletes the folder and its entry in `.gitmodules`. You may also want to delete the leftover config under `.git/modules/<path>`.
