---
slug: git-commands-carousel
type: carousel
language: en
title: "Git commands that save real time"
topic: git
source_type: article
source: useful-git-commands
link: https://oatllo.com/useful-git-commands
publish_at: 2026-08-14 19:00
status: ready
formats: [post, reel]
hashtags: [git, cli, productivity, workflow, devtools]
caption: |
  You learned add, commit and push in week one. Then you stopped.

  The commands that save real time are the ones no tutorial reaches: the ones
  that dig you out of a bad reset, park half a feature, or get back the branch
  you deleted an hour ago.

  Ten of them are linked in bio.

  Which one did you learn embarrassingly late?
verified:
  verdict: approved
  at: 2026-07-16 07:16
  fingerprint: 5db78981a682242dddd068df8f7cbde7feff1034
  checks:
    - "every command is real and correctly formed: git switch -c, git switch main, git restore FILE, git restore --staged FILE, git stash, git stash pop, git reflog, git switch -c recovered HEAD@{5}"
    - the restore distinction is right and is the part most often got backwards - plain restore discards working-tree edits, --staged only unstages and keeps them; matches the article
    - checkout being overloaded into switch (branches) and restore (files) is accurate git history and matches the article FAQ
    - reflog recording almost every move of HEAD, and reset --hard being recoverable from it, traced to the article; a couple of months is the article own phrasing and squares with its 90-day gc note (git default gc.reflogExpire is 90 days)
    - caption says ten commands - the source article is literally 10 Useful Git Commands, count matches
  notes: |
    topic git is right. Nothing version-pinned that ages: switch and restore have been stable since git 2.23 and the post never calls them new. Only diff from the source is a shortened branch name (feature/export vs feature/user-export), which changes nothing.
---

## You learned add, commit, push. Then you stopped.

The Git that saves real time is the part no tutorial reaches.

<!-- slide -->

## checkout was doing two jobs

```bash
git switch -c feature/export
git switch main
```

Git split them. `switch` moves branches, and nothing else.

<!-- slide -->

## restore is the other half

```bash
git restore src/payment.js
git restore --staged src/payment.js
```

First one throws away your edits. Second one only unstages and keeps them.

<!-- slide -->

## Park the work, keep a clean tree

```bash
git stash
git stash pop
```

A bug lands mid feature. You cannot commit half of it, but you need a clean
tree to switch.

<!-- slide -->

## The one that saves your afternoon

```bash
git reflog
git switch -c recovered HEAD@{5}
```

Bad reset? Deleted branch? Git recorded almost every move of HEAD.

<!-- slide role="cta" -->

## It was never gone

`reset --hard` on the wrong commit used to mean lost work. The reflog has your
back for a couple of months.
