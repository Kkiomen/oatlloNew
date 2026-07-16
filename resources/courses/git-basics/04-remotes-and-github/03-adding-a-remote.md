---
title: "Adding a remote"
slug: adding-a-remote
seo_title: "git remote add origin: Connect a Local Repo to GitHub"
seo_description: "Started a repo with git init? Create an empty GitHub repo and connect it with git remote add origin so your local commits have somewhere to push."
---

Cloning is for projects that already live on GitHub. Plenty of projects start the other
way around: you run `git init` locally, make some commits, and *then* decide to put the
project online. That's when you reach for `git remote add origin` - the command that
connects your existing local repository to a remote on GitHub yourself.

## Step 1: create an empty repository on GitHub

Go to GitHub and create a new repository. The important part: leave it **empty**. Do
not tick "Add a README", "Add .gitignore", or "Choose a license". You already have
commits locally, and an empty remote is the cleanest thing to connect them to.

After creating it, GitHub shows you the repository URL, something like:

```text
https://github.com/yourname/your-project.git
```

## Step 2: add the remote

In your local project folder, tell Git about that URL and give it the name `origin`:

```bash
git remote add origin https://github.com/yourname/your-project.git
```

Read it as: "add a remote, name it `origin`, and point it at this URL". Nothing is
uploaded yet - you've only recorded where the remote *is*. Git doesn't even contact the
server at this point, which is why a typo in the URL stays silent until your first push
fails. Confirm the line looks right now:

```bash
git remote -v
```

```text
origin  https://github.com/yourname/your-project.git (fetch)
origin  https://github.com/yourname/your-project.git (push)
```

Now your local repository knows about GitHub. The next lesson,
[pushing changes](/course/git-basics/remotes-and-github/pushing-changes), sends your
commits up for the first time.

## Fixing the URL if you got it wrong

If you pasted the wrong URL, you don't have to remove and re-add. Change it in place:

```bash
git remote set-url origin https://github.com/yourname/correct-repo.git
```

And to remove a remote entirely:

```bash
git remote remove origin
```

## Common mistake

The most common snag is creating the GitHub repository **with** a README or `.gitignore`
already in it. That gives the remote a commit your local repository doesn't have, and
your first push gets rejected because the two histories have diverged. Keep the new
GitHub repository empty when you're connecting an existing local project, and this
problem never appears.

## FAQ

### Why is the remote called origin and not something else?

Convention. `origin` is the default name Git uses when cloning, so people use it when
adding remotes by hand too. You *can* pick another name, but `origin` is what everyone
expects.

### I ran git remote add but nothing uploaded. Is that normal?

Yes. Adding a remote only saves the URL under a name. Uploading happens later with
`git push` - that's the next lesson.

### Can I connect the same local repo to two GitHub repositories?

Yes, by adding a second remote with a different name, for example
`git remote add backup <url>`. Each name points at its own URL.
