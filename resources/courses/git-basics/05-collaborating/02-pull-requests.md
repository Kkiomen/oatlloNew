---
title: "Pull requests"
slug: pull-requests
seo_title: "GitHub Pull Request: Open, Review and Merge It"
seo_description: "What a GitHub pull request is and how to open one, get it reviewed and merged - and how a PR maps to a plain git merge under the hood."
---

## What a pull request actually is

A **GitHub pull request** (PR) is a GitHub feature, not a Git command. It's a request that says:
"please merge my branch into `main`." It gives your team a place to look at the change,
discuss it, and approve it *before* it becomes part of the main codebase.

Under the hood, a merged PR is just a
[git merge](/course/git-basics/branching-and-merging/merging-branches). GitHub runs the
same merge you'd run locally - the PR is the review-and-discuss layer wrapped around it.

Other Git hosts call it the same idea by a different name (GitLab and others say "merge
request"). Same concept.

## Opening a pull request

First, push your feature branch to GitHub (from the
[previous lesson](/course/git-basics/collaborating/the-feature-branch-workflow)):

```bash
git push -u origin add-login-page
```

Then, in the browser:

1. Go to the repository on GitHub. It usually shows a banner offering to open a PR from
   your freshly pushed branch - click **Compare & pull request**.
2. Check the two branches at the top: the **base** (where you're merging *into*, usually
   `main`) and the **compare** (your branch).
3. Give it a clear title and a description of what changed and why.
4. Click **Create pull request**.

That's it. There's no special Git command - you pushed a branch, and the rest happens on
the website.

## Review and merge

Once the PR is open, GitHub shows every commit and a line-by-line diff of your changes.
Teammates can comment, ask questions, or request changes. If you need to fix something,
you just commit to the same branch and push again - the PR updates automatically.

When it's approved, someone clicks **Merge pull request**. GitHub merges your branch into
`main`, and the change is now live in the main codebase. You then update your local copy:

```bash
git switch main
git pull origin main
```

Watch the merge box on the PR page. GitHub only shows a green "able to merge" state when
your branch still merges cleanly into `main`. If someone else's PR lands first and touches
the same lines, that box turns into a conflict warning - GitHub is telling you the same
thing a local [merge](/course/git-basics/branching-and-merging/merging-branches) would, just
in the browser.

## A common mistake

**Treating a PR as fire-and-forget.** Opening the PR isn't the finish line. Reviewers may
leave comments, and CI checks may fail. Keep an eye on it, respond to feedback, and push
fixes to the same branch until it's approved and merged.

## FAQ

### Can I review my own pull request?

You can read through your own diff (and you should - it catches mistakes), but the value
of a PR is a *second* pair of eyes. On solo projects some people still open PRs just for
the clean diff view and history.

### What's the difference between a pull request and git pull?

Nothing, despite the similar names. `git pull` downloads and merges commits from a remote
into your local branch. A pull request is a GitHub web feature for merging one branch into
another with review. Different things entirely.

### What happens to my branch after the PR is merged?

Nothing automatic locally. GitHub offers a "Delete branch" button to remove it on the
remote. Locally you delete it yourself with `git branch -d add-login-page` once it's
merged.
