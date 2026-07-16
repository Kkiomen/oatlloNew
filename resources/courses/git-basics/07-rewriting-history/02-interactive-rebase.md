---
title: "Interactive rebase"
slug: interactive-rebase
seo_title: "Git Interactive Rebase: Squash, Reword, Reorder"
seo_description: "Clean up a branch before a pull request with git interactive rebase: squash commits, reword, reorder, edit and drop, step by step for beginners."
---

Your feature works, but the history is a mess: `fix typo`, `wip`, `oops`, `actually fix
it`. A **git interactive rebase** (`git rebase -i`) lets you squash, reword, and reorder
those commits into a clean set before you open a pull request, so reviewers see a tidy
story instead of your fumbling.

## The command

Pick how many commits back you want to edit, counting from the tip. To reshape the
last 4 commits:

```bash
git rebase -i HEAD~4
```

`-i` means interactive; `HEAD~4` means "starting 4 commits before HEAD". Git opens an
editor with those commits listed, oldest at the top:

```text
pick a1b2c3d Add login form
pick b2c3d4e wip
pick c3d4e5f fix typo
pick d4e5f6a actually make it work
```

Each line starts with an action. You change the action words, save, and close the
editor. Git then replays the commits following your instructions.

Read that list top to bottom and it runs oldest to newest, which is the reverse of what
`git log` shows you. Trips up nearly everyone the first time. The oldest commit sits at
the top precisely because Git replays from there downward.

## The actions you can use

```text
pick     keep the commit as-is
reword   keep the commit, but change its message
squash   merge this commit into the one above it, combine messages
fixup    like squash, but discard this commit's message
edit     pause here so you can change the commit's content
drop     delete the commit entirely
```

You can also **reorder** commits by moving the lines up or down in the editor.

## Squash: combine several commits into one

This is the most common cleanup. Turn four messy commits into one solid commit by
changing every line after the first to `squash` (or `fixup` to throw away the noise
messages):

```text
pick a1b2c3d Add login form
fixup b2c3d4e wip
fixup c3d4e5f fix typo
fixup d4e5f6a actually make it work
```

Save and close. Git combines all four into a single commit. If you'd used `squash`
instead of `fixup`, Git would open one more editor so you can write a clean combined
message.

Both `squash` and `fixup` fold a commit into the line **above** it. That means the very
first line has nothing to squash into, so leaving it as `pick` is not optional, it's the
only thing that works. If you want to drop the oldest commit's message too, reword the
first `pick` and fixup the rest.

## Reword: fix a bad message

Change `pick` to `reword` on the commit whose message you want to improve:

```text
reword a1b2c3d Add login form
```

Git replays up to that commit, then opens the editor so you can rewrite the message.
Everything you learned in [good commit messages](/course/git-basics/everyday-git/good-commit-messages)
applies here - this is your chance to apply it after the fact.

## Edit: change the actual content of an old commit

Mark a commit with `edit` and Git stops there, mid-rebase, with that commit checked
out. You make your change, stage it, and amend:

```bash
# ... Git paused at the commit ...
# make your edits, then:
git add .
git commit --amend
git rebase --continue
```

`git rebase --continue` resumes and replays the rest. Amending is exactly what you saw
in [amending the last commit](/course/git-basics/undoing-things/amending-the-last-commit),
just applied to a commit in the middle of the branch.

## Reorder and drop

To reorder, move the lines. To delete a commit, change its action to `drop` (or just
delete the line):

```text
pick a1b2c3d Add login form
drop b2c3d4e Add debug logging I forgot to remove
pick c3d4e5f Add validation
```

## If you get stuck

An interactive rebase can hit a conflict, just like a merge. Resolve the files, stage
them, and continue. If it all goes wrong, back out safely:

```bash
git rebase --abort
```

That returns your branch to exactly how it was before you started. Nothing is lost.

## Common mistake

Running `git rebase -i` on commits you've already pushed to a shared branch. Because
rebase rewrites commit IDs, your local history no longer matches the remote, and
pushing will be rejected (or force you into a dangerous force-push). Only clean up
commits that are still private to your machine. The final lesson of this chapter
covers exactly where that line is.

## FAQ

### What does HEAD~4 mean?

It's the commit four steps back from your current position (HEAD). `git rebase -i
HEAD~4` lets you edit the last four commits. Use `HEAD~2` for two, and so on.

### What's the difference between squash and fixup?

Both combine a commit into the one above it. `squash` keeps both messages and lets you
edit the result; `fixup` silently discards the squashed commit's message. Use `fixup`
for junk like "wip".

### Can I undo an interactive rebase after it finishes?

Yes. The old commits are still in the reflog for a while. `git reflog` shows where your
branch was, and you can reset back to it - see
[recovering with reflog](/course/git-basics/undoing-things/recovering-with-reflog).

### The editor opened and I don't know what to do

The default editor may be Vim. Save and quit with `Esc` then `:wq`. To set a friendlier
editor, revisit [configuring Git](/course/git-basics/getting-started/configuring-git).
