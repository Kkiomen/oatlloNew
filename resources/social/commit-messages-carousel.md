---
slug: commit-messages-carousel
type: carousel
language: en
title: "How to write meaningful commit messages"
topic: git
source_type: article
source: good-commit-messages
link: https://oatllo.com/good-commit-messages
publish_at: 2026-07-27 19:00
status: ready
formats: [post, reel]
hashtags: [git, workflow, cleancode, productivity, devtools]
caption: |
  Send this to whoever on your team wrote "fix", "fix2" and "final fix" in the same afternoon.

  The diff already shows what changed. The message is the only place the why
  survives. Six months later, that is the only part anyone actually needs.

  Full write-up linked in bio.

  What is the worst commit message in your history, and be honest: was it yours?
verified:
  verdict: approved
  at: 2026-07-16 07:01
  fingerprint: 87461d332d5cddea1f9b0dab7d5f304bf86a6ff1
  checks:
    - the sleep(200) stale-reads why-not-what example and the fix-vs-Fix-tap-target-overlap pair are the article own
    - the if-applied-this-commit-will imperative rule and the Merge-branch/Revert precedent are correct Git conventions, not just article claims
    - semantic-release mapping verified - fix bumps patch, feat bumps minor, BREAKING CHANGE bumps major - and both slide examples are valid Conventional Commits syntax
    - the CTA do-not-rewrite-pushed-history point matches the article FAQ
  notes: |
    Nothing here is version-pinned or time-sensitive; ages indefinitely.
---

## Your git log reads: fix, fix2, final fix, actually fix

Six months later, that history is the only witness left.

<!-- slide -->

## The diff already says what. It can't say why.

Git computed the what for you. Only the message carries the why: that the weird
`sleep(200)` is there because a third-party API returns stale reads for a
fraction of a second.

<!-- slide -->

## Same commit. One of them is useless.

```bash
git commit -m "fix"

git commit -m \
  "Fix tap target overlap on mobile nav"
```

Both are one line. In six months only one still answers a question.

<!-- slide -->

## If applied, this commit will ___

Your subject has to finish that sentence. "Fix null pointer in parser" does.
"Fixed" and "Fixing" don't. Git writes its own messages that way too:
`Merge branch`, `Revert`.

<!-- slide -->

## Then your tools start reading them too

```text
feat(auth): add password reset via email
refactor(api): extract pagination helper
```

semantic-release parses this. A `fix` bumps the patch, a `feat` bumps the minor,
`BREAKING CHANGE` bumps the major.

<!-- slide role="cta" -->

## The next one costs you thirty seconds

Don't rewrite pushed history to fix old messages. That forces everyone else to
reconcile, which is a worse problem than an ugly subject line. Just write the
next one well.
