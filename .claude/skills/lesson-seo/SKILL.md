---
name: lesson-seo
description: >-
  Optimize a single Oatllo course lesson (.md in resources/courses/) for Google
  SEO without hurting teaching quality. Runs per lesson file and does three
  things: (1) find the best SEO keywords for that lesson, (2) rewrite the lesson
  in place to rank well for them, (3) verify the SEO changes did not damage the
  lesson's clarity, beginner-friendliness or "never get ahead of the material"
  ordering. Use when the user asks to "zoptymalizuj lekcje pod SEO", "popraw
  SEO lekcji", "SEO kursu", "optimize lessons for SEO", or wants a course's
  lessons to position better in Google.
---

# lesson-seo — SEO optimization pass for course lessons

Oatllo courses are Markdown files under `resources/courses/{course}/{NN-chapter}/{NN-lesson}.md`
(see the `course-writer` skill for the full format). This skill improves **one lesson
file at a time** for search ranking, **rewrites it in place**, and then checks that the
lesson is still excellent for the human reading it.

Content language: **English** (matches the public site).

## Golden rule

SEO serves the reader, never the other way around. A lesson that ranks but confuses a
beginner is a failure. **Never** trade clarity, correctness, or teaching order for a
keyword. If a "good SEO move" would hurt the lesson, don't make it.

## How it runs (per file)

Given a lesson file path (or a whole course/chapter), process **each lesson file
independently**, in course order. For each file, run the three steps below and edit
that file in place. When given a directory, iterate over every `NN-*.md` lesson file
(skip `_chapter.md` and `course.md` unless asked), in numeric order, so earlier lessons
are optimized before later ones.

Always read the file first. Also skim the **course.md**, the chapter's **_chapter.md**,
and at least the **titles of earlier lessons** in the same course, so you know:
- the course's overall keyword theme (e.g. "Docker", "Laravel"),
- what concepts have already been introduced (for the ordering rule),
- what the neighbouring lessons target (to avoid two lessons competing for the same
  keyword — see keyword cannibalization below).

## Step 1 — Find the best SEO keywords

For this specific lesson, determine:

1. **Primary keyword** (1) — the main phrase this single lesson should rank for.
   Prefer a specific, long-tail, intent-clear phrase tied to the lesson's one topic
   (e.g. `docker volumes` → better: `docker volumes persist data`, or
   `cmd vs entrypoint dockerfile`). One lesson = one primary keyword.
   State the **search intent** (informational / how-to / problem / comparison).
2. **Secondary keywords** (2–5) — closely related phrases the lesson naturally covers.
   These map to `##` / `###` headings.
3. **Long-tail variants** (3–8) — natural question / phrasing forms
   ("how to …", "why does …", "… example", "… vs …", exact error strings). These map
   to sub-sections, the meta description, and internal-link anchors.
4. **Semantic terms** — entities Google expects near this topic (commands, flags, file
   names, related concepts). Covering them signals depth.

Constraints:
- **Match the lesson's real content and intent.** Don't pick a keyword the lesson
  doesn't (and shouldn't, per the ordering rule) satisfy.
- **Avoid cannibalization.** Two lessons in the same course must not target the same
  primary keyword — give each a distinct angle. If you find overlap, differentiate.
- Favor phrases a **developer would actually type** into Google; no marketing jargon.
- Without live volume data, reason from intent, specificity and competition — say so;
  never fabricate exact volumes or difficulty scores.

## Step 2 — Optimize the lesson for SEO (rewrite in place)

Apply, only where it also helps the reader:

**Frontmatter** (edit these keys):
- `seo_title` — include the primary keyword near the front, kept readable (~50–60
  chars). This is the `<title>`/search headline.
- `seo_description` — **≤155 chars**, primary keyword + one secondary, written to earn
  the click (a benefit + what they'll learn). One clean sentence, no keyword stuffing.
- `title` — the on-page H1. Keep it clear and human; fold the primary keyword in only
  if it stays natural. Prefer clarity over a forced keyword.
- **`slug` — DO NOT CHANGE.** Slugs are live URLs; changing one breaks links and SEO.
  If a slug is genuinely poor, flag it for the user instead of editing it.

**Body** (Markdown, sections start at `##`) — always make at least light edits here,
not just frontmatter:
- **First ~100 words:** use the primary keyword once, naturally, ideally in the first
  paragraph or first `##` heading.
- **Headings (required):** revise the `##`/`###` headings so they are descriptive and
  carry the secondary / long-tail keywords - this is one of the strongest on-page SEO
  signals, so every lesson should get at least one or two heading tweaks. A generic
  heading like "The idea" or "An example" wastes an SEO opportunity; turn it into
  something a searcher would type, e.g. "What a Docker volume is" or "docker run
  example: mapping a port". Question-form headings that match a real search are ideal.
  Keep them short and honest - the section must actually deliver what the heading says.
- **Depth & coverage:** ensure the lesson actually covers the secondary/semantic terms
  a searcher expects — add a short, genuinely useful paragraph or example if a real gap
  exists (never padding).
- **Scannability (helps SEO + reading):** short paragraphs, a tight bullet list where it
  fits, a small runnable code block tagged with its language.
- **Internal linking anchors:** phrase mentions of other topics so they read as natural
  anchor text (the site links internally at render time).
- **Answer the query early:** put the direct answer high up (good for featured snippets
  and for impatient readers).
- **Meta-honest:** the `seo_title`/`seo_description` promise must be delivered by the
  body — no clickbait mismatch.

**Never do:**
- Keyword stuffing, repeating the primary keyword unnaturally, or the same idea twice.
- Hidden text, homoglyphs, invisible characters, or any detector/ranking trick.
- Changing the slug, or renaming the file.
- Introducing a concept, command, flag or term **not yet taught in an earlier lesson or
  earlier in this one** (the `course-writer` ordering rule is absolute — SEO does not
  override it). If a valuable keyword needs a not-yet-taught concept, don't force it.

## Step 2b — Add experience and an FAQ (helpful-content / E-E-A-T)

Good rankings and good on-page tags are not enough. Google's **helpful content**
system rewards original, first-hand, genuinely useful pages over generic textbook
prose that reads like every other tutorial. Two additions do the most here, and every
lesson should get **both** (kept short - this is a beginner course, not a blog post).

**1. A first-hand / practical note (1-2 per lesson).** Weave in a real, true-in-practice
insight the reader won't get from a bare definition:
- a **common mistake** and how to avoid it ("A frequent mistake is ... - do X instead"),
- a **gotcha** ("The catch is ...", "This trips people up because ..."),
- a **when-to-use trade-off** or a "in a real project you'll ..." note.
Rules: it must be **true and non-obvious**, phrased as practical guidance. Do **not**
fabricate specific personal anecdotes, fake numbers, benchmarks, or invented war
stories - that's the opposite of trustworthy (the T in E-E-A-T). Honest, concrete
practitioner guidance only. Respect the ordering rule: the note may only lean on
concepts already taught.

**2. A short FAQ block at the end (2-4 Q&A).** Target the real questions people also
ask about this exact topic - the long-tail queries the lesson can honestly answer.
Format so each question is a searchable heading:

```markdown
## FAQ

### <A real question a beginner would type, e.g. "Do I need to stop a container before removing it?">

A short, direct answer (1-3 sentences). Answerable from what this lesson taught; if it
gently points forward, say "you'll see this in a later lesson" without relying on it.
```

Rules for the FAQ:
- Pick questions from genuine search intent / "People Also Ask" style, not filler.
- Answer **directly and briefly** - the first sentence should stand alone (good for
  featured snippets).
- Questions become `###` headings, so they double as long-tail keyword targets.
- Keep answers within the lesson's scope and ordering; never introduce a not-yet-taught
  concept as if known.
- Do not repeat a Q&A that another lesson already owns (avoid FAQ cannibalization).

**Schema note - already wired up.** The lesson view emits real **`FAQPage` JSON-LD**
structured data automatically. `App\Services\Course\LessonFaqExtractor` parses the
rendered lesson HTML, finds the `## FAQ` section, and pulls each `### question` + its
answer; `HomeController::courseLessonEn/Pl` pass the result to the view, which outputs
the schema when the list is non-empty. So **writing the FAQ in Markdown is enough** -
you do not touch any Blade or hand-write JSON-LD. To make sure it's picked up:
- Use a top-level `## FAQ` heading (the extractor keys off an `<h2>` whose text starts
  with "FAQ") and put each question in a `### ` heading with the answer in the text below.
- The FAQ section ends at the next `## ` heading, so keep the FAQ last (or ensure the
  section that follows is a real `##`).
- Never hand-write JSON-LD into the Markdown body - the view already produces it.

## Step 3 — Verify quality was not damaged

This is the point of the skill: SEO must not degrade the lesson. After rewriting, read
the whole lesson top-to-bottom as a beginner and confirm **every** box:

- [ ] **Still beginner-friendly:** plain language, short sentences, one idea at a time;
      any new technical term is defined the first time it appears.
- [ ] **Ordering intact:** nothing is used before it's introduced (checked against
      earlier lessons + earlier in this file). No forward references relied upon.
- [ ] **Correctness:** all commands, code and explanations are still accurate; code
      blocks are language-tagged and runnable.
- [ ] **Flow preserved:** the lesson still reads naturally; SEO edits didn't make it
      choppy, repetitive, or stuffed. Revised headings still match their section's
      content exactly (no heading promising more than the section delivers).
- [ ] **Real added value:** any content added for coverage genuinely helps the reader
      (no filler to hit a keyword).
- [ ] **Scope unchanged:** the lesson still teaches its one focused topic; it didn't
      bloat into the next lesson's material.
- [ ] **Promise kept:** `seo_title` / `seo_description` accurately describe the body.
- [ ] **Style conventions:** plain hyphens `-` (no em dashes), sections start at `##`
      (H1 is the title), no raw HTML in the body.
- [ ] **Experience is real, not faked:** the practical/gotcha notes are true and
      non-obvious; no invented anecdotes, numbers, or benchmarks.
- [ ] **FAQ earns its place:** 2-4 genuine questions, each answered directly and within
      the lesson's scope and ordering; no filler, no cannibalizing another lesson's FAQ.

If any box fails, revise the lesson until it passes. Quality wins ties.

## Output / report (per file)

After processing each lesson, give a short report:

```
File: <path>
Primary:   <keyword>  — intent: <...>
Secondary: <k1>, <k2>, ...
Long-tail: <q1>, <q2>, ...

Changes:
- seo_title:       <old> -> <new>
- seo_description: <old> -> <new>
- title:           <kept / new>
- body:            <bullet list of the edits made and why>

Quality check: PASS (all boxes) — <one line on how quality was preserved/improved>
Flags: <slug concerns, cannibalization with lesson X, or "none">
```

When run over a whole course, end with a one-paragraph summary: which lessons changed,
any cross-lesson keyword overlaps you resolved, and anything the user should decide
(e.g. a weak slug you deliberately left untouched).

## Deploy note

Lessons render straight from the `.md` files - no artisan command and no upload. After
editing, the user just **commits and deploys** (deploy = `git pull`). Only `.md` files
change here, so no CSS rebuild is needed.
