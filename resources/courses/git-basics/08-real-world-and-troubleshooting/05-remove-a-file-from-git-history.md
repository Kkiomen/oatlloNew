---
title: "Remove a file from Git history"
slug: remove-a-file-from-git-history
seo_title: "Remove a file from Git history (secrets, big files)"
seo_description: "Committed a secret or huge file? git rm --cached fixes the future; to purge it from all history use git filter-repo or BFG. Rotate the secret and force-push."
---

## The problem: remove a file from Git history

You committed something that shouldn't be in the repository - an API key, a password in
`.env`, or a huge binary that bloats every clone. To truly remove a file from Git history
you need more than a delete commit, because **the file still lives in every past commit**.
Anyone can check out an older commit and read it. It has to be gone from history, not just
from the latest snapshot.

## First decide: future only, or all of history?

**If the file is not sensitive** (just clutter or a big file going forward), you often
only need to stop tracking it from now on. That's the same fix as the
[gitignore lesson](/course/git-basics/real-world-and-troubleshooting/gitignore-not-working):

```bash
git rm --cached secret.txt
git commit -m "Stop tracking secret.txt"
```

This removes it from future commits but **leaves every past copy intact.** For a leaked
secret, that's not good enough - the secret is still readable in old commits.

## Purge from all history: git filter-repo

To erase a file from every commit in the repository's history, use `git filter-repo` (the
modern, recommended tool - install it once with `pip install git-filter-repo`):

```bash
git filter-repo --path secret.txt --invert-paths
```

`--invert-paths` means "keep everything except this path." Git rewrites every commit so
the file never existed. The BFG Repo-Cleaner is a popular alternative that's simpler for
the common cases:

```bash
bfg --delete-files secret.txt
```

**DESTRUCTIVE:** both tools rewrite your entire history - every commit after the file's
first appearance gets a new hash. Make a backup of the repository first.

Don't be surprised when `git filter-repo` drops your `origin` remote after it runs. That's
deliberate, not a bug: the rewritten history no longer shares commits with the remote, so
the tool detaches it to stop you from casually pushing a mangled history onto the wrong
repo. You re-add it with `git remote add origin <url>` once you've checked the result.

## If it was a secret, rotate it - no exceptions

Removing a secret from history does **not** make it safe. Assume it was already copied,
cloned, or cached the moment it was pushed. **Rotate the credential**: revoke the leaked
key or password and issue a new one. Cleaning history limits future exposure; rotating is
what actually protects you.

## Force-push warning

After rewriting history you have to overwrite the remote, because your local history no
longer matches it:

```bash
git push --force
```

**DESTRUCTIVE and disruptive on shared repos:** a force-push rewrites the shared history
for everyone. This is exactly the situation
[when not to rewrite history](/course/git-basics/rewriting-history/when-not-to-rewrite-history)
warns about. Tell your team first - everyone else will need to re-clone or carefully reset
their local copies, and any branch based on the old history must be rebuilt.

## FAQ

### How do I completely remove a file from Git history?

Use `git filter-repo --path <file> --invert-paths` (or BFG's `--delete-files`) to rewrite
every commit without the file, then force-push. Back up the repo first, since all hashes
change.

### I committed a secret. Is deleting it in a new commit enough?

No. Old commits still contain it, and a plain deletion leaves those intact. Purge it from
all history with `git filter-repo`, and - most importantly - rotate the secret, because it
should be considered leaked.

### Do I still need to rotate the key if I cleaned the history?

Yes, always. Once a secret has been pushed, treat it as compromised. History cleaning
reduces future exposure but can't un-leak what was already public. Revoke and replace it.

### Why does everyone need to re-clone after this?

Rewriting history changes every commit hash, so your cleaned history and their old history
have diverged. A force-push replaces the remote, and their local copies no longer match -
the safest fix is a fresh clone.
