---
title: "git reset --soft vs --hard explained"
slug: git-reset-explained
seo_title: "git reset --soft vs --mixed vs --hard"
seo_description: "git reset --soft vs --hard vs --mixed against HEAD~1: what each mode keeps and what it deletes, with a clear table and when to reach for which."
---

## What's the difference between git reset --soft, --mixed and --hard?

You made a commit and now want to undo it - move the branch back so that commit is no
longer the latest one. `git reset` does that. The catch: it has three modes, and they
treat your files very differently. Pick the wrong one and you can lose work. This lesson
makes the difference clear.

Throughout, `HEAD~1` means "one commit before the current one". So resetting to `HEAD~1`
undoes the **last** commit.

## The three modes

Every reset moves your branch pointer back to the commit you name. The `--soft`,
`--mixed` and `--hard` flags decide what happens to the changes from the commits you undid
- do they stay staged, stay in your files, or get thrown away?

### --soft: keep everything, staged

```bash
git reset --soft HEAD~1
```

Undoes the last commit but leaves all its changes **staged**, ready to commit again. Use
this when you want to redo the last commit - for example, to split it, or write a better
message by committing again.

### --mixed: keep everything, unstaged (the default)

```bash
git reset HEAD~1
```

`--mixed` is the default, so you can leave the flag off. It undoes the last commit and
keeps its changes in your working directory, but **unstaged**. Your edits are safe; you
just re-stage what you want. Use this when you want to rebuild the commit from scratch.

### --hard: DESTRUCTIVE, throw the changes away

```bash
git reset --hard HEAD~1
```

**This is destructive.** It undoes the last commit *and* deletes its changes from your
working directory. Any uncommitted work in tracked files is wiped too. After a `--hard`
reset, those changes are gone from your files. Use it only when you truly want the changes
gone.

## The comparison table

| Mode      | Moves branch back | Changes kept in working dir | Changes kept staged |
|-----------|-------------------|-----------------------------|---------------------|
| `--soft`  | Yes               | Yes                         | Yes                 |
| `--mixed` | Yes               | Yes                         | No (unstaged)       |
| `--hard`  | Yes               | **No (deleted)**            | No                  |

The pattern: `--soft` keeps the most, `--hard` keeps the least. `--mixed` sits in between
and is the safe default.

One detail that saves grief: even `--hard` leaves **untracked** files alone. It wipes
tracked changes, staged and unstaged, but a new file Git isn't watching yet stays on
disk. Reassuring, but don't lean on it - anything already tracked is fair game.

## When to use which

- Wrote a bad commit message and want to recommit the same changes? `--soft`.
- Want to undo a commit and re-pick what goes in it? `--mixed` (the default).
- Want the commit and its changes gone entirely? `--hard` - and be sure.

## Common mistake

Reaching for `--hard` when you only wanted to undo the *commit*, not the *work*. If you
just want the last commit gone but your edits kept, use the default (`git reset HEAD~1`),
not `--hard`. And **never** `--hard` reset a branch you've already pushed and shared -
that's rewriting shared history; use
[git revert](/course/git-basics/undoing-things/reverting-a-commit) instead.

If you do run `--hard` by accident, don't panic - a *committed* change can often be
rescued with the [reflog](/course/git-basics/undoing-things/recovering-with-reflog).
Uncommitted work wiped by `--hard`, though, is gone.

## FAQ

### What's the difference between git reset --soft, --mixed and --hard?

`--soft` keeps your changes staged, `--mixed` (the default) keeps them unstaged, and
`--hard` deletes them. All three move the branch pointer back; only `--hard` touches your
files destructively.

### How do I undo my last commit but keep the changes?

Run `git reset HEAD~1` (the default `--mixed`). The commit is undone and its changes stay
in your working directory, unstaged and ready to re-commit.

### Is git reset --hard reversible?

Only for committed history: you can often find the old commit with `git reflog` and reset
back to it. But any uncommitted changes destroyed by `--hard` are not recoverable.
