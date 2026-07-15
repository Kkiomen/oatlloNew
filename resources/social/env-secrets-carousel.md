---
slug: env-secrets-carousel
type: carousel
language: en
title: "How to manage secrets and env variables safely"
topic: devops
source_type: article
source: manage-secrets-env-variables
link: https://oatllo.com/manage-secrets-env-variables
publish_at: 2026-07-29 19:00
status: ready
formats: [post, reel]
hashtags: [devops, security, laravel, php, backend]
caption: |
  Scrubbing the git history feels like the fix. It is housekeeping.

  Once a credential is pushed, forks, clones, CI logs and caches may already
  hold a copy. Rewriting history reaches none of them. Rotating the key is the
  only step that actually closes the hole.

  Full write-up linked in bio.

  Has .env ever hit your history, or are you about to go and check?
---

## You committed .env once. It is in the history forever.

Deleting the file does not delete the commit.

<!-- slide -->

## Rewriting history does not reach them

Forks. Clones. CI logs. Caches. Any of them may already hold the value. Your
tidy history does not travel to other people's machines.

<!-- slide -->

## So rotate first. Always first.

Generate a new key at the provider and revoke the old one. That is the only
step that truly closes the hole. Everything after it is cosmetic.

<!-- slide -->

## Then clean up, if you want a tidy repo

```bash
git filter-repo --path .env --invert-paths
```

`filter-repo`, not the old `filter-branch`. And do it knowing what it is.

<!-- slide -->

## One rotation needs a plan

`APP_KEY` is not just a string. Sessions and encrypted columns tied to the old
one stop decrypting. Do not fire that one blindly on a Friday.

<!-- slide role="cta" -->

## The habit that prevents all of it

Commit `.env.example`, never `.env`. It carries the shape of your config and
none of its values.
