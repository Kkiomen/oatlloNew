---
title: "Stash work in progress with git stash"
slug: stashing-work
seo_title: "git stash: save work in progress in Git"
seo_description: "Use git stash, pop, list and drop to shelve unfinished changes, switch branches with a clean tree, then bring your work back exactly where you left it."
---

## How do I save work without committing using git stash?

You're halfway through editing, nothing is ready to commit, and you suddenly need to
switch branches - a colleague wants a quick fix on `main`. Git often won't let you switch
with a dirty working tree, and committing half-finished work just to move is no fun.
`git stash` is the fix: it shelves your uncommitted changes and hands you a clean tree,
ready to pick up later.

## Stash your changes

```bash
git stash
```

This takes all your uncommitted changes (staged and unstaged) off your working directory
and saves them on a stack. Your tree is now clean, matching the last commit - as if you
hadn't started editing. Run `git status` to confirm.

Now you're free to switch branches, pull, or do that quick fix:

```bash
git switch main
```

## Bring your work back with pop

When you're ready to continue, restore the stashed changes:

```bash
git stash pop
```

`pop` re-applies the most recent stash to your working directory **and removes it from the
stack**. Your changes are back exactly where you left them. Make sure you're on the branch
where you want them before you pop.

Handy safety detail: if the pop can't apply cleanly - the branch moved on and the changes
now conflict - Git leaves the stash **on the stack** instead of dropping it. So a failed
`pop` never loses the stash. Resolve the conflict, then drop it yourself once you're happy.

## Managing multiple stashes

You can stash more than once; they pile up on a stack. List them:

```bash
git stash list
```

```text
stash@{0}: WIP on feature: 3f1a2b4 Add login form
stash@{1}: WIP on main: 9c4d5e6 Update readme
```

Apply a specific one without removing it from the stack:

```bash
git stash apply stash@{1}
```

(`apply` is like `pop` but keeps the stash on the stack, in case you want it again.)

Give a stash a name as you make it, so the list is readable later:

```bash
git stash push -m "half-done login validation"
```

## Dropping a stash (DESTRUCTIVE)

Delete a stash you no longer need:

```bash
git stash drop stash@{0}
```

**This is destructive.** Dropping a stash discards those saved changes, and they aren't
easy to get back. Only drop a stash once you're sure you don't need it - usually after a
successful `pop`, which removes it for you anyway.

## Common mistake

Forgetting a stash exists. Because stashed changes vanish from your working tree, it's easy
to walk away and lose track of them - especially across branches. Get in the habit of
running `git stash list` so nothing sits forgotten on the stack. Also note: `git stash` by
default ignores **untracked** files (new files Git isn't watching yet). Add `-u` to include
them: `git stash -u`.

## FAQ

### How do I save my changes without committing in Git?

Run `git stash`. It shelves your uncommitted changes and gives you a clean working tree.
Bring them back later with `git stash pop`.

### What's the difference between git stash pop and git stash apply?

Both re-apply your stashed changes. `pop` also removes the stash from the stack; `apply`
leaves it there so you can re-use it. Use `apply` when you're not sure it'll go smoothly.

### Does git stash include untracked files?

Not by default - only tracked changes are stashed. Use `git stash -u` to include untracked
files as well.
