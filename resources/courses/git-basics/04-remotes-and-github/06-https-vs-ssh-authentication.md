---
title: "HTTPS vs SSH authentication"
slug: https-vs-ssh-authentication
seo_title: "Git HTTPS vs SSH: Which GitHub Auth to Use"
seo_description: "Git HTTPS vs SSH for GitHub: authenticate with a personal access token over HTTPS or SSH keys from ssh-keygen. See the difference and which to pick."
---

To push to a private repository - or any repository you own - GitHub has to know it's
really you. There are two ways to prove it: over **HTTPS** with a personal access token,
or over **SSH** with a key pair. This lesson explains both so you can pick one and stop
fighting authentication prompts.

## The two URL styles

The choice starts with the URL you
[clone from](/course/git-basics/remotes-and-github/cloning-a-repository). On GitHub's
green **Code** button you'll see both:

```text
HTTPS:  https://github.com/yourname/your-repo.git
SSH:    git@github.com:yourname/your-repo.git
```

The URL decides how Git authenticates. HTTPS URLs use a token; SSH URLs use a key. You
can check which one a repository uses with `git remote -v`, and switch with
`git remote set-url origin <other-url>`.

## Option 1: HTTPS with a personal access token

With an HTTPS URL, Git asks for a username and password when you push. Here's the catch
that trips up everyone: **GitHub no longer accepts your account password here**. You
need a **personal access token** (PAT) instead - a long, random string you generate that
acts as a password for Git.

To create one, go to GitHub: **Settings > Developer settings > Personal access tokens**,
generate a token, give it repository access, and copy it (you only see it once). Then
when Git prompts:

```text
Username: yourname
Password: <paste the token here, not your account password>
```

So you don't paste it on every push, install **Git Credential Manager** (it ships with
Git for Windows and is available on macOS and Linux). It stores the token securely and
fills it in for you after the first time.

**Good for:** getting started fast, machines behind strict firewalls (HTTPS traffic is
rarely blocked), and when you don't want to manage keys.

## Option 2: SSH keys

SSH uses a **key pair**: a **private key** that stays secret on your machine, and a
**public key** you give to GitHub. When you push, the two are checked against each other.
No passwords, no tokens to paste - once it's set up, it just works.

Generate a key pair:

```bash
ssh-keygen -t ed25519 -C "your_email@example.com"
```

Press Enter to accept the default location. You can set a passphrase for extra safety or
leave it empty. This creates two files, usually in a `.ssh` folder in your home
directory:

- `id_ed25519` - your **private** key. Never share this.
- `id_ed25519.pub` - your **public** key. This is the one you give to GitHub.

Copy the contents of the `.pub` file:

```bash
cat ~/.ssh/id_ed25519.pub
```

Then on GitHub go to **Settings > SSH and GPG keys > New SSH key**, and paste it in.
Test the connection:

```bash
ssh -T git@github.com
```

A greeting with your username means it works. From now on, pushing and pulling over an
SSH URL needs no username or password.

One gotcha to file away: SSH normally runs on port 22, and some corporate networks and
public wifi block it outright. If `ssh -T git@github.com` just hangs on such a network,
GitHub also serves SSH over port 443 - the same port HTTPS uses, which almost nothing
blocks. That's the escape hatch when SSH "works at home but not at the office".

**Good for:** everyday development on your own machine, when you push often and want zero
prompts.

## Which should you pick?

Both are secure and fully supported - this isn't a trick question.

- **New to Git, or on a lot of different machines?** Start with **HTTPS + token**. It's
  the least setup and works everywhere, and Credential Manager handles the token for you.
- **Working daily on your own computer?** Set up **SSH** once and enjoy never being
  prompted again.

You can switch any time by changing the remote URL, so nothing here is permanent. Pick
one, get it working, and move on.

## Common mistake

The number one authentication error is typing your **GitHub account password** at the
HTTPS password prompt. GitHub rejects it, and the message ("Support for password
authentication was removed") isn't obvious. The prompt wants a **personal access token**,
not your login password. If HTTPS keeps failing, that's almost always why.

## FAQ

### Is SSH more secure than HTTPS?

Not meaningfully for normal use - both encrypt your traffic and both are approved by
GitHub. SSH is more *convenient* once set up because it doesn't prompt you, but a token
over HTTPS is just as safe.

### Do I need to create a token or key for every repository?

No. One personal access token, or one SSH key, works for every repository on your
account. You set it up once per machine, not once per project.

### Can I use HTTPS on one machine and SSH on another?

Yes. Authentication is per machine, based on the remote URL each clone uses. Your laptop
can use SSH while a server uses an HTTPS token, all pointing at the same GitHub
repository.

### I set up SSH but Git still asks for a password. Why?

Your remote is probably still an HTTPS URL. Check with `git remote -v`; if it starts with
`https://`, switch it with
`git remote set-url origin git@github.com:yourname/your-repo.git`.
