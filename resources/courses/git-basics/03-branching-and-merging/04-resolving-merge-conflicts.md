---
title: "Resolving merge conflicts"
slug: resolving-merge-conflicts
seo_title: "How to Resolve a Git Merge Conflict Step by Step"
seo_description: "Resolve a Git merge conflict: read the conflict markers, edit the file, then git add and git commit to finish - or git merge --abort to back out safely."
---

Sooner or later a merge stops and prints **CONFLICT**. A merge conflict is not an
error and not a broken repository, even though it looks alarming the first time. It
means Git hit a spot where it can't decide for you and is handing the choice back.
Here's how to work through one calmly.

## What is a merge conflict?

When you merge, Git tries to combine the changes from both branches automatically. It
succeeds almost every time - even when both branches changed the same file, as long as
they changed *different parts* of it.

A **conflict** happens only when both branches changed the **same lines** of the same
file in different ways. Git has no way to know which version you want, so instead of
guessing, it pauses the merge and asks you.

## When a conflict happens

You run a normal merge:

```bash
git switch main
git merge new-feature
```

And instead of finishing, Git tells you:

```text
Auto-merging notes.txt
CONFLICT (content): Merge conflict in notes.txt
Automatic merge failed; fix conflicts and then commit the result.
```

The merge is now paused. Run `git status` and Git lists the conflicted files under
"Unmerged paths". Your job is to fix each one.

## Reading the conflict markers

Open the conflicted file. Git has inserted **conflict markers** around the part it
couldn't merge:

```text
<<<<<<< HEAD
The price is 20 dollars.
=======
The price is 25 dollars.
>>>>>>> new-feature
```

Read it like this:

- `<<<<<<< HEAD` starts the version from your **current branch** (`main`).
- `=======` is the divider between the two versions.
- `>>>>>>> new-feature` ends the version from the **branch you're merging in**.

So the top block is your side, the bottom block is their side, and Git wants you to
decide what the final text should be.

## Resolve it: edit the file

To resolve the conflict, edit the file so it reads exactly how you want the final
version to look, and **remove all three marker lines**. You might keep one side, keep
the other, or combine them.

For example, if 25 dollars is correct, the finished section is simply:

```text
The price is 25 dollars.
```

No `<<<<<<<`, no `=======`, no `>>>>>>>`. The most common mistake by far is leaving a
marker line behind - it becomes part of your file and quietly breaks things later.
Search the file for `<<<<<<<` to be sure you got them all.

When you know you just want one whole side and don't care about the other, you can
skip the hand-editing: `git checkout --ours notes.txt` keeps your current branch's
version of the file, and `git checkout --theirs notes.txt` keeps the incoming
branch's. Both replace the entire file, markers and all, so use them only when the
whole file should come from one side.

## Finish: git add and git commit

Once the file looks right, stage it to tell Git the conflict is resolved:

```bash
git add notes.txt
```

Do this for each file that was conflicted. When `git status` shows nothing left
unmerged, complete the merge with a commit:

```bash
git commit
```

Git already has a merge message prepared, so you can usually just save and close the
editor. The merge is now done, with your resolved version baked in.

## Backing out: git merge --abort

If you get halfway through and decide you don't want to deal with the conflict right
now, you can cancel the whole merge and go back to exactly how things were before you
started:

```bash
git merge --abort
```

This throws away the in-progress merge and restores your branch to its pre-merge
state. Nothing is lost, and you can try again later. It's a safe escape hatch whenever
a merge gets confusing.

## Common mistake

Beyond leaving markers behind, the other classic mistake is panicking and assuming the
repository is broken. It isn't. A conflict is a normal, expected pause. Work through
the files one at a time, `git add` each once it's clean, and `git commit` to finish -
or `git merge --abort` if you'd rather step away. Checking
[what changed](/course/git-basics/everyday-git/seeing-changes) with `git diff` can
help you understand each side before you edit.

## FAQ

### What causes a merge conflict?

Two branches changing the same lines of the same file in different ways. Git can merge
different parts of a file automatically, but when edits overlap it can't decide which
version wins, so it asks you.

### What do the <<<<<<< and >>>>>>> markers mean?

They mark the two conflicting versions. The block after `<<<<<<< HEAD` is your current
branch's version, `=======` divides the two, and the block before `>>>>>>>` is the
incoming branch's version. Edit the file to the final text and delete all three lines.

### How do I finish a merge after fixing conflicts?

Edit each conflicted file, remove the markers, run `git add` on it, and then run
`git commit`. Once all conflicts are staged and committed, the merge is complete.

### How do I cancel a merge that has conflicts?

Run `git merge --abort`. It undoes the in-progress merge and returns your branch to
exactly the state it was in before you ran `git merge`.
