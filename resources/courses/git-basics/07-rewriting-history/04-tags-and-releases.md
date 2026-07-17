---
title: "Tags and releases"
slug: tags-and-releases
seo_title: "Git Tags and Releases: Lightweight vs Annotated"
seo_description: "Mark releases with git tag: lightweight vs annotated tags, pushing tags with git push --tags, and a quick note on semantic versioning like v1.2.0."
---

A **git tag** is a permanent name pinned to a specific commit. Branches move as you
commit; tags don't. That fixed-point behavior is what makes them right for marking
releases - `v1.0.0` should point at the exact commit you shipped, and keep pointing there
forever.

## The problem tags solve

A commit hash like `a1b2c3d` is precise but meaningless to humans. When someone asks
"which commit did we release as version 1.2.0?", you don't want to dig through history.
You tag that commit `v1.2.0` once, and now the answer is a name you can check out any
time.

## Lightweight tags

The simplest tag is just a name on a commit:

```bash
git tag v1.0.0
```

That tags the commit you're currently on (HEAD). A lightweight tag stores nothing but
the name and the commit it points to - no author, no date, no message. It's fine for
quick, private bookmarks.

## Annotated tags (use these for releases)

An **annotated** tag is a full object: it records who made it, when, and a message.
Create one with `-a` and a message with `-m`:

```bash
git tag -a v1.0.0 -m "First stable release"
```

For real releases, prefer annotated tags. They carry a date and a note explaining what
the release was, which is exactly the information you'll want months later.

There's a second payoff that isn't obvious until you hit it. `git describe` names any
commit by its distance from the nearest tag, spitting out something like
`v1.2.0-5-g3d8f1a2` (five commits past `v1.2.0`). By default it only counts annotated
tags and skips lightweight ones. Tag your releases the quick way and your build scripts
suddenly can't tell one commit from another.

## Tagging an older commit

You don't have to tag the latest commit. Pass a commit hash to tag something in the
past:

```bash
git tag -a v0.9.0 a1b2c3d -m "Beta release"
```

## Listing and inspecting tags

```bash
git tag
```

That lists every tag. To see the details and the commit an annotated tag points to:

```bash
git show v1.0.0
```

## Tags are not pushed automatically

This trips up almost everyone. When you
[`git push`](/course/git-basics/remotes-and-github/pushing-changes), your tags **do not**
go with your commits. You have to push them explicitly.

Push a single tag:

```bash
git push origin v1.0.0
```

Push every tag you've created:

```bash
git push --tags
```

Once pushed, the tag appears on GitHub. GitHub also builds a **Releases** page from
tags, so a pushed tag can become a downloadable, documented release.

## A note on semantic versioning

Most projects name release tags using **semantic versioning**: `vMAJOR.MINOR.PATCH`,
for example `v1.4.2`.

```text
v   1   .   4   .   2
    |       |       |
    |       |       PATCH  backward-compatible bug fixes
    |       MINOR          new features, still backward-compatible
    MAJOR                  breaking changes
```

So `v1.4.2` to `v1.4.3` is a bug fix, `v1.4.2` to `v1.5.0` adds features safely, and
`v1.4.2` to `v2.0.0` warns users that something they depend on has changed. It's a
convention, not a Git rule, but following it makes your tags instantly meaningful.

## Deleting a tag

If you tagged the wrong commit, delete the tag locally and then on the remote:

```bash
git tag -d v1.0.0
git push origin --delete v1.0.0
```

Deleting it locally and on the remote still leaves it sitting in every teammate's clone;
`git fetch` won't remove a tag they already have. If a bad tag has spread, tell people to
run `git fetch --prune-tags`, or they'll keep seeing the version you thought you erased.

## Common mistake

Creating a tag and forgetting to push it. Your teammates and GitHub never see it,
because `git push` skips tags by default. After tagging a release, always follow up
with `git push origin <tag>` or `git push --tags`.

## FAQ

### What's the real difference between lightweight and annotated tags?

A lightweight tag is just a name pointing at a commit. An annotated tag also stores the
tagger, date, and a message as a proper Git object. Use annotated (`-a`) for anything
you'll share or release.

### Do tags move when I add new commits?

No. That's the point. A branch pointer moves forward as you commit; a tag stays pinned
to the exact commit you put it on.

### How do I check out the code at a tag?

`git switch --detach v1.0.0` (or `git checkout v1.0.0`) puts your files in the state of
that release. You'll be in
["detached HEAD"](/course/git-basics/real-world-and-troubleshooting/detached-head), which
just means you're looking at a commit directly rather than a branch.

### Does GitHub need anything special to show a release?

No extra tool required. Push an annotated tag and it shows on the repository's Releases
page; from there you can optionally add notes and attach files.
