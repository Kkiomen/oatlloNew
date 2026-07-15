---
name: social-carousel
description: >-
  Craft the content arc of an Oatllo Instagram carousel - hook, body, CTA -
  with the per-slide character budgets and code limits that keep text inside the
  1080x1350 canvas. Use when writing or improving a multi-slide carousel post, or
  when the user asks "zrób karuzelę", "popraw karuzelę", "carousel o X",
  "make the carousel better", "slajdy na instagrama".
---

# social-carousel — the craft of an Oatllo carousel

The carousel is the workhorse format for developer content: it teaches one idea
in sequence, and each swipe is a tiny commitment the reader chose to make.

This skill is about **content**. The file format lives in `social-writer`; the mix,
the cadence and the evidence behind the rules here live in **`social-growth`**.

## The arc

**4-7 slides.** Under four rarely earns the swipe.

⚠️ **Ten is NOT a data-backed wall.** Instagram's cap is **20** (raised from 10 in
2024). The famous "10 slides perform best" finding is from **August 2020, when 10 WAS
the maximum** — so "10 wins" literally meant "maxing out wins", and nobody has re-run
the curve since. Stay at 4-7 because **one idea rarely needs more**, not because a
study says so.

1. **Hook (slide 1)** — a concrete, surprising, *specific* claim. This slide is
   the **thumbnail in the feed**. It alone decides whether the other six exist.
   It gets no slide number on purpose — keep it clean.
2. **Slide 2 must stand alone as a second hook.** In the only documented version of
   Instagram's "second chance" behaviour (Mosseri, Oct 2024), a carousel nobody swiped
   is retried **starting at slide 2** — so slide 2 is an entry point, not a
   continuation. The mechanism is weakly evidenced (one sentence in one reel, never
   reconfirmed), but the advice costs nothing and survives it being retired.
3. **Body (slides 2..n-1)** — **one idea per slide.** If a slide needs "and",
   it is two slides. Show the problem, then the fix, in that order.
4. **CTA (last slide)** — `<!-- slide role="cta" -->`. Recap the payoff, then
   point at the link. One ask, not three. *(Honest note: the last-slide CTA has never
   been measured by anyone. Keep it because it is sensible, not because it is proven —
   the CTA that IS measured lives in the caption, see `social-writer`.)*

⚠️ **Ignore any "swipe-through rate" or "carousel completion" benchmark you find.**
The metric **is not exposed in Insights or the Graph API**, so nobody outside Instagram
can compute it. Every published number for it is invented.

## The carousel is also the Reel

Default a carousel to **`formats: [post, reel]`**. Below ~500K followers, **Reels get
~2.3x the reach of carousels** (Metricool, 700M posts), and `social:video` renders the
Reel from these exact slides at near-zero extra cost.

**This should shape the writing:** Remotion animates `.headline`, `.body > *` and code
blocks **per element**, so a slide built as one dense paragraph animates as one blob.
Short, separable lines move well. Write slides that read in sequence *and* play in
motion — it is the same file.

## Hooks that work for developers

- Name the **symptom they have already felt**: "Your Blade loop is running 200
  queries" beats "Understanding N+1 queries".
- Use a real number when you have one. Not an invented one — **never fabricate
  benchmarks**.
- Contradict a default assumption: "Nothing throws. Nothing breaks. It just gets
  slower."
- Avoid: "Let's dive in", "Here's everything you need to know", "A thread 🧵",
  and anything that promises value instead of delivering it.

### The hook's real job: earn a SEND, not a swipe

**Sends are the signal that reaches non-followers** — i.e. the only kind of reach that
grows the account (Mosseri, Jan 2025: the top three signals are watch time, likes and
sends, and sends matter most for unconnected reach).

> **So the hook test is: would a developer send this slide to a specific colleague
> with "this is literally us"?**

That favours hooks naming a **shared, nameable pain** over hooks naming a topic:

- "Your Blade loop is running 200 queries" — correct, useful, **not** forwardable.
- "The dashboard takes 4 seconds and nobody knows why" — same lesson, and it has a
  recipient.

A hook can be sharp and still be unsendable. Both are worth writing; only one grows
the account. Prefer the one with a recipient when the content allows it honestly —
**and never manufacture a relatable symptom that isn't real.**

## Budgets (enforced by `social:lint`)

| Slide part | Budget | Why |
|---|---|---|
| Hook headline | **70 chars** | Renders at the largest type scale |
| Body headline | **55 chars** | |
| Body text | **180 chars** | One idea per slide |
| Code | **8 lines, 46 columns** | 46 is measured, not guessed: at 30px the mono font fits ~50 columns in a 1080px canvas |

**Overflow is silent.** The canvas has `overflow: hidden`, so text past the edge
just disappears. **Shorten the copy — never shrink the font.**

## Code on slides

- **8 lines maximum, 46 columns maximum.** Wrap long calls across lines:

  ```php
  Model::preventLazyLoading(
      ! app()->isProduction()
  );
  ```
- Cut everything that is not the point: no namespaces, no boilerplate, no
  `<?php`. A carousel slide is not a file.
- One comment at most, and only if it carries the punchline (`// +1 query each`).
- Real, runnable code. Never invented APIs.

## Before/after in two slides

The strongest carousel shape for developers:

- slide 2: the broken code, with the cost made visible ("101 queries")
- slide 3: the fix, one line, with the new cost ("2 queries")

The reader does the comparison themselves, which is what makes it stick.

## Code on slides is a TEASER, permanently

Screen readers cannot read a code image and **nobody can copy it**. That is inherent
to Instagram, not a bug to fix — devs
[complain about it](https://news.ycombinator.com/item?id=33381119) and they are right.

> **Code the viewer NEEDS is the failure mode.** Every code slide must have a
> destination where the code is actually obtainable (the article, the course).

The ≤8 lines / ≤46 columns budget has **no external validation** — but 46 was
*computed* for this canvas, which puts it ahead of every number published on the
internet. **Trust the house number.**

## Checklist

- [ ] Hook is a symptom, not a topic label
- [ ] **Someone would send this to a named colleague** (or you know why they wouldn't)
- [ ] **Slide 2 stands alone as a second hook**
- [ ] One idea per slide
- [ ] Problem before fix
- [ ] Every code block within 8 lines / 46 columns, and obtainable elsewhere
- [ ] Exactly one ask on the CTA slide
- [ ] **Caption asks a question** (+36.70% comments, the only measured CTA)
- [ ] **Max 5 hashtags** (hard platform cap since 2025-12-18)
- [ ] **`formats: [post, reel]`** unless there is a reason not to
- [ ] `php artisan social:lint {slug}` clean
- [ ] Looked at the actual PNGs (`social:export`), not just the markdown
