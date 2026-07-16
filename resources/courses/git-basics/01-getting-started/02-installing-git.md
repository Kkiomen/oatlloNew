---
title: "Installing Git"
slug: installing-git
seo_title: "How to Install Git on Windows, macOS and Linux"
seo_description: "Install Git in a few minutes on Windows, macOS or Linux, then confirm it worked with git --version. Beginner-friendly steps for every system."
---

Git is free, and you install it once per machine. It runs on Windows, macOS, and
Linux, and the steps differ a little on each. Pick your system below, install Git,
then confirm it worked.

## Install Git on Windows

The easiest way is the official installer.

1. Go to [git-scm.com/downloads](https://git-scm.com/downloads) and download the
   Windows version.
2. Run the installer. The default options are fine for this course - you can click
   through and accept them.

The installer also gives you a program called **Git Bash**, a terminal where all the
`git` commands in this course will work.

## Install Git on macOS

The simplest option is [Homebrew](https://brew.sh), a popular package manager for
macOS. If you have it, run:

```bash
brew install git
```

If you don't use Homebrew, you can also download the installer from
[git-scm.com/downloads](https://git-scm.com/downloads) and run it.

macOS often ships a basic Git through Apple's Xcode command line tools, so the very
first time you type a `git` command it may pop up a prompt to install those tools.
Accepting it works fine for this course. Installing through Homebrew simply gives you
a newer version that you control.

## Install Git on Linux

Use your distribution's package manager.

On Debian or Ubuntu:

```bash
sudo apt update
sudo apt install git
```

On Fedora:

```bash
sudo dnf install git
```

The `sudo` part runs the command as an administrator, which installing software
requires.

## Verify Git installed with git --version

Whatever system you're on, open a terminal (or **Git Bash** on Windows) and run:

```bash
git --version
```

You should see something like this:

```text
git version 2.45.2
```

The exact number will differ, and that's fine - any recent version works for this
course. If you see a version, Git is installed and ready.

## "git: command not found" after installing?

If you run `git --version` and get an error like `command not found` or `'git' is
not recognized`, the most common cause is that you opened the terminal *before*
installing Git, or the install hasn't updated your current window.

Close the terminal completely and open a new one, then try again. A fresh terminal
picks up the newly installed `git` command.

## FAQ

### How do I update Git later?

Re-run the installer with a newer version on Windows, or use your package manager:
`brew upgrade git` on macOS, or `sudo apt install git` again on Ubuntu. You don't
need the very latest version for this course.

### Which terminal should I use on Windows?

**Git Bash**, which comes with the Git installer. It behaves like the terminals on
macOS and Linux, so the commands in this course will match exactly what you see.

### Do I need to install anything else?

No. Git on its own is enough to complete this entire chapter. You'll only need a
[GitHub](https://github.com) account much later, when we cover sharing your work
online.
