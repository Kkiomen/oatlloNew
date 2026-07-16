---
title: "Viewing history with git log"
slug: viewing-history
seo_title: "git log: View Commit History (--oneline, --graph)"
seo_description: "Read your Git history with git log and its most useful flags: --oneline, --graph, -p and -n to see commits the way you need them."
---

## What git log shows

Every commit you make is stored in history, and `git log` prints that history, newest
first:

```bash
git log
```

You'll see an entry per commit:

```text
commit 9f3a1c2b7e4d0a1f2c3d4e5f6a7b8c9d0e1f2a3b
Author: Jane Dev <jane@example.com>
Date:   Mon Jul 13 10:22:04 2026 +0200

    Add password reset endpoint
```

Each block shows the full commit hash, the
[author](/course/git-basics/everyday-git/committing), the date, and the message. Press
the space bar to page down, and `q` to quit when you're done reading.

## The flag you'll use most: --oneline

The full output is verbose. Condense each commit to a single line:

```bash
git log --oneline
```

```text
9f3a1c2 Add password reset endpoint
a1b2c3d Cache user permissions on login
7e8f9a0 Fix timezone in invoice dates
```

Short hash plus subject line. This is the everyday view - it's why
[good commit subjects](/course/git-basics/everyday-git/good-commit-messages) pay off.

## Other useful git log flags

- `-n <number>` - show only the last N commits. `git log -n 5` shows the five most
  recent, and `git log -5` is the same thing with less typing.
- `--graph` - draw an ASCII diagram of the history. It looks plain now with a straight
  line, but becomes genuinely useful once you start branching in the next chapter.
- `-p` - show the actual code changes (the patch) introduced by each commit, not just
  the message.

Flags combine, which is where `git log` gets powerful:

```bash
git log --oneline -n 5
```

```text
9f3a1c2 Add password reset endpoint
a1b2c3d Cache user permissions on login
7e8f9a0 Fix timezone in invoice dates
3c4d5e6 Add invoice PDF export
6f7a8b9 Update README install steps
```

A compact, scannable summary of your recent work.

## Looking at one commit's changes

Want to see exactly what a single commit changed? Combine `-p` with `-n 1`:

```bash
git log -p -n 1
```

This prints the last commit's message followed by its diff - handy for reviewing your
own work before moving on.

## FAQ

### How do I get out of git log?

Press `q`. Git shows long output in a pager (a scrollable viewer), and `q` quits it.
Space and arrow keys scroll.

### Why is my --graph just a straight line?

Because you have one line of history so far. `--graph` shines once you create branches
and merge them, which is the next chapter. It's still valid now, just not visual yet.

### Can I see only my last few commits?

Yes - use `-n`, for example `git log --oneline -n 3` for the last three. It's the fastest
way to glance at recent work.
