---
name: "How to Manage Secrets and Env Variables Safely in Laravel"
slug: manage-secrets-env-variables
short_description: "A practical guide to manage secrets and env variables safely in Laravel: keep .env out of git, rotate leaks, avoid the config cache trap."
language: en
published_at: 2027-01-18 09:00:00
is_published: true
tags: [laravel, security, devops, configuration]
---

The first time I leaked a secret, it was a Stripe key. I pasted a `.env` snippet into a support ticket to show a colleague the queue config, and the live API key rode along for the trip. Nothing bad happened, but I spent an afternoon rotating keys and reading webhook logs with my stomach in a knot. That afternoon taught me more about how to manage secrets and env variables than any tutorial had. This guide is the version of that lesson I wish someone had handed me earlier.

We'll stay strictly on the defensive side: keeping credentials out of version control, handling a leak when it happens, and wiring up config so your app reads secrets correctly in production. I'll use Laravel for the concrete examples, but most of it maps cleanly onto any 12-factor app.

## Why environment variables in the first place

The idea behind [12-factor config](https://12factor.net/config) is simple: anything that changes between your laptop, staging, and production is configuration, and configuration lives in the environment, not in code. Database passwords, API tokens, mail credentials, encryption keys. None of them belong in a file you commit.

Environment variables give you that separation. The same codebase runs everywhere; only the environment differs. Laravel reads these from a `.env` file in local development and from the actual process environment in production.

That distinction matters more than people expect, and it's where most of the pain lives.

## Keep `.env` out of git, commit `.env.example`

This is the non-negotiable baseline. Your `.env` file holds real credentials and must never be tracked by git. A fresh Laravel install already gitignores it, but verify it yourself instead of trusting the default:

```bash
# Should print .env. If it prints nothing, you have a problem
git check-ignore .env
```

If that command returns nothing, `.env` is not ignored. Add it:

```bash
# .gitignore
.env
.env.backup
.env.*.local
```

What you *do* commit is `.env.example` — a template with every key present but the values blanked or filled with safe dummies. It documents which variables the app needs without exposing anything:

```env
APP_KEY=
DB_PASSWORD=
STRIPE_SECRET=
MAIL_PASSWORD=
```

New developers copy it to `.env` and fill in their own values. The example file doubles as living documentation of your config surface, which is genuinely useful when someone joins six months from now.

## The `config()` vs `env()` trap that bites everyone

Here is the single most common Laravel mistake around secrets, and I've watched senior developers hit it: **calling `env()` outside of config files.**

In development it works fine, so it slips through review. Then you deploy, run `php artisan config:cache` to speed things up, and half your integrations silently break because `env()` starts returning `null`.

Why? Config caching serializes your entire `config/` directory into one file and, from that point on, the framework never loads the `.env` file at runtime. Any `env()` call outside the config layer has nothing to read.

The rule:

- **Config files** (`config/*.php`) may call `env()`.
- **Everywhere else** (controllers, services, jobs, models): call `config()`.

So instead of this in a service class:

```php
// Breaks after config:cache: returns null in production
$key = env('STRIPE_SECRET');
```

Do this. First, read the env value once inside a config file:

```php
// config/services.php
return [
    'stripe' => [
        'secret' => env('STRIPE_SECRET'),
    ],
];
```

Then reference the config key from your code:

```php
// Safe everywhere, cache or no cache
$key = config('services.stripe.secret');
```

I treat any `env()` call outside `config/` as a bug in code review. A quick `grep -rn "env(" app/` before a release has saved me twice.

## When a secret leaks: rotate first, everything else second

Scrubbing git history feels like the fix. It is not. Once a credential has been pushed, even for a minute, even to a private repo, treat it as compromised. Forks, clones, CI logs, and caches may already hold a copy. Rewriting history does not reach any of them.

The order that actually protects you:

1. **Rotate the credential immediately.** Generate a new key at the provider and revoke the old one. This is the only step that truly closes the hole.
2. **Deploy the new value** through your secret store or environment.
3. **Then** clean the history if you want a tidy repo, but only after rotation, and understanding it's cosmetic at this point.
4. **Check the access logs** for the exposed key's activity, if the provider offers them.

For history scrubbing, `git filter-repo` is the current recommended tool (the old `filter-branch` is slow and error-prone). But say it with me: rotation is the fix, scrubbing is housekeeping.

Rotating a database password or `APP_KEY` has knock-on effects: sessions and encrypted columns tied to the old `APP_KEY` won't decrypt. Plan that rotation, don't fire it blindly on a Friday.

## Per-environment config without duplicating secrets

Local, staging, and production each need their own values. A few patterns that hold up:

- Keep a single `.env` per environment, managed *on* that environment, never shared through chat or email.
- Use `.env.testing` for the test suite so runs are deterministic and never touch real services.
- For anything beyond a hobby project, graduate from files to a real secret manager (next section).

Laravel also ships an underrated feature for teams that must keep an encrypted env in the repo.

### Laravel's built-in env encryption

Since Laravel 9.32 you can encrypt your `.env` and commit the ciphertext safely:

```bash
# Produces .env.encrypted, prints the decryption key. Store that key in your secret manager
php artisan env:encrypt
```

On the server, decrypt it during deploy:

```bash
php artisan env:decrypt --key=base64:your-key-here
```

The encrypted file is safe to commit; the *key* is the secret and must live somewhere secure. This is a decent middle ground when you can't yet run a full secret manager but want the env versioned. Don't mistake it for a complete solution. You've just moved the secret down to one key.

## Use a real secret manager when you outgrow files

Once you have multiple services, rotating credentials, and an audit requirement, flat files stop scaling. A dedicated secret manager gives you access control, rotation, and an audit trail. The common choices:

- **HashiCorp Vault**: powerful, self-hostable, supports dynamic secrets. Heavier to operate.
- **AWS Secrets Manager / GCP Secret Manager**: a native fit if you're already in that cloud, with IAM-based access.
- **Doppler**: developer-friendly, syncs secrets into your app's environment with little fuss.
- **1Password**: its CLI and service accounts work surprisingly well for smaller teams already living in it.

The pattern is the same across all of them: the secret manager holds the truth, and secrets are injected into the process environment at boot or pulled at runtime. Your app still reads `config()`; it just doesn't care where the value came from.

## CI/CD secrets: encrypted, and never echoed

Your pipeline needs credentials too, whether to deploy, run migrations, or hit a container registry. Never hardcode them in the workflow file.

GitHub Actions provides encrypted secrets. You reference them as expressions and they're injected as masked env vars:

```bash
# GitHub masks secret values in logs, but only if you don't defeat it
- name: Deploy
  env:
    DEPLOY_TOKEN: ${{ secrets.DEPLOY_TOKEN }}
  run: ./deploy.sh
```

Two rules I hold to hard:

- **Never `echo` a secret** or print it for debugging. The log mask helps, but a base64 encode or a concatenation slips right past it, and CI logs are often far more visible than you think.
- Scope tokens to the minimum they need. A deploy token doesn't need admin.

If you're building out a pipeline, I wrote up the full setup in [Laravel with GitHub Actions](/blog/laravel-github-actions), and the production container side in [Dockerizing Laravel for production](/blog/dockerize-laravel-production). Injecting config through the service container cleanly is worth understanding too; see [the Laravel service container](/blog/laravel-service-container).

## Your secrets checklist

Run through this before your next deploy:

- [ ] `git check-ignore .env` returns `.env`
- [ ] `.env.example` is committed and lists every required key
- [ ] No `env()` calls exist outside `config/` (`grep -rn "env(" app/`)
- [ ] `php artisan config:cache` runs clean in production
- [ ] Secrets in CI use encrypted store, never plaintext or `echo`
- [ ] A rotation runbook exists and the team knows rotation beats scrubbing
- [ ] Production secrets live in a manager, not a shared file
- [ ] `APP_KEY` is set and backed up (losing it means losing encrypted data)

## FAQ

### Is it safe to commit `.env` to a private repository?

No. "Private" is not a security boundary for credentials. Collaborators, forks, CI systems, and clones all retain copies, and repo visibility can change by accident. Commit `.env.example` only, and keep real values in the environment or a secret manager.

### Why does `env()` return null in production but work locally?

Because you ran `php artisan config:cache`. Caching stops Laravel from loading the `.env` file at runtime, so `env()` calls outside `config/` return null. Move the value into a config file and read it with `config()` everywhere else. This is the number one config gotcha in Laravel.

### If a secret was committed and I deleted the commit, am I safe?

No. Deleting the commit or rewriting history does not reach clones, forks, or caches that already pulled it. The only reliable fix is to rotate the credential at the provider so the leaked value stops working. Clean the history afterward if you like, but treat it as cosmetic.

### Do I need a secret manager for a small project?

Not on day one. A properly gitignored `.env` plus encrypted CI secrets is fine for a solo project or small app. Reach for Vault, AWS/GCP Secrets Manager, Doppler, or 1Password once you have multiple environments, several people, or a rotation and audit requirement.

## Wrapping up

Managing secrets well is mostly a handful of habits, not a heroic effort. Keep `.env` out of git and commit the example. Read config through `config()`, never `env()`, outside your config files — that one rule prevents a genuinely nasty class of production bug. When something leaks, rotate before you do anything else. And as the project grows, let a real secret manager carry the load.

Start with the checklist above on your current project. If even two of those boxes are unchecked right now, that's your afternoon sorted — and it beats the afternoon I spent rotating Stripe keys.