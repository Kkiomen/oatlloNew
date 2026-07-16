---
name: social-article-story
description: >-
  Generate and manage Instagram "new article" announcement stories for Oatllo —
  one story per queued article, dated to its publication day, in the dedicated
  announce-article style ("Słuchajcie, nowy artykuł"). Use when the user asks to
  create/refresh the new-article stories, "story o nowym artykule", "ogłoszenia
  o artykułach na story", "wygeneruj story pod artykuły", or set up the mechanism
  that announces each new blog post on stories.
---

# social-article-story — "new article" announcement stories

Announce every new blog article on Instagram Story: one story per **queued**
article (`published_at` in the future), dated to the day the article goes live,
so a regular story-viewer keeps learning "there's a new article, I can go read
it." Articles publish ~3/week, so this is a natural 3–4 extra stories per week.

This is a **generated, committed `.md`** like every other social post — no DB,
no cron. The generator never publishes anything; the stories flow through lint,
the `/social/review` panel and (optionally) auto-publish like any post.

## The mechanism

```bash
php artisan social:article-stories            # all queued articles (future-dated)
php artisan social:article-stories --dry-run  # show what would be created
php artisan social:article-stories --all      # include already-published articles
php artisan social:article-stories --force    # overwrite existing story files
php artisan social:article-stories --limit=8  # only the next N (by publish date)
```

For each queued article it writes `resources/social/story-new-{article-slug}.md`:

- `type: story`, `formats: [story]`, `style: announce-article`
- `title` / `link` taken from the article (`link` is built on `brand.domain`,
  i.e. `https://oatllo.com/...`, NOT `APP_URL` — locally that's Herd)
- `publish_at` = the article's `published_at` (same day it appears on the site)
- `status: ready` + a `verified: approved` stamp (title and link are derived
  from the article, so they are correct by construction; fingerprint is computed
  the same way as `social:verify`, so the panel shows green "zweryfikowane")
- an empty caption (stories have no caption field; the message lives on the
  graphic), and `notes:` reminding you to add a link sticker when posting

**Idempotent by design:** an existing story file is never overwritten (unless
`--force`), so manual edits survive re-runs. Correcting an article does not touch
its story — they are two separate files.

## The dedicated style

`announce-article` (`resources/views/social/styles/announce-article.blade.php`,
registered in `config/social-styles.php`) is a CSS skin over the story view. Its
recognizable, unchanging element is the **"New on the blog" banner** (a CSS
pseudo-element on `.stage`), while the accent and tech logo change per article
(via `TechThemeResolver`) so the colour tells the reader what the article is
about. The banner text uses `var(--accent-ink)` (WCAG-correct) so it stays
readable on any accent.

It is applied **only** via explicit `style: announce-article` (the generator
writes it). It is deliberately **absent** from `rotation` and `type_rotation` —
adding it there would reshuffle every other post's style (`crc32 % count`).

Change the on-graphic wording in `config('social.article_story')`:
`kicker` (the banner), `intro` (the line under the title), `slug_prefix`.

## Not to be confused with the poll "anchor frame" stories

`resources/social/` already contains a separate genre of hand-authored stories
named `story-{shorthand}` (e.g. `story-cors`, `story-enums`): a provocative
question + a native Instagram poll + a reshare of that week's carousel, built for
**engagement**. Those are NOT article announcements. The generated set uses the
distinct `story-new-` prefix precisely to keep the two genres separate and avoid
name collisions. Do not merge them.

## Workflow

1. `php artisan social:article-stories` (commit the new `.md` files — deploy = git).
2. `php artisan social:lint` — the format gate (stories must pass before export).
3. Approve them in `/social/review` (the human still gates what auto-publishes).
4. Publish path is the same as any post: `social:export {slug}` → `social:push`
   → the hourly tick, or manual export + upload (add the link sticker per `notes`).

Re-run any time after adding articles — only the missing ones get created.
