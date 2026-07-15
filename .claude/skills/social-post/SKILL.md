---
name: social-post
description: >-
  Create a complete Oatllo Instagram post end-to-end - from picking the topic,
  through writing resources/social/{slug}.md, to exporting ready-to-upload PNGs
  and a caption. Use when the user wants a new Instagram post, carousel, story or
  announcement for Oatllo, or asks "zrób posta na instagrama", "karuzela o X",
  "post na IG", "make an Instagram carousel". This is the orchestrator; it calls
  the other social-* skills in order.
---

# social-post — the Instagram post pipeline for Oatllo

You are the **orchestrator** that produces one finished Instagram post for
**Oatllo** (https://oatllo.com), a blog for **developers** (PHP, Laravel,
JavaScript, architecture, DevOps, tooling, AI for developers).

Content language: **English**.

Mode: **autonomous up to the folder**. Run the whole pipeline without stopping
for approval — but **STOP at the folder. You do NOT publish.** There is no API
and no scheduler; a human uploads the files to Instagram by hand.

## Pipeline

Run these in order. Each phase has a companion skill — apply its guidance (read
the skill file if useful) rather than improvising.

0. **Know what you are optimising for** (`social-growth`)
   - The post's job is to earn a **send**, not a click. Sends are the signal that
     reaches non-followers, which is the only reach that grows the account.
   - `social-growth` owns the mix, the cadence and the evidence. **Read it if the
     question is strategy** (what/how often to post) rather than craft.

1. **Pick the topic** (`social-ideas`)
   - If the user gave a topic, use it. Otherwise mine `resources/articles/` and
     `resources/courses/` for the best repurpose and say which you chose and why.
   - Check `resources/social/` first so you do not duplicate an existing post.

2. **Write the file** (`social-writer`, plus `social-carousel` if `type: carousel`)
   - Produce `resources/social/{slug}.md` with valid frontmatter and slides.
   - `social-writer` owns the format; `social-carousel` owns the hook/body/CTA arc
     and the per-slide budgets.
   - **Default a carousel to `formats: [post, reel]`** — see step 5.
   - **Max 5 hashtags.** Hard platform cap since 2025-12-18.

3. **Lint** — `php artisan social:lint {slug}`
   - **Fix every ERROR.** Weigh every WARNING (they mean it renders badly).
   - Do not proceed until it is clean; `social:export` will refuse anyway.

4. **Export** (`social-export`) — `php artisan social:export {slug}`
   - Produces `storage/app/social-export/{slug}/` with `01.png..NN.png`,
     `caption.txt` and `post.json`.

5. **Render the Reel** (`social-video`) — `php artisan social:video {slug}`
   - **Do this for every carousel unless there is a reason not to.** Below ~500K
     followers **Reels get ~2.3x the reach of carousels** (Metricool, 700M posts),
     and this renders from the slides you already wrote.
   - Your own carousel → your own Reel is **not** "reposting" under Instagram's
     originality policy; that policy targets accounts republishing *other people's*
     content. No penalty.
   - Rendering takes minutes, not seconds. If the user only wants the PNGs, say the
     Reel is skipped rather than silently dropping it.

6. **LOOK at the PNGs.** Actually read the exported images before reporting.
   Lint checks budgets, not taste — clipped text, a broken watermark or an ugly
   line break only show up in the picture.

## Final report

- chosen topic and why,
- the file: `resources/social/{slug}.md`,
- the folder: `storage/app/social-export/{slug}/`, with slide count,
- **whether a Reel was rendered** (and if not, why not),
- the caption's first line (it is all anyone sees before "... more"),
- the manual upload checklist (or just tell the user to run
  `php artisan social:publish {slug}`).

## Guardrails

- **NEVER invent facts, benchmarks, numbers or quotes.** Accurate technical
  detail or nothing. This applies to **growth claims too** — Instagram advice is
  ~80% content-farm material citing itself. If a number is not in
  `social-growth/references/research.md`, do not use it.
- **NEVER write "Comment WORD below and I'll DM you the link."** The DM tool is a
  sanctioned Meta feature, but that caption is **engagement bait** under the
  Recommendations Guidelines — it risks the account's recommendability for one tap.
- **Do not commit the exported PNGs.** `storage/app/*` is gitignored; only the
  `.md` belongs in git. Publishing the post = commit the `.md` + upload by hand.
- **`publish_at` and `status` publish nothing** — no cron, no scheduler, no DB.
  They are notes for a human.
- **NEVER use `→`/`←`** — not in the font subset; use `->`. Lint catches it.
- **NEVER separate slides with `---`** — the separator is `<!-- slide -->`.
- If the export fails, **report it**. Do not claim success.
- This module has nothing to do with `App\Models\InstagramPost` (a legacy
  database-backed "follow me" tile gallery). **Never touch it.**
