---
name: blog-writer
description: >-
  Write a complete, SEO-optimized blog article for Oatllo in the exact Markdown +
  YAML frontmatter format the site expects, ready to upload. Use when a topic and
  keywords are ready and the article needs drafting, or when the user asks to
  "napisz artykuł/treść", "write the post/content", "draft the article".
---

# blog-writer — SEO article writing for Oatllo

Write a full article for **Oatllo** (developer blog) as a single Markdown file
with valid frontmatter, saved to `blog-drafts/{slug}.md` (create the folder if
needed). The full file format is documented in `docs/markdown-articles-api.md`
— read it if unsure.

Content language: **English** by default.

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
- `short_description` — this is the meta description; ≤155 chars, written to earn
  the click, primary keyword included once.
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

## Body structure (SEO best practices)

The body is Markdown (converted to HTML on the site). Write, in order:

1. **Intro (2–4 sentences).** Use the primary keyword within the first 100 words,
   naturally. State the problem and what the reader will get. No fluff preamble.
2. **`## H2` sections** mapped to secondary/long-tail keywords. Use descriptive,
   keyword-relevant headings. Prefer `##` and `###`; do not use `#` (the title/H1
   comes from `name`).
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

## SEO checklist (verify before finishing)

- Primary keyword in: title, slug, meta description, first 100 words, ≥1 H2.
- Secondary/long-tail keywords distributed across headings and body, naturally.
- Length **1200–2200 words** of substantive content (site computes read-time from word count).
- All code is correct and runnable; no placeholder pseudo-code passed off as real.
- Internal-link opportunities noted where another Oatllo post would fit (use a
  relative link like `/some-slug` if you know a real existing slug; otherwise
  leave a plain mention rather than a broken link).
- No keyword stuffing; content reads for humans first.

## Accuracy

Never fabricate APIs, function signatures, benchmarks, versions, or quotes. If
you're unsure a detail is correct, verify it or phrase it conservatively. Wrong
technical content destroys trust and rankings.

## Output

1. Save the file to `blog-drafts/{slug}.md`.
2. Report the slug, title, word count, and the target primary keyword.

After writing, the article should go through the **blog-anti-ai** pass before
uploading with **blog-upload**.
