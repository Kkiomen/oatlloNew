---
title: "What is Git?"
slug: what-is-git
seo_title: "What Is Git? Version Control Explained for Beginners"
seo_description: "What is Git and what does version control mean? Learn how Git tracks changes, lets you work in parallel, and makes sure you never lose your work."
---

**Git** is a **version control system**: a tool that records the history of your
project so you can see what changed, when, and why, then roll back to any earlier
version. It's how most developers track code, whether they work alone or on a team.

## Why version control exists (the problem Git solves)

Imagine you're working on a project and you want to try a risky change. Without a
safety net, you might copy the whole folder and name it `project-backup-final-v2`.
A week later you have five of those folders and no idea which one is current.

Now add a second person. You both edit the same file, email versions back and forth,
and someone's work gets overwritten. There's no clean record of who changed what.

This is exactly what **version control** is for. It tracks every change to your
files over time, so you can see the full history, compare versions, undo mistakes,
and work with other people without stepping on each other's edits.

## So what is Git, specifically?

**Git** is a **version control system**. As you work, you tell Git to save
snapshots of your project. Each saved snapshot is called a **commit**, and together
the commits form a timeline you can browse, compare, and rewind.

We'll make real commits in the next chapter. For now, just hold the idea: Git
remembers every version you save, so you never truly lose work.

## Distributed: everyone has the full history

Git is a **distributed** version control system. That's a fancy way of saying every
copy of the project contains the *entire* history, not just the latest version.

When you copy a project from a server (you'll learn how in the remotes chapter),
you get all of its commits on your own machine. You can look at history, save new
commits, and review changes without an internet connection. You only need the
network to share your work with others.

## Snapshots, not differences

Some older tools store history as a list of differences: "line 4 changed, line 9
was deleted", and so on. Git works differently. Each commit is a **snapshot** of
what all your files looked like at that moment.

You can picture it like this:

```text
commit 1  ->  full picture of the project
commit 2  ->  full picture of the project
commit 3  ->  full picture of the project
```

If a file didn't change between commits, Git doesn't waste space storing it again -
it just points to the version it already has. But the mental model is snapshots:
each commit is a complete photo of your project, not a patch. This is why moving
around history in Git feels fast and reliable.

Why does the model matter this early? Because it changes how you read Git's output
later. When a tool talks in "differences", you think about lines. With Git you'll
think about whole states of the project, and that framing makes commands like
checking out an old version far less mysterious.

## Why developers use Git

- **A safety net.** Try anything; you can always return to a known-good version.
- **A clear history.** See what changed, when, and (through commit messages) why.
- **Working in parallel.** Branches let you build a feature without disturbing the
  main project. That's the branching chapter later on.
- **Collaboration.** Many people can work on the same project and combine their work.

## FAQ

### Is Git the same as GitHub?

No. **Git** is the tool that runs on your computer and tracks your project. **GitHub**
is a website that hosts Git projects online so you can back them up and share them.
You can use Git completely on your own without GitHub. We'll get to GitHub later.

### Do I need Git for small or solo projects?

Yes, it's still worth it. Even alone, you get a history you can undo and a record of
why you made each change. The habits are the same whether the project is tiny or
huge.

### Is Git only for code?

Git works best with text files like code, but you can track any files with it. It
just won't be able to show you a nice line-by-line history for things like images or
compiled binaries.
