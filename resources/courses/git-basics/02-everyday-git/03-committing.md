---
title: "Committing with git commit"
slug: committing
seo_title: "git commit: Save a Snapshot to Your History"
seo_description: "Run git commit and git commit -m to save staged changes. Learn what a commit really is: a snapshot with a message, author and parent link."
---

## What a commit actually is

Staging with [git add](/course/git-basics/everyday-git/staging-changes) picks the
changes; a **git commit** writes them permanently into the project's history. A commit
isn't just "a save". It's a record with four parts:

- A **snapshot** of your staged files at that moment.
- A **message** describing the change.
- An **author** (your name and email, from your Git config).
- A **parent** - the commit that came before it, which is how Git links history into a
  chain.

That parent link is why history is a timeline: each commit points back to the one
before, all the way to the first.

## Making a commit

Stage something, then commit it with a message using the `-m` flag:

```bash
git add index.html
git commit -m "Add homepage heading"
```

Git replies with something like:

```text
[main 9f3a1c2] Add homepage heading
 1 file changed, 3 insertions(+), 1 deletion(-)
```

`main` is your branch, `9f3a1c2` is the start of the commit's unique ID (its hash), and
the summary tells you what moved. That's it - the change is now part of history.

## Why -m matters

The `-m` flag lets you write the message right in the command. If you leave it out:

```bash
git commit
```

Git opens a text editor for you to type the message. That's fine, but for short messages
`-m` is faster. Every commit **must** have a message - Git won't let you commit without
one.

## Only staged changes get committed

This is the part beginners miss. `git commit` saves what's **staged**, nothing else. If
you edited three files but only staged one, only that one is committed. The other two
stay as they were, waiting.

So the everyday rhythm is always the same:

```bash
git status
git add <files>
git commit -m "message"
```

Check, stage, commit. Run
[git status](/course/git-basics/everyday-git/checking-status) after committing and you'll
see a clean tree (assuming you staged everything you meant to).

## Common mistake

Running `git commit -m "..."` with nothing staged. Git will refuse and say "nothing to
commit". Editing a file is not enough - you must `git add` it first. The fix is simply to
stage your changes, then commit.

## FAQ

### What is a commit hash?

It's a unique ID Git generates for each commit (a long string like `9f3a1c2...`). You
usually only need the first 7 characters to refer to one. Git computes the hash from the
snapshot, message, author and parent together, so changing any of them produces a
different hash - which is why you can't quietly rewrite a commit and keep its ID.

### Where does the author name come from?

From the `user.name` and `user.email` you set when
[configuring Git](/course/git-basics/getting-started/configuring-git). Every commit is
stamped with them.

### Can I change a commit after making it?

The last commit can be changed, and there's a whole chapter on undoing things later. For
now, just aim to stage carefully before you commit.
