---
title: "What is a remote?"
slug: what-is-a-remote
seo_title: "What Is a Git Remote? origin and GitHub Explained"
seo_description: "Understand what a Git remote is - a copy of your repo hosted on GitHub - why it's named origin, and how to list remotes with git remote -v."
---

A **remote** is a version of your repository that lives somewhere other than your
computer - usually on a hosting service like **GitHub**. Everything you've done so far
(commits, branches, merges) has happened in a repository on your own machine. A remote
gives that repository a home online.

## Why you want a remote

A local-only repository has two problems. If your laptop dies, your history dies with
it. And nobody else can see your work or contribute to it. A remote fixes both:

- **Backup.** Your commits are stored on a server, safe from a broken laptop.
- **Sharing.** Other people can get your code, and you can get theirs.
- **A central copy.** When a team works together, the remote is the shared source of
  truth everyone pushes to and pulls from.

The remote isn't magic - it's just another Git repository, sitting on a server instead
of in a folder on your desk. You send commits to it and receive commits from it.

## GitHub is a host, Git is the tool

It's worth separating two words that beginners often blur together. **Git** is the
version control tool you've been using. **GitHub** is a company that hosts Git
repositories on the internet (GitLab and Bitbucket are alternatives). Your remote lives
on GitHub, but the commands you use to talk to it are plain Git.

## Listing your remotes

A repository can have remotes configured. To see them, run:

```bash
git remote -v
```

The `-v` means "verbose". If the repository has a remote, you'll see something like
this:

```text
origin  https://github.com/yourname/your-repo.git (fetch)
origin  https://github.com/yourname/your-repo.git (push)
```

Two things to notice. First, the name **`origin`**. That's the default name Git gives
to the remote you cloned from or first added - it's a nickname for the URL so you don't
have to type the full address every time. Second, there are two lines: one for
**fetch** (downloading commits) and one for **push** (uploading commits). Usually
they're the same URL.

If you started a repository locally with `git init` and never connected it anywhere,
`git remote -v` prints nothing. That's normal - the repository just doesn't have a
remote yet. You'll add one in
[adding a remote](/course/git-basics/remotes-and-github/adding-a-remote).

A remote isn't stored anywhere mysterious, either. It's a few lines of plain text in
`.git/config` inside your project - the name, the URL, nothing more. Open that file some
time and you'll see exactly what `git remote -v` is reading back to you.

## Common mistake

Do not think of `origin` as a special keyword that means "GitHub". It's just a name,
and you could call your remote anything (`upstream`, `backup`, `github`). The reason
you see `origin` everywhere is convention: `git clone` uses it automatically, so nearly
every repository ends up with a remote called `origin`. Stick with it unless you have a
reason not to.

## FAQ

### Can a repository have more than one remote?

Yes. It's common to have `origin` (your own copy) and a second remote pointing at
someone else's copy. Each remote just needs a different name.

### Is a remote a backup?

In practice, yes - once you've pushed your commits, they're stored on the server. But
it only backs up what you've actually pushed, so commits you haven't pushed yet still
live only on your machine.

### Do I have to use GitHub?

No. GitHub is the most popular host, so this course uses it, but Git works the same way
with GitLab, Bitbucket, or a server you run yourself.
