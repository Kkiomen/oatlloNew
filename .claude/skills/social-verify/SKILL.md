---
name: social-verify
description: >-
  Verify an Oatllo Instagram post before a human reviews it: check every factual
  claim against the source article and reality, catch typos and inconsistencies,
  confirm the code actually works, then stamp the .md with the verdict so the
  approval panel shows it. Use when posts are drafted and need checking, or when
  the user asks "zweryfikuj posty", "sprawdź czy nie ma błędów", "sprawdź
  merytorycznie", "verify the posts", "check the facts".
---

# social-verify — the gate that lint cannot be

**`social:lint` checks the FORMAT. This checks whether the post is TRUE.**

A post can pass lint perfectly — 46 columns, every budget respected, no banned
glyphs — and still say Xdebug listens on port 9000, or that Anthropic ships an
embeddings endpoint. Lint sees none of it. The audience does, and they comment.

That is the whole job here: **nobody should ever be able to reply "this is wrong."**

Run this BEFORE the human opens `/social/review`. Their job is judging whether
the post is worth publishing; it is not fact-checking your work.

## The command

```bash
php artisan social:verify {slug} --verdict=approved \
  --check="what you actually checked" \
  --check="and the next thing" \
  --note="anything the reviewer should know"

php artisan social:verify {slug} --verdict=issues --note="what is wrong"
php artisan social:verify --status          # what is verified / stale / missing
```

The command **stamps only**. The checking is you, reading. It writes a `verified:`
block into the post's frontmatter with a fingerprint of the content, so the panel
can tell a live stamp from a dead one.

**Never hand-write the `verified:` block.** The fingerprint has to be computed the
same way every time or staleness detection silently stops working.

## What "verified" has to mean

Stamp `approved` only when you have checked all five. If you cannot check
something, that is `issues` — not a shrug.

### 1. Facts — the reason this skill exists

Read `resources/articles/{source}.md`. **Every claim in the post must be traceable
to the article** — a number, a default, an error string, an API behaviour.

Then go further: **the article can be wrong too.** Where a claim is checkable and
load-bearing, check it against reality (docs, the actual API, the actual default).
Being faithful to a wrong article still ships a false post.

Kill anything you cannot substantiate. A post with one fewer slide beats a post
with one invented fact.

### 2. Code

- Does it parse? Would it run?
- Does it do what the slide says it does?
- Right version? (`Kernel.php` is gone in Laravel 11; `readonly` is 8.1; fibers are 8.1)
- Are names real? `Http::preventStrayRequests()` exists, `Http::preventStray()` does not.

### 3. Typos and language

Post is English. Read it as prose, not as a diff. Watch names that look right and
are not: Sanctum/Santcum, `withPivot`/`withPivots`, PostgreSQL not Postgress.

### 4. Internal consistency

- Hook promises X, slides deliver X — not a cousin of X.
- Code matches the sentence next to it.
- The CTA slide's payoff is the thing the hook set up.
- `topic:` matches what the post is actually about (it drives the logo).

### 5. The claim that ages

`publish_at` can be months out. Flag anything tied to "latest", a version number,
or a price — those are the claims that turn false while sitting in the queue.

## What NOT to do here

- **Don't re-check the format.** Lint owns budgets, columns and glyphs.
- **Don't rewrite for taste.** If it is true and clear, it passes. Your opinion
  about the hook is not a defect.
- **Don't stamp `approved` to be done.** An unverified post is honest; a wrongly
  stamped one is worse than nothing, because it buys trust it did not earn.

## How the stamp behaves

```yaml
verified:
  verdict: approved
  at: 2026-07-16 06:47
  fingerprint: 9bad664f2aa4e19d...
  checks:
    - facts traced to the source article
    - "code: PHP 8.1 Fiber syntax, runs"
  notes: |
    AMPHP is a library, not core - matches the article.
```

**Edit the post and the stamp dies.** The fingerprint covers the content, so any
change flips the panel to *"Weryfikacja NIEAKTUALNA"* in red. That is intended:
without it, "verified" would mean "verified once, in a version nobody remembers".

**The stamp does not disturb the human's verdict.** `SocialReviewRepository::fingerprint()`
ignores the `verified:` block on purpose — the reviewer judges the content, not your
annotation about it. So verifying a post that is already approved does not send it
back to the queue.

**Order matters: verify → human reviews → publish.** Verifying after approval is
harmless but pointless; the panel exists to show your verdict *to the reviewer*.

## Where it shows up

`/social/review` renders the state above the accept/reject buttons:

| State | Panel |
|---|---|
| `approved`, fingerprint matches | green, ✓ *Zweryfikowane merytorycznie* + your checks |
| `issues` | amber, ⚠ *Zweryfikowane — z uwagami* + your note |
| fingerprint stale | red, ✗ *Weryfikacja NIEAKTUALNA — treść zmieniona po sprawdzeniu* |
| no stamp | dashed, · *Niezweryfikowane* |

## Batch work

`social:verify --status` lists everything unverified or stale. For a large queue,
work per post — reading the source article is the job, and there is no version of
it that skips the reading.

Full reasoning: **CLAUDE.md → Weryfikacja merytoryczna**.
