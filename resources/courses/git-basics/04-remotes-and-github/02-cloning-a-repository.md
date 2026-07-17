---
title: "Cloning a repository"
slug: cloning-a-repository
seo_title: "git clone: How to Clone a GitHub Repository"
seo_description: "How git clone works: copy a GitHub repository to your machine with full history, a working copy, and origin set up so push and pull just work."
---

When you want to work on a project that already exists on GitHub, you **clone** it.
Cloning downloads the whole repository - every commit, every branch, all the history -
and sets it up on your machine ready to use.

## The command

Go to the repository on GitHub, click the green **Code** button, and copy the URL. Then
run:

```bash
git clone https://github.com/someuser/some-repo.git
```

Git creates a new folder named after the repository (`some-repo`), downloads everything
into it, and checks out the default branch (`main`) so you have real files to look at.
Move into the folder and you're ready to work:

```bash
cd some-repo
```

## What clone sets up for you

Cloning does more than copy files. It hands you a fully wired-up repository:

- **A working copy.** The latest files from the default branch, checked out so you can
  edit them.
- **The full history.** Every commit and branch is downloaded, not just the current
  state. You can browse the log and switch branches offline.
- **A remote called `origin`.** Git automatically points `origin` at the URL you cloned
  from, so `git push` and `git pull` know where to go. Check it:

```bash
git remote -v
```

```text
origin  https://github.com/someuser/some-repo.git (fetch)
origin  https://github.com/someuser/some-repo.git (push)
```

- **A tracking connection.** Your local `main` branch is linked to the remote's `main`,
  so Git can tell you when you're ahead or behind. More on that in
  [fetching and pulling](/course/git-basics/remotes-and-github/fetching-and-pulling).

Because clone sets up `origin` and tracking for you, cloning is the easiest way to start
- there's nothing left to configure before you can push and pull.

## Cloning into a specific folder

By default Git names the folder after the repository. To choose a different name, add it
at the end:

```bash
git clone https://github.com/someuser/some-repo.git my-folder
```

The name comes from the last part of the URL, and the `.git` suffix is optional - Git
strips it either way. One trick worth knowing: a fresh clone into a new folder is the
quickest way to prove a bug isn't caused by leftover uncommitted files on your machine.
The clone has only what's actually committed and pushed, so if the problem disappears
there, the difference was living in your working directory.

## Common mistake

Do not run `git clone` inside a folder that's already a Git repository. Cloning creates
its own new folder with its own `.git`, and nesting one repository inside another leads
to confusion about which repository your commands are talking to. Clone into a plain,
empty directory - your projects folder, for example - and let Git make the subfolder.

## FAQ

### What's the difference between cloning and forking?

Cloning copies a repository to *your machine*.
[Forking](/course/git-basics/collaborating/forking-a-repository) (covered later in the
course) makes a copy under *your GitHub account* first. You often fork on GitHub, then clone
your fork to your machine.

### Do I need to run git init after cloning?

No. A cloned folder is already a Git repository - `git init` is only for starting a
brand new repository from scratch.

### Can I clone without a GitHub account?

You can clone public repositories without an account. You only need to authenticate to
clone private ones, or to push changes back.
