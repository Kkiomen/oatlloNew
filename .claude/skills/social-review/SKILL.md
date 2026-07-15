---
name: social-review
description: >-
  Work through the human's verdicts from the Instagram approval panel
  (/social/review): take every post marked "do poprawy" in resources/social/reviews/,
  fix the post .md exactly as the reason asks, and hand it back for re-review. Use
  when the user says "przejrzałem zaplanowane posty i teraz je opracuj", "opracuj
  recenzje", "popraw posty z panelu", "I reviewed the posts, now fix them", or asks
  what is waiting in the review queue.
---

# social-review — turn the human's verdicts into fixed posts

The human reviews posts one at a time in the panel at **`/social/review`**
(dev-only, `SOCIAL_PREVIEW=true`). Every click writes one Markdown file:

```
resources/social/reviews/{slug}.md
---
slug: eloquent-n1-carousel
verdict: changes          # albo: approved
reviewed_at: 2026-07-15 14:03
fingerprint: 9f2c...      # sha1 treści pliku posta, TAK JAK JĄ WIDZIAŁ CZŁOWIEK
---

Slajd 3 ma za dużo tekstu, hook nie zatrzymuje.
```

Body of the review file = **the reason**. That is your work order.

**No database. The panel writes only into `resources/social/reviews/`; the post
`.md` is yours to edit.**

## The loop (this is the whole point)

`fingerprint` is a hash of the post file as reviewed. The queue shows a post when
it has **no review** or when its review was written **for a different version** of
the file. So:

1. Human clicks red + gives a reason → `verdict: changes`.
2. You fix `resources/social/{slug}.md`.
3. The file changed → the fingerprint no longer matches → **the post reappears in
   the panel automatically** for the human to re-judge.

You never touch the review file. You never mark anything approved. Fixing the post
is what sends it back — deleting or editing the verdict would only hide the fix
from the human.

## Steps

1. **Read the queue** — the reason is the spec, do not invent extra work:

   ```bash
   php artisan social:review --changes --json
   ```

   Empty output → say so and stop. Nothing is waiting.

   Other views when the user just asks what is pending:

   ```bash
   php artisan social:review              # table of everything
   php artisan social:review --pending    # not yet judged
   php artisan social:review --approved
   ```

2. **For each post**: read `post_path` from the JSON and the matching reason.

3. **Fix exactly what the reason says.** The reason is the human's, not a hint to
   improve around. If it says "slide 3 too long", shorten slide 3 — do not also
   rewrite the hook. If the reason is genuinely ambiguous, fix the clearest reading
   and say in your summary what you assumed.

   Apply the rules of the writing skills while fixing:
   - **`social-carousel`** — hook/body/CTA arc and the per-slide character budgets
     (hook ≤70, headline ≤55, body ≤180, code ≤8 lines / ≤46 columns).
   - **`social-writer`** — the file format (frontmatter keys, `<!-- slide -->`
     separator, caption, hashtags).
   - **Shorten the text, never shrink the font.** Overflow is an authoring bug: the
     canvas has `overflow:hidden`, so anything too long silently falls off the edge.
   - **No `→` / `←`** (U+2192/U+2190). They are missing from the woff2 latin subset
     and fall back to a system font mid-line. Lint treats them as an error.

4. **Lint every post you touched** — the exporter refuses to build on errors:

   ```bash
   php artisan social:lint {slug}
   ```

5. **Look at the result** before handing it back. Text budgets are advisory; the
   canvas is the truth:

   ```bash
   php artisan social:export {slug} --html-only    # no browser needed
   ```

   Or open `/social/{slug}/preview` if the dev server is already running.

6. **Report back, per post**: what the reason asked for, what you changed, lint
   result. Then tell the user the fixed posts are waiting again at
   `/social/review` — re-review is the human's call, not yours.

## Do not

- **Do not export or publish** off the back of a review. Publishing is manual and
  the human's decision; `social:export` runs when they ask.
- **Do not edit `resources/social/reviews/*.md`.** Those are the human's words.
  The one exception: if a post file has been deleted, its orphan review file is
  dead weight — say so and let the user decide.
- **Do not touch approved posts.** Green means done.
- **Do not change `status:` in the frontmatter** to fake progress. `status` is the
  author's note about what the exporter picks up; it is not a review verdict.
