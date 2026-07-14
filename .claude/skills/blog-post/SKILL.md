---
name: blog-post
description: >-
  Create and publish a complete, SEO-optimized blog post for Oatllo (oatllo.com)
  end-to-end and fully autonomously — from topic ideation, through keyword
  research, SEO writing, anti-AI humanization, to publishing it live by
  committing to resources/articles/ and deploying via git. Use when the user wants to create, write, or publish a new blog
  article/post for Oatllo, or asks for "a new post", "napisz artykuł na bloga",
  "opublikuj post", etc. This is the orchestrator; it calls the other blog-*
  skills in order.
---

# blog-post — autonomous blog post pipeline for Oatllo

You are the **orchestrator** that produces and publishes one finished blog post
for **Oatllo** (https://oatllo.com), a blog for **developers** (PHP, Laravel,
JavaScript, architecture, DevOps, tooling, and AI *for developers*).

Content language: **English** (`language: en`) unless the user explicitly asks
for another language.

Mode: **fully autonomous** — run the entire pipeline and publish at the end
**without stopping for approval**. Only stop if a hard blocker occurs (e.g. the
commit/deploy fails, or the live URL does not return 200 after deploy).

## Pipeline

Run these phases in order. Each phase corresponds to a companion skill — apply
that skill's guidance (read the skill file if useful) rather than improvising.

1. **Ideas → pick a topic** (`blog-ideas`)
   - If the user gave a topic, use it. Otherwise generate ideas and pick the
     single best one for Google ranking (high intent, achievable competition,
     genuinely useful to developers). Briefly state which topic you chose and why.

2. **Keyword research** (`blog-keywords`)
   - Produce a primary keyword, 3–6 secondary keywords, and long-tail variants
     with search intent. These drive the title, headings, and body.

3. **Write the article** (`blog-writer`)
   - Produce a complete Markdown file with valid frontmatter (see the format in
     `docs/markdown-articles-api.md`). Save the draft locally to
     `blog-drafts/{slug}.md` (create the folder if needed).
   - Target length: **1200–2200 words** of genuinely useful content with real
     code examples where relevant.

4. **Anti-AI humanization pass** (`blog-anti-ai`)
   - Revise the draft so it reads as authentic, human-written, experience-based
     content that satisfies Google's helpful-content / E-E-A-T guidance and is
     unlikely to be flagged as AI-generated. Rewrite the saved draft in place.

5. **Publish** (`blog-upload`)
   - Commit `resources/articles/{slug}.md` to the repo and deploy via `git pull`
     (there is no upload API). Confirm HTTP 200 on the live URL, then report it.

## Final report

After publishing, give the user a short summary:
- chosen topic + primary keyword,
- title and slug,
- published URL (verified HTTP 200 live),
- the committed file path (`resources/articles/{slug}.md`).

## Guardrails

- Never invent facts, benchmarks, or quotes. Prefer accurate, verifiable technical detail.
- Keep the cover image deterministic if none is provided:
  `https://picsum.photos/seed/{slug}/1200/630`.
- Publishing is commit + `git pull` deploy (no API/token). If the commit or
  deploy fails, stop and report instead of claiming success.
- Only set `category:` in frontmatter if that category slug already exists in the
  site's database; otherwise omit it (tags are free-form and always safe).
