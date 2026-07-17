---
title: "Ignoring files with .gitignore"
slug: gitignore
seo_title: ".gitignore: Ignore Files and Folders in Git"
seo_description: "Use a .gitignore file to keep files out of Git: pattern syntax, per-project vs global ignore, and why it only affects untracked files, not tracked ones."
---

## Why you need to ignore files

Not everything in your project folder belongs in Git. Generated output like
`node_modules/` and `build/`, log files, secret keys - committing them bloats history
and, in the case of secrets, can burn you badly. You want Git to pretend they aren't
there.

A `.gitignore` file does exactly that. It's a plain text file listing what Git should
leave alone.

## Creating a .gitignore

Make a file named exactly `.gitignore` in your project root and list one pattern per
line:

```text
node_modules/
*.log
.env
build/
```

Now [git status](/course/git-basics/everyday-git/checking-status) will no longer show
those files as untracked. Git simply skips them. Commit the `.gitignore` file itself so
your teammates ignore the same things.

## Pattern syntax

A few rules cover almost everything:

- `node_modules/` - the trailing slash means "a directory". Ignores the whole folder.
- `*.log` - the `*` matches anything, so this ignores every file ending in `.log`.
- `.env` - a plain name ignores that exact file.
- `# comment` - lines starting with `#` are comments.
- `!important.log` - a leading `!` **un-ignores** something. This keeps `important.log`
  even though `*.log` is ignored.

Patterns match anywhere in your project unless you anchor them with a leading slash
(`/build/` ignores `build` only at the root).

The `!` negation has one catch that surprises people: it can't rescue a file whose parent
folder is already ignored. Once `logs/` is excluded, Git never descends into it, so
`!logs/keep.log` does nothing. Un-ignore the folder first, then re-ignore the parts you
don't want.

## Per-project vs global ignore

The `.gitignore` file lives **in the project** and is shared with everyone who clones it.
That's the right place for project-specific things like `build/` or `.env`.

But some files are personal - editor settings, OS junk like `.DS_Store` on macOS or
`Thumbs.db` on Windows. Those aren't the project's business; they're yours. Set up a
**global** ignore for them once, on your machine:

```bash
git config --global core.excludesfile ~/.gitignore_global
```

Then list your personal patterns in `~/.gitignore_global`. Now they're ignored in every
repository you work in, without polluting each project's `.gitignore`.

## Common mistake: it only ignores untracked files

This is the big one. `.gitignore` only affects files Git is **not already tracking**. If
you committed `debug.log` last week and *then* add `*.log` to `.gitignore`, Git keeps
tracking `debug.log` - the ignore rule is skipped for files already in the repo.

The fix is to stop tracking the file first (with `git rm --cached`, covered in the
[next lesson](/course/git-basics/everyday-git/moving-and-removing-files)). If you ever
hit "I added it to .gitignore but Git still sees it", this is almost always why - and a
[later troubleshooting lesson](/course/git-basics/real-world-and-troubleshooting/gitignore-not-working)
walks through it in full.

## FAQ

### Where do I put the .gitignore file?

Usually in the project root. Git also reads `.gitignore` files inside subfolders, whose
rules apply to that folder and below - but one file at the root covers most needs.

### Why is Git still tracking a file I added to .gitignore?

Because it was already tracked before you ignored it. `.gitignore` only stops **new**,
untracked files. You have to untrack it first for the rule to take effect.

### Should I commit the .gitignore file?

Yes. Committing it means everyone who clones the project ignores the same files
automatically. It's a shared part of the repo.
