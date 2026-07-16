---
title: "Configuring Git"
slug: configuring-git
seo_title: "How to Configure Git: user.name, user.email & main Branch"
seo_description: "Configure Git after installing: set your name and email with git config, learn --global vs local config, and make main the default branch."
---

You only have to configure Git once per machine, and it takes a minute. Git needs to
know who you are so it can label your work, and there's one default worth changing
while you're at it.

## Set your Git name and email with git config

Every commit you make is stamped with a name and an email address. Set them once with
these two commands:

```bash
git config --global user.name "Ada Lovelace"
git config --global user.email "ada@example.com"
```

Let's break that down:

- `git config` is the command for reading and changing Git's settings.
- `--global` means "apply this to every project on this computer" (more on that
  below).
- `user.name` and `user.email` are the settings you're changing.

Use your real name and the email you'll use for your projects. If you plan to use
[GitHub](https://github.com) later, use the same email as your GitHub account so your
commits get linked to you.

## Global vs local config: what `--global` means

Git settings live at two levels:

- **Global** settings apply to every project for your user account. That's what
  `--global` does. Your name and email usually belong here so you don't set them
  again for each project.
- **Local** settings apply to a single project only, and they override the global
  ones. You set them by running `git config` *without* `--global` while inside that
  project's folder.

For example, if one project needs a different email:

```bash
git config user.email "work@company.com"
```

Run inside that project's folder, this overrides the global email for that project
only. You'll create your first project folder in the next lesson - for now, just know
the difference exists.

## Set the default branch to main

When Git creates a new project, it makes a first branch to hold your work. For years
the default name was `master`; the modern convention is `main`, and it's what
[GitHub](https://github.com) and this course use.

Set it once so every new project starts on `main`:

```bash
git config --global init.defaultBranch main
```

We'll explain what a branch actually is in a later chapter. For now this just makes
sure your projects use the same name everyone else does.

One thing worth knowing: this setting only affects projects you create *after*
running it. It never renames the branch in a project you already made, so set it
before you create your first repository in the next lesson and you won't have to
think about it again.

## Check your config with git config --list

To see everything Git currently has configured, run:

```bash
git config --list
```

You'll see your values listed, something like:

```text
user.name=Ada Lovelace
user.email=ada@example.com
init.defaultbranch=main
```

If your name and email are there, you're set up correctly.

## Why does my Git name only save the first word?

A quick one: forgetting the quotes around your name. Because a name has a space in it,
this fails or only saves the first word:

```bash
git config --global user.name Ada Lovelace
```

Always wrap values that contain spaces in double quotes, like `"Ada Lovelace"`.

## FAQ

### Do I have to set this up for every project?

No. Because you used `--global`, your name and email apply to every project on your
computer. You only set them again if a specific project needs different values.

### Will my email be public?

If you push commits to a public [GitHub](https://github.com) project, the email in
your commits is visible in that history. If that concerns you, GitHub offers a
privacy email address you can use instead - something to keep in mind for later.

### Can I change these settings later?

Yes, anytime. Just run the same `git config --global` command again with the new
value and it overwrites the old one.
