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
