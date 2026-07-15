---
name: social-ideas
description: >-
  Find the best Instagram post ideas for Oatllo by mining the existing articles
  in resources/articles/ and courses in resources/courses/ for repurposable
  hooks, code snippets and announcements. Use when the user needs something to
  post, or asks "co wrzucić na instagrama", "pomysły na posty", "co z bloga
  nadaje się na IG", "Instagram post ideas", "what should I post".
---

# social-ideas — what to post on Instagram for Oatllo

Oatllo already has **101 articles** and **2 courses** written. The cheapest good
post is almost always a repurpose, not a new idea: the thinking is done, the code
is tested, and the post has somewhere to send people.

## Where to look

- **`resources/articles/*.md`** — 101 articles. Read the frontmatter (`name`,
  `short_description`, `tags`) and the opening paragraphs, which is where the
  hook usually already is.
- **`resources/courses/*/`** — courses (`course.md` + chapters + lessons). Each
  course deserves at least one `announce` and one `story`.
- **`resources/social/*.md`** — what already exists. **Do not propose a duplicate**;
  check the `source:` fields first.

## The four shapes, and what feeds them

| Type | Best source | Signal to look for | Budget |
|---|---|---|---|
| `carousel` | An article with a clear problem → fix arc | The article shows broken code then fixed code | **3-4/week — and each one also ships as a Reel** |
| `quote` | Any article with one memorable snippet | A single line that changes behaviour (`Model::preventLazyLoading()`) | **≤1/week combined with `announce`** |
| `announce` | A course, or a strong new article | A course with no `announce` post yet | *(as above)* |
| `story` | Something worth asking the audience about | A real question with two defensible answers | **3-5 frames clustered, 3-4 days/week** |

### ⚠️ Propose the format deliberately — it decides the reach

- **`carousel` is the default, and it is also the Reel.** Below ~500K followers,
  **Reels get ~2.3x the reach of carousels** (Metricool, 700M posts). `social:video`
  renders one from the same slides, so a carousel idea is really a **two-format** idea:
  propose `formats: [post, reel]`.
- **`quote` and `announce` render as single STATIC images — the one dying format**
  (reach −22%, engagement −46% YoY). Don't stop proposing them; **stop proposing them
  by default**. If the idea is only a `quote` because it's thin, it's a weak idea, not
  a quote.
- **`story` is NOT a shrunk carousel.** Twelve of those already exist in the repo and
  they are megaphones. Stories are the only surface with **native reply mechanics** —
  propose a story when you have something worth *asking*, not restating.

See `social-growth` for the full mix and the evidence.

## What makes a good repurpose

- **The article names a symptom the reader has felt.** "Page got slow with real
  data" repurposes; "Understanding architecture patterns" does not.
- **There is real code.** Instagram carousels for developers live on before/after
  code. An article with no snippet is a weak carousel.
- **One idea fits in 4-7 slides.** If the article needs 12 slides, it is a link,
  not a carousel.
- **A blog post's "why" translates. Its "how" mostly doesn't.** Anything needing
  copyable code, long reasoning chains or prerequisites belongs on the blog. Code on a
  slide is a **teaser** — nobody can copy it, so it must be obtainable elsewhere.
- **Beginner-friendly beats deep.** Instagram skews 18-24, and the largest dev accounts
  post beginner tutorials. Oatllo's **free courses** fit IG far better than its
  architecture/DevOps depth — that depth is what the blog and SEO are for.

## The ranking test: would someone SEND it?

**Sends are the signal that reaches non-followers** (Mosseri, Jan 2025) — the only
reach that grows the account. So the question that ranks ideas is not "is this a good
article?" but:

> **Would a developer send this to a specific colleague with "this is literally us"?**

An idea naming a **shared, nameable pain** (the teammate who force-pushes, the config
nobody understands) outranks one naming a topic — even when both teach the same thing.
Rank forwardable ideas above merely correct ones. **Never manufacture a relatable
symptom that isn't real** to get there.

## What to avoid

- Articles that are mostly prose or theory - no code, no carousel.
- Anything requiring a fabricated number to be interesting. **Never invent
  benchmarks or stats** to make a hook land.
- Reposting the same source twice in a row.

⚠️ **But do NOT avoid repeating a topic.** There is **no topical-fatigue mechanism** in
any Instagram statement, and topic consistency plausibly helps — it is how the system
classifies the account. **Hammer PHP, Laravel and Docker.** "We already did Docker" is
not a reason to reject an idea; "we already did *this* Docker post" is.

## Output

Give a **ranked** list. For each idea:

- proposed `slug`, `type`, **`formats`**, `topic`
- the **hook line** (this is what you are really proposing - if the hook is weak,
  the idea is weak)
- `source_type` + `source` + `link`
- one line on why it earns a swipe — **and who would send it to whom**

Rank by: **whether anyone would forward it**, strength of hook, presence of real code,
and whether it points somewhere useful. Say plainly which one you would post first and
why.

**A note on honesty when ranking:** there is **zero data** on which dev topics perform
on Instagram — roadmaps vs "X vs Y" vs mistakes vs memes is completely untested, and
no engagement benchmark exists for this niche at all. Anyone claiming otherwise
(including a confident-sounding search result) is guessing. **Rank on the reasoning
above, say it's judgment, and let Oatllo's own Insights settle it over time.**

## Workflow

1. Read `resources/social/` to see what exists (avoid duplicates).
2. Mine `resources/articles/` and `resources/courses/` for candidates.
3. Return the ranked list with hooks.
4. When the user picks one, hand off to `social-writer` (and `social-carousel`
   for the arc).
