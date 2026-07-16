---
title: "Your first repository"
slug: your-first-repository
seo_title: "git init: Create Your First Git Repository (Beginners)"
seo_description: "Create your first Git repository with git init. See what the hidden .git folder holds and why a repo is just a folder Git has started tracking."
---

Git is installed and configured, so it's time to create your first **repository**
with `git init`. A repository is the thing Git actually tracks, and it's simpler than
the word makes it sound.

## What is a Git repository?

A **repository** (or **repo**) is just a folder that Git is watching. It's an ordinary
directory with your project files inside, plus a little extra bookkeeping that lets
Git record the history of those files.

That's the whole idea. You don't move your project somewhere special. You take a
folder you already have - or a new empty one - and tell Git to start tracking it.

## Create a repository with git init

Let's make a fresh folder and turn it into a repository. In your terminal (or **Git
Bash** on Windows):

```bash
mkdir my-project
cd my-project
git init
```

Line by line:

- `mkdir my-project` creates a new folder called `my-project`.
- `cd my-project` moves you inside it.
- `git init` tells Git to start tracking this folder.

You'll see a message like:

```text
Initialized empty Git repository in /path/to/my-project/.git/
```

Because you set `init.defaultBranch` earlier, your repository starts on the `main`
branch. That's it - the folder is now a repository.

Running `git init` a second time in the same folder does no harm, by the way. Git
just reports it reinitialized the existing repository and leaves your history alone.
So if you're ever unsure whether a folder is already a repo, the safe move is to
check for the `.git` folder rather than guess.

## What is the hidden .git folder?

When you ran `git init`, Git created a hidden folder named `.git` inside your project.
This is where Git stores everything: your commits, your history, your settings for
this repo. On most systems it's hidden, but you can list it:

```bash
ls -a
```

```text
.  ..  .git
```

A few important points about `.git`:

- **It's the repository.** Delete this folder and the folder goes back to being an
  ordinary directory with no history. Your files stay, but Git forgets everything.
- **You don't edit it by hand.** Git manages what's inside. You'll never open these
  files yourself - you interact with them through `git` commands.
- **There's one per repository.** Each project you `git init` gets its own `.git`
  folder and its own separate history.

## Ran git init in the wrong folder?

Be careful *where* you run `git init`. If you run it in your home folder or on your
whole Desktop by accident, Git will try to track everything in there.

If you ever realize you initialized a repo in the wrong place, you can undo it by
deleting the `.git` folder:

```bash
rm -rf .git
```

This removes only Git's tracking. Your actual files are untouched. Then `cd` into the
correct folder and run `git init` there.

## FAQ

### Do I need internet to create a repository?

No. `git init` works entirely on your computer. Git is distributed, so your
repository and its full history live locally. You only need internet later, when you
share the project online.

### I ran git init but nothing seems to have happened. Is that normal?

Yes. Aside from the confirmation message and the hidden `.git` folder, nothing about
your files changes. The repository exists; you just haven't saved any snapshots into
it yet. That's the next chapter.

### Can I turn an existing project into a repository?

Absolutely. Just `cd` into that project's folder and run `git init`. Git starts
tracking the files that are already there.
