---
name: blog-keywords
description: >-
  Keyword research for an Oatllo blog topic — find the primary keyword,
  secondary keywords, and long-tail variants with search intent, and specify
  where to place them for Google ranking. Use when preparing or writing a
  developer-focused article, or when the user asks for "słowa kluczowe",
  "keywords", "keyword research", "pod jakie frazy pisać".
---

# blog-keywords — keyword research for Oatllo

Goal: turn a topic into a concrete keyword plan the writer can execute so the
article targets Google well without keyword stuffing.

Content language: **English** by default.

## Deliverable

For the given topic, produce:

1. **Primary keyword** (1) — the main phrase the article should rank for.
   Prefer a specific, long-tail phrase with clear intent over a broad head term.
   State the likely **search intent** (informational / how-to / problem / comparison).

2. **Secondary keywords** (3–6) — closely related phrases and subtopics to cover.
   These become H2/H3 sections.

3. **Long-tail variants** (5–10) — natural question forms and specific phrasings
   ("how to …", "why does …", "… example", "… vs …", error strings). These map
   to FAQ items, sub-sections, and internal-link anchors.

4. **Semantic / LSI terms** — entities and terms Google expects to co-occur with
   the topic (libraries, functions, concepts). Covering them signals depth.

5. **Placement plan** — where each keyword goes:
   - `name` (title/H1): include the primary keyword near the front, kept readable.
   - `slug`: short, hyphenated, primary keyword.
   - `short_description` (meta description, ≤155 chars): primary + one secondary,
     written to earn the click.
   - First 100 words: use the primary keyword once, naturally.
   - H2/H3 headings: secondary + long-tail variants.
   - Image `alt` / captions: a secondary keyword where natural.

## Output format

```
Primary:      <keyword>   — intent: <...>
Secondary:    <k1>, <k2>, <k3>, ...
Long-tail:    <q1>, <q2>, ...
Semantic:     <t1>, <t2>, ...

Placement:
- Title:      <how>
- Slug:       <slug>
- Meta desc:  <draft ≤155 chars>
- Headings:   <which keyword in which H2/H3>
```

## Rules

- **Match intent first.** A keyword you can't satisfy with the article's intent
  is the wrong keyword.
- **One primary keyword per article.** Multiple primaries dilute ranking.
- **Write for humans.** Density matters far less than covering the topic
  thoroughly and naturally; never stuff keywords.
- Favor keywords a **developer** would actually search; avoid marketing jargon.

If you lack live search-volume data, reason from intent, specificity, and
competition heuristics, and say so — do not fabricate exact volumes or difficulty
scores.
