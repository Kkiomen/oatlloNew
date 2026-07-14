---
name: "10 Useful Git Commands That Save Developers Time"
slug: useful-git-commands
short_description: "10 useful Git commands that save developers real time, with correct examples and scenarios for stash, rebase, bisect, reflog, worktree and more."
language: en
published_at: 2026-08-19 09:00:00
is_published: true
tags: [git, cli, productivity, workflow]
---

Everyone learns `add`, `commit`, and `push` in their first week. Then they stop. That's a shame, because the most **useful Git commands** are the ones nobody shows you in the tutorial — the ones that dig you out of a bad merge, find the commit that broke production, or let you juggle three branches without stashing your sanity.

I've been using Git daily for years, and the list below is not academic. Each command here has personally saved me time on real work: a broken build at 6pm, a "wait, where did that code go?" moment, a review comment I needed to fix without a messy follow-up commit. I'll show the exact syntax, explain what it does, and tell you where it actually pays off. A couple of them rewrite history, so I'll flag the danger too.

## 1. `git switch -c` — create and jump to a branch in one move

For years I typed `git checkout -b`. It works, but `checkout` is overloaded: it switches branches *and* restores files, which is confusing. Modern Git split those jobs. Use `switch` for branches.

```bash
git switch -c feature/user-export
```

This creates `feature/user-export` and moves you onto it. Want to hop back to an existing branch? Drop the `-c`:

```bash
git switch main
```

The payoff is clearer intent and fewer "oops, I branched off the wrong thing" mistakes. When a hotfix lands and you need a branch off `main` right now, `switch -c` is muscle memory that never bites you.

## 2. `git restore` — undo local changes without the scary syntax

The other half of the old `checkout`. `git restore` throws away uncommitted changes to a file.

```bash
git restore src/payment.js
```

That reverts `payment.js` to whatever is in your last commit. Already staged something and want to unstage it (but keep the edits)?

```bash
git restore --staged src/payment.js
```

I reach for this after a failed experiment. Instead of manually undoing edits or fighting `reset`, one command puts a file back to its last committed state. One caveat: restoring discards uncommitted work permanently. There's no undo for changes Git never recorded.

## 3. `git stash` — park your work and come back later

You're halfway through a feature when a bug report drops. You can't commit half-broken code, but you need a clean tree to switch branches. Stash it.

```bash
git stash
```

Your changes vanish into a stack; the working tree is clean. Fix the bug on another branch, come back, and pull your work out:

```bash
git stash pop
```

Forgot what you stashed, or stashed a few things? List them:

```bash
git stash list
```

This one genuinely saved me during a live incident. Deep in a refactor, ops pinged me about a broken deploy. `git stash`, switch, patch, deploy, switch back, `git stash pop` — refactor untouched, no throwaway commit in the history.

## 4. `git log --oneline --graph --all` — see the branch topology at a glance

Plain `git log` is a wall of text. This combo draws the actual shape of your history in the terminal.

```bash
git log --oneline --graph --all
```

`--oneline` compresses each commit to one line, `--graph` draws the branch/merge lines, and `--all` includes every branch, not just the current one.

Before a tricky merge or rebase, I run this to understand where branches diverged and what's already merged. It answers "did that PR make it into the release branch?" in two seconds without opening a browser. Make it an alias (`git lg`) and you'll use it constantly.

## 5. `git commit --amend` — fix the last commit instead of piling on

You committed, then noticed a typo in the message, or you forgot to stage one file. Don't make a "fix typo" commit. Amend.

```bash
git commit --amend -m "Add user export endpoint"
```

Forgot a file? Stage it first, then amend without changing the message:

```bash
git add src/routes.js
git commit --amend --no-edit
```

The win here is clean history during code review. Reviewers see one coherent commit, not "add feature" followed by three "oops" commits.

**Danger:** amending rewrites the commit. If you already pushed it and others pulled it, amending forces a `--force-with-lease` push and can disrupt teammates. Amend freely on local, unpushed commits; think twice on shared branches.

## 6. `git rebase -i` — clean up a messy branch before you open the PR

Interactive rebase lets you rewrite a series of commits: reorder them, squash several into one, reword messages, or drop them entirely.

```bash
git rebase -i HEAD~4
```

That opens an editor listing your last four commits. Change `pick` to `squash` (or `s`) to fold a commit into the one above it, `reword` to edit a message, or `drop` to delete it.

My feature branches are honestly a mess mid-work: "wip", "wip 2", "fix test". Before the PR, I squash them into two or three meaningful commits. Reviewers get a clean story instead of my stream of consciousness.

**Danger:** this rewrites history. Only rebase commits you haven't shared, or a branch you're certain nobody else is building on. Rebasing shared history and force-pushing is how you make enemies.

## 7. `git cherry-pick` — grab one specific commit

Sometimes you need exactly one commit from another branch: a hotfix that's on `main` but needs to land on the release branch too. Cherry-pick copies that commit onto your current branch.

```bash
git cherry-pick a1b2c3d
```

You can pick a range as well:

```bash
git cherry-pick a1b2c3d^..f4e5d6c
```

Backporting fixes is the classic case. A critical patch merged to `main`, but the `release/2.3` branch still needs it and you don't want the twenty other commits that came with it. Cherry-pick the one you care about and move on.

## 8. `git bisect` — binary-search your way to the commit that broke it

Something worked last week and is broken now, and you have no idea which of 200 commits did it. `git bisect` runs a binary search through history, and you tell it "good" or "bad" at each step.

```bash
git bisect start
git bisect bad
git bisect good v1.4.0
```

Git checks out a commit halfway between good and bad. Test it, then tell Git the result:

```bash
git bisect good   # or: git bisect bad
```

Repeat a handful of times and Git names the exact offending commit. When you're done, reset:

```bash
git bisect reset
```

This is the one that feels like magic. Instead of reading diffs for an hour, ten commits get narrowed in a handful of tests. I once found a subtle performance regression in about six steps across ~600 commits. Nothing else comes close.

## 9. `git reflog` — recover commits you thought you destroyed

Deleted a branch? Botched a reset? Lost commits after a bad rebase? They're probably not gone. Git records almost every move of `HEAD` in the reflog.

```bash
git reflog
```

You'll see a list like `HEAD@{2}: commit: ...`. Find the state you want and get back to it:

```bash
git switch -c recovered HEAD@{5}
```

Call it panic prevention. `git reset --hard` on the wrong commit used to mean lost work. Now I know the reflog has my back for a couple of months. This single command has talked me down from more than one small heart attack.

## 10. `git worktree add` — multiple branches checked out at once

You're mid-feature and need to review a colleague's branch, or run a long test suite on `main` without touching your dirty working tree. Instead of stashing and switching, `worktree` gives you a second working directory linked to the same repo.

```bash
git worktree add ../project-review origin/colleague-branch
```

Now `../project-review` is a full checkout of that branch, sharing the same `.git` history. Work in both folders at the same time. When you're finished, clean it up:

```bash
git worktree remove ../project-review
```

No more stash-switch-stash dance just to peek at another branch. I keep one worktree for `main` running a slow build while I keep coding in the primary folder. Two branches, zero context loss.

## FAQ

**What's the difference between `git switch` and `git checkout`?**
`checkout` does two unrelated jobs (switching branches and restoring files), which trips people up. Newer Git splits them: `switch` handles branches, `restore` handles files. `checkout` still works, but the newer commands are clearer about intent.

**Is `git rebase` dangerous?**
Only when you rewrite commits others have already pulled. Rebasing local, unshared commits is completely safe and produces cleaner history. The rule of thumb: never rebase (or amend, or force-push) a branch other people are actively working on.

**Can I recover work after `git reset --hard`?**
Usually yes. Run `git reflog`, find the `HEAD@{n}` entry from before the reset, and check it out or branch from it. Git keeps unreachable commits for around 90 days before garbage collection removes them, so act sooner rather than later.

**Do I need to memorize all of these?**
No. Start with `stash`, `switch -c`, and `log --oneline --graph --all`. You'll use them daily. Add `reflog`, `bisect`, and `worktree` as the situations that need them show up. They stick fast once they've saved you once.

## Wrapping up

Git is huge, but you don't need all of it. These ten commands cover the situations that eat the most time: parking half-done work (`stash`), cleaning history before review (`rebase -i`, `commit --amend`), hunting bugs (`bisect`), recovering mistakes (`reflog`), and running parallel branches (`worktree`).

Pick two you don't use yet and force yourself to reach for them this week. `git bisect` and `git worktree` are the two I'd bet you'll wish you learned years ago. The rest of the list is here whenever the situation shows up — and in this job, it always does.