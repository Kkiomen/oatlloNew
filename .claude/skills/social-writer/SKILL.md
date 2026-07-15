---
name: social-writer
description: >-
  Write an Oatllo Instagram post as a committed Markdown file in
  resources/social/, in the exact frontmatter + slide format the renderer
  expects (carousel, quote, announce, story). Use when a topic is picked and the
  post file needs drafting, or when the user asks to "napisz posta na instagrama",
  "zrób karuzelę", "post na IG", "write an Instagram post", "draft a carousel".
---

# social-writer — write the Instagram post file for Oatllo

Posts live as **committed `.md` files in `resources/social/`**, exactly like
articles (`resources/articles/`) and courses (`resources/courses/`).
**There is NO database, NO cron, NO scheduler.** Git is the only writer.

Content language: **English** (`language: en`) unless the user asks otherwise.

## The file format

One file per post: `resources/social/{slug}.md`.

```yaml
---
slug: eloquent-n1-carousel      # optional; falls back to the filename
type: carousel                  # carousel | quote | announce | story   (REQUIRED)
language: en
title: "Fix the Eloquent N+1 problem"
topic: laravel                  # optional; overrides theme detection
source_type: article            # article | course | none
source: eloquent-n1-query-problem
link: https://oatllo.com/eloquent-n1-query-problem   # MUST be https
publish_at: 2026-07-20 09:00    # a NOTE TO YOURSELF - see below
status: ready                   # draft | ready | published
formats: [post, reel]           # optional; what you publish that day - see below
hashtags: [laravel, php, eloquent, backend, webdev]
caption: |
  Your Blade loop is quietly firing 200 queries.

  Swipe for the fix. Full write-up linked in bio.
---

## Your Blade loop is running 200 queries

You didn't write a loop of queries. Eloquent wrote it for you.

<!-- slide -->

## One word fixes it

```php
Post::with('author')->get(); // 2 queries
```

<!-- slide role="cta" -->

## Catch it before prod

Put `Model::preventLazyLoading()` in a service provider.
```

Only these frontmatter keys are allowed: `slug`, `type`, `language`, `title`,
`topic`, `source_type`, `source`, `link`, `publish_at`, `status`, `formats`,
`hashtags`, `caption`, `notes`. **Anything else is a lint ERROR** — a silently
ignored key is the worst possible failure mode.

## `caption` vs `notes` — what ships vs what you do

**`caption` is text that goes out. `notes` is an instruction to the human uploading.
Never put one in the other's field.**

`caption` is the only field that reaches Instagram: it becomes `caption.txt` (a
select-all-and-paste file) and renders in the review panel exactly where Instagram
draws the caption. Anything you park there, you are publishing.

`notes` is for the steps the renderer cannot perform because they are app features —
poll and question stickers, the story cluster, "reshare this in your story". It never
enters `captionWithHashtags()`; it surfaces in `post.json`, in `social:publish`
instructions, and in the review panel on amber, outside the phone mock.

**Story has no caption field on Instagram.** So for `formats: [story]`, leave
`caption` empty and write the plan in `notes` — a caption there is a lint WARNING,
because there is nowhere to paste it. This is not hypothetical: 13 story files once
carried their upload instructions in `caption`, which meant the review panel showed a
production note where the real caption goes. The empty-caption-on-ready ERROR keys on
`formats`, not `type` — a 9:16 frame with `formats: [post]` hits the feed and needs a
caption like anything else.

```yaml
type: story
status: ready
formats: [story]        # -> no caption field on Instagram; leave caption out
notes: |
  Build the cluster in the app at upload time:
  1. this frame (the rendered PNG)
  2. NATIVE POLL: "N+1" / "419"
  3. reshare of this week's carousel
```

## `type` vs `formats` — different questions

- **`type`** = the SHAPE of the slides (canvas, count, view): `carousel`, `quote`,
  `announce`, `story`.
- **`formats`** = WHAT you publish that day: `post`, `story`, `reel`, `video`.

One file often ships twice: a carousel in the feed **and** a Reel built from the
same slides (`social:video`). That is `formats: [post, reel]` — two entries on one
day in the review calendar, because they are two separate publications.

Omit it and it defaults from `type` (`story` → `[story]`, everything else →
`[post]`), so you only write it when a post ships in more than one form. An unknown
value is a lint ERROR: a typo like `reels` would vanish from the calendar without
a trace and the day would look empty. `reel` this module renders; `video` is a
label for material recorded elsewhere.

### Default a carousel to `formats: [post, reel]`

**For an account under ~500K followers, Reels get ~2.3x the reach of carousels**
(Metricool, 700M posts, per-tier medians). The Reel renders from the slides you have
already written (`php artisan social:video {slug}`), so the marginal authoring cost
is close to zero — and turning **your own** carousel into **your own** Reel is not
"reposting" under Instagram's originality policy, which targets accounts republishing
*other people's* content.

**So: unless a carousel has a specific reason not to move, give it `formats: [post, reel]`.**
Skipping the Reel is the choice that needs justifying, not the other way round.

See the `social-growth` skill for the full mix and the evidence behind it.

## Write for the SEND, not for the click

**This is the rule that decides whether the post grows the account.**

Mosseri (2025-01-22), the only real primary on ranking: the top three signals are
**watch time, likes and sends** — and **sends matter most for reaching people who do
not follow you**. Growth *is* non-follower reach. So:

> **The test is not "will this get a like?" or "will this get a click?" It is:
> would a developer send this to a specific colleague with the words
> "this is literally us"?**

That is a **writing** decision, not a design one. A post naming a shared, nameable
pain — the teammate who force-pushes, the config nobody understands, the dashboard
that takes four seconds — is forwardable. A post that merely explains a concept
correctly is not. **Both are useful; only one grows the account.**

The failure mode to avoid is the well-written blog teaser: accurate, useful, and
with no reason on earth for anyone to forward it. If the only reason the post exists
is to move someone to the blog, it will do neither well.

**Fabricating a number to make something forwardable is never the answer** — Oatllo's
credibility is the product. Real code, real symptoms, or nothing.

## HARD RULES

- **`publish_at`, `status` and `formats` PUBLISH NOTHING.** There is no scheduler.
  They are notes for a human. `status: ready` only decides what `social:export`
  picks up; `formats` only feeds the calendar at `/social/calendar`.
- **NEVER use `→`, `←`, `↔` or `⇒`.** `U+2192` and `U+2190` are **not** in the
  latin subset of our embedded woff2 — they silently fall back to a system font
  mid-line. Write `->` instead. This is a lint ERROR, not a style opinion.
- **Avoid `—` and `–`** (em/en dash). They exist in the font but clash with house
  style — `ContentSanitizer` strips them from articles. Use `-`. Lint WARNING.
- **NEVER separate slides with `---`.** The separator is `<!-- slide -->`. A `---`
  in the body is a normal `<hr>` and stays inside its slide.
- **Run `php artisan social:lint {slug}` before you are done.** Errors block the
  export; warnings mean it will render badly.

## Slides

- Slide 1 is everything before the first `<!-- slide -->` marker.
- In a slide, the first `##` heading becomes the headline; the rest is the body.
- `<!-- slide role="cta" -->` marks the last slide explicitly. Roles otherwise
  come from position: first = `hook`, last = `cta`, middle = `body`.
- `quote`, `announce` and `story` take **exactly one slide** (no markers).
- `carousel` takes **2-10 slides**. See the `social-carousel` skill for the craft.

## Character budgets (lint WARNINGs, and they matter)

Overflow is an **authoring** bug, not a render bug: CSS wraps for real, so long
text just pushes past the canvas edge and gets clipped silently.
**Shorten the text — never shrink the font.**

| What | Budget |
|---|---|
| Hook headline | 70 |
| Body/CTA headline | 55 |
| Slide body text | 180 |
| Code block | 8 lines, **46 columns** |
| Caption first line | 125 (Instagram cuts to "... more") |
| Caption body | **under ~30 words** - shorter captions win (Socialinsider, 9.1M posts) |
| Caption + hashtags | 2200 (hard Instagram limit) |
| **Hashtags** | **MAXIMUM 5. This is a hard platform cap, not a style opinion.** |

### Hashtags: 5 is the ceiling now

[@creators, 2025-12-18](https://www.threads.com/@creators/post/DSalXGPCWM4/): *"Starting today, Instagram
will allow up to **5 hashtags** in a reel or post."* The old 30 limit is gone.

Hashtags **never drove reach anyway** - Socialinsider (75M posts): *"the number of hashtags does not
influence post distribution."* They are for **search and categorisation**, nothing else. So pick 5 that
describe the post honestly and move on; there is no game to play here.

## Avoid hyphenated words in headlines

A headline renders at up to 92px, and CSS is allowed to break a line **after an
existing hyphen**. "Delete your one-line getters" comes out as
"Delete your one-" / "line getters", which looks broken. No CSS prevents this —
reword instead ("Delete the getters that do nothing"). Lint cannot catch it;
**look at the PNG**.

## The caption

The **first line is the whole game** — it is all anyone sees before "... more".
Make it a complete, concrete thought, not a teaser like "Read on!". Then a short
body (**under ~30 words**), then the ask. Hashtags go in `hashtags:`, never inline.

**Ask a question.** Metricool (24.3M posts): questions get **+36.70% more comments**;
comment-focused CTAs **+202.78%**. That is the **only** CTA intervention anyone has
measured at scale. A caption ending "Which one bit you last?" beats one ending only
"Link in bio." — and you can do both.

Know what you are buying, though: **comments are not a top-three ranking signal**
(watch time, likes and sends are). A question builds conversation and relationship;
it does not directly buy reach. Use it for that, honestly.

**⚠️ Never write "Comment WORD below and I'll DM you the link."** The DM automation
itself is a sanctioned Meta feature, but **that caption is textbook engagement bait**
under the Recommendations Guidelines — it risks the account's recommendability to
save one tap. A real question is not engagement bait; a keyword-harvesting prompt is.

**"Link in bio" is FINE — keep it.** Mosseri debunked this directly (2025-07): *"if
you say 'link in bio' it's going to decrease your reach. **That is not true**."*
Captions don't render clickable links anyway, so the myth punishes a mechanism that
does not exist.

## Style: leave it alone

The **visual style is picked automatically** by `SocialStyleResolver`. **Ten styles**
ship in the pack: `midnight` (base), `paper` (light), `spotlight` (accent fills the
canvas), `terminal` (shell window), `blueprint` (technical grid), `editorial`
(minimal, giant ghost numeral), `neon` (horizon grid + glow), `aurora` (mesh
gradient), `card` (content on a card above the accent), `brutalist` (light, thick
black frame, hard shadow).

The choice, first match wins:

1. explicit `style:` in frontmatter,
2. **code language** — a ```bash / ```dockerfile block means the post *is* a shell
   session, so it gets `terminal`,
3. **post type** — gives a **POOL** of styles (`config('social-styles.type_rotation')`),
   not one style; the pick within the pool is `crc32(slug)`,
4. **topic** — database/architecture/patterns get `blueprint`,
5. deterministic rotation by slug, so the feed stays varied.

**Step 3 is a pool, and that matters at volume.** A single style per type meant every
story got `spotlight` — a dozen identical tiles in the feed, which rotation never
touched because type decides earlier. The pools are chosen for the form: `story` gets
styles that shout, `quote` gets styles that carry emptiness, `announce` stays dark
because its logo is the hero.

**Do not set `style:` by default.** Set it only when you have a specific reason
the automatic pick is wrong. It is deterministic — the same post always renders
the same way, so a post you liked will not change under you.

See the pack and what each post got: `php artisan social:styles`.
Compare one post across the whole pack: `php artisan social:styles {slug}`.

## Picking `topic:`

The theme (logo + accent color) is matched by keyword against `title`, `slug`,
`source` and `hashtags` using `config/course-covers.php`. Matching is
**substring-based and greedy** — e.g. the `ai` theme's `ai` keyword matches
inside "av**ai**lable". **When the topic is not blindingly obvious from the
title, set `topic:` explicitly.** It wins over everything.

Existing themes: `kubernetes`, `docker`, `laravel`, `php`, `node`, `javascript`,
`python`, `database`, `git`, `devops`, `ai`.

## Types

- **`carousel`** — teaching content, 4-7 slides. **The workhorse**, and the source of
  the Reel (`formats: [post, reel]`).
- **`quote`** — one code snippet or one claim. Renders as a "code window".
- **`announce`** — promote an article or course. Set `source_type` + `source` +
  `link`; the tech logo becomes the hero.
- **`story`** — 1080x1920. Very short: a headline and one line. Instagram's UI
  covers the top ~250px and bottom ~320px, so the renderer keeps content out of
  those bands — do not fight it with long text.

### ⚠️ `quote` and `announce` ship as STATIC IMAGES — the one dying format

Single-slide posts render as single images, and images are in structural decline:
**reach −22%, engagement −46% YoY** (Metricool, 24.4M posts), the only format falling
on every measure across every source.

**Don't delete these types — stop reaching for them by default.** A `quote` that has
earned its slot is fine. A `quote` written because the calendar had a hole should have
been a carousel with a Reel. Budget **≤1 static per week**.

### ⚠️ A `story` is not a shrunk carousel

The failure mode already in the repo: twelve stories that restate a carousel in one
line and say "swipe up". That is a megaphone, and stories are the **only** surface with
native reply mechanics — polls, questions, quizzes.

**A story reply is a DM, and a DM is the relationship that makes a *send* likely
later.** Ask something answerable ("which of these two would you ship?"). Stories are
a **retention and conversation** surface, not a reach surface — Buffer excluded them
from its growth study *"due to their limited role in audience growth"*.

Also: **cluster them.** A lone frame pays the worst exit rate in the whole format
(23.8% on frame 1) and never reaches frames 6-13, where reach peaks. See `social-growth`.

## Workflow

1. Write `resources/social/{slug}.md` in the format above.
2. `php artisan social:lint {slug}` — fix every ERROR, weigh every WARNING.
3. `php artisan social:export {slug}` — see `social-export` for the mechanics.
4. **If it is a carousel with `formats: [post, reel]`: `php artisan social:video {slug}`.**
5. Tell the user the file path and that publishing is commit + manual upload.
