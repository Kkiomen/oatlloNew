---
name: blog-writer
description: >-
  Write a complete, expert-level blog article for Oatllo in the exact Markdown +
  YAML frontmatter format the site expects, ready to upload. Use when a topic and
  keywords are ready and the article needs drafting, or when the user asks to
  "napisz artykuł/treść", "write the post/content", "draft the article".
---

# blog-writer — expert article writing for Oatllo

Write a full article for **Oatllo** (developer blog) as a single Markdown file
with valid frontmatter, saved to `blog-drafts/{slug}.md` (create the folder if
needed). The full file format is documented in `docs/markdown-articles-api.md`
— read it if unsure.

Content language: **English** by default.

## What you are actually writing (read this first)

You are writing **what an experienced PHP/Laravel developer knows** about a
problem. The article ranks *because* it is that — not the other way round.

This is not a philosophical preference; it is what this site's own Search Console
data says. Oatllo published ~45 articles written SEO-first: keyword in the title,
keyword in the intro, brand name worked into the body, CTA in every meta
description. Combined result: **9 clicks.** The PHP course — written to actually
teach, with no SEO scaffolding at all — earns **88% of the domain's traffic**.
Filler did not just read badly; it failed at the one job it was optimized for,
and Google now refuses to index most of the domain.

So the rule is: **SEO is a constraint, never the goal.** Keywords tell you which
problem to solve and what to call it. They never decide a sentence's wording.
If a phrasing serves the keyword but not the reader, the reader wins — always.

The test before you write a section: *could a competent developer get this from
the official docs in 30 seconds?* If yes, it does not belong. Your value is the
part the docs leave out — the trade-off, the failure mode, the thing that bites
in production.

## Topic scope — refuse anything you cannot back

Write **only** within demonstrable expertise: PHP, Laravel, JavaScript/TypeScript,
architecture, databases, DevOps, tooling, AI *for developers*.

**Refuse** career, motivational, productivity, and generic-business topics
("freelancing pros and cons", "programmer burnout", "disaster recovery for
business"). Every single one of Oatllo's zero-click failures was one of these,
and the reason is structural: outside a real specialty there is nothing concrete
to say, so the text fills with platitudes by necessity. If asked for such a
topic, say plainly why it is off-strategy and propose a technical angle instead.

## Non-negotiables (these are the exact failures that damaged this site)

Each of these is quoted from a real Oatllo article. Do not reproduce the pattern.

1. **Never write "Oatllo" in the body.** A blog is not a vendor. The old text
   said: *"Oatllo, a name synonymous with digital business continuity"* — in an
   article about database backups. That is a brand mention aimed at an algorithm,
   and a reader clocks it instantly. The brand belongs in the domain, nowhere else.
2. **Never shoehorn an exact-match keyword into a sentence.** Real example:
   *"Looking at the good side of IT Freelancing Pros and Cons will give us the
   opportunity to weigh our options."* Nobody writes like that. Use the natural
   phrase a developer would say out loud, even when it does not match the title.
3. **No CTA in `short_description`.** It is a description, not an ad. Banned:
   "Click to learn more!", "Read more now!", "Start planning today!", "Discover…",
   and trailing exclamation marks. Describe what the article answers; that earns
   the click on its own.
4. **`short_description` must not restate the opening paragraph.** Duplicated
   text between meta and body reads as machine output in the SERP — it is what
   gave the whole site away.
5. **No hollow openers.** "In today's fast-paced world", "Technology is
   constantly evolving", "In the ever-evolving landscape of…". Open with the
   problem, in the reader's words.
6. **Write plain, idiomatic English.** Real failures: *"wherever your refinery
   directs you"*, *"A forceful choice in the career psyche of programming"*.
   If a sentence would not survive being read aloud, rewrite it.

## Frontmatter (required shape)

```markdown
---
name: "Article Title With Primary Keyword"
slug: article-title-with-primary-keyword
short_description: "Compelling meta description, ≤155 chars, primary keyword + benefit."
image: https://picsum.photos/seed/{slug}/1200/630
language: en
published_at: <YYYY-MM-DD HH:MM:SS>    # use today's date/time
is_published: true
tags: [primary-topic, secondary-topic, one-more]
keys_link: "distinctive phrase, another specific phrase, exact concept name"
---
```

Rules for frontmatter:
- `name` — includes the primary keyword, reads naturally, ~50–60 chars ideal.
- `slug` — lowercase, hyphenated, short, contains the primary keyword. Must be
  unique on the site (it becomes the URL and the filename).
- `short_description` — the meta description; ≤155 chars. State what the article
  answers, concretely. It earns the click by being specific, not by selling.
  No CTA, no exclamation mark, and never a paraphrase of the opening paragraph.
- `image` — a real cover URL if provided, else `https://picsum.photos/seed/{slug}/1200/630`.
- `published_at` — today's date/time (ask the environment for the current date; do
  not hardcode a stale one).
- `tags` — 2–5 lowercase tags. Free-form (they don't need to pre-exist).
- `keys_link` — 2–4 comma-separated **anchor phrases** used by the site's internal
  linking engine: when any of these phrases appears in the body of *another*
  article, it becomes a link to *this* one. Make them **specific and distinctive**
  (the exact concept/feature/term this article owns), ≥4 chars, and **not** generic
  words like `php`, `laravel`, `code`. Prefer multi-word phrases. Optional but
  recommended — it makes the article a good internal-link target.
- `category` — include **only** if that category slug already exists in the DB;
  otherwise omit it.

## Body structure

The body is Markdown (converted to HTML on the site). Write, in order:

1. **Intro (2–4 sentences).** Open with the concrete problem — ideally the moment
   you actually hit it. State what the reader will get. No fluff preamble.
   The topic will name itself in the first 100 words simply because you are
   describing it; that is sufficient. **Do not place the keyword deliberately,
   and never bold it.** See the keyword rule below — this is the one habit that
   still shows up in otherwise clean articles.
2. **`## H2` sections** covering the subtopics a reader needs. Headings describe
   what is in the section, in natural language. Prefer `##` and `###`; do not use
   `#` (the title/H1 comes from `name`).
3. **Concrete value**: real, correct **code examples** in fenced blocks with the
   language tag (```php, ```js, ```bash). Explain the code — don't just dump it.
4. **Skimmable formatting**: short paragraphs (2–4 sentences), bullet/numbered
   lists, bold for key terms. Developers scan before they read.
5. **Practical extras where relevant**: a short "common pitfalls" list, a quick
   comparison table, or a step-by-step. These win featured snippets.
6. **FAQ section** (`## FAQ`) with 2–4 real questions (long-tail keywords) and
   tight answers — strong for Google's People-Also-Ask.
7. **Conclusion** — summarize the takeaway and a next step. No generic
   "in conclusion, technology is important" filler.

## The keyword rule

Keywords belong in exactly **two** places you choose deliberately: the `name`
and the `slug`. Everywhere else, write the phrase a developer would actually
say — and let the keyword appear only where it lands naturally.

**Never bold a keyword to make it count.** An audit of this site's articles found
this exact tic surviving in otherwise excellent writing:

> "The first time you hit **sqlstate hy000 laravel** in a stack trace…"

You do not hit `sqlstate hy000 laravel`. You hit `SQLSTATE[HY000]`. The lowercase
run-on is the search query, not the thing — pasted into a sentence where the real
name belongs. Same defect, milder dose, as *"IT Freelancing Pros and Cons will
give us the opportunity"*. Write the error, the function, the concept **by its
real name and real casing**. If that means the exact phrase never appears verbatim
in the body, that is correct and costs nothing.

## Checklist (verify before finishing)

Substance:
- [ ] Every section passes the docs test: it says something the official docs don't.
- [ ] At least one honest trade-off, failure mode, or "this bit me" note — true ones only.
- [ ] All code is correct and runnable; no placeholder pseudo-code passed off as real.
- [ ] Length **1200–2200 words** of substantive content (site computes read-time
      from word count). Length is a *result* of having enough to say — never pad to reach it.

Constraints (necessary, not sufficient):
- [ ] Topic is inside the technical scope above.
- [ ] Title and slug carry the primary keyword and read naturally out loud.
- [ ] The subtopics a searcher expects are covered — because they matter, not to hit a list.
- [ ] No bolded keywords; every concept called by its real name and casing.
- [ ] `short_description`: descriptive, ≤155 chars, no CTA, not a copy of the intro.
- [ ] "Oatllo" appears nowhere in the body prose.
- [ ] Internal links only to slugs you know exist (relative, e.g. `/some-slug`);
      otherwise a plain mention rather than a broken link.

Final read: would you send this to a colleague who knows the topic — and would
they learn something? If no, the SEO boxes do not save it. This site has the
data to prove it.

## Accuracy

Never fabricate APIs, function signatures, benchmarks, versions, or quotes. If
you're unsure a detail is correct, verify it or phrase it conservatively. Wrong
technical content destroys trust and rankings.

## Output

1. Save the file to `blog-drafts/{slug}.md`.
2. Report the slug, title, word count, and the target primary keyword.

After writing, the article should go through the **blog-anti-ai** pass before
uploading with **blog-upload**.
