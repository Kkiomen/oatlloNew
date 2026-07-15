---
name: social-video
description: >-
  Turn an existing Oatllo Instagram post (resources/social/*.md) into a Reel —
  an animated MP4 1080x1920 rendered with Remotion from the same slides that go
  to PNG. Use when the user wants a short video / Reel for Instagram, or asks
  "zrób reela", "wideo z tego posta", "krótki filmik na IG", "animuj karuzelę",
  "make a reel", "video version of the post".
---

# social-video — the post, in motion

A Reel is **not a new kind of content**. It is an existing post `.md` rendered as
video. Remotion gets **exactly the HTML documents that go to PNG** and adds
**motion only**. There is no second design, no `.tsx` copy of the slides.

If there is no post yet, this is the wrong skill — write the post first
(`social-writer` / `social-post`), then come back. A Reel of a bad carousel is a
bad Reel.

## Commands

```bash
php artisan social:video eloquent-n1-carousel              # -> reel.mp4
php artisan social:video {slug} --stage-only               # build input, no render
php artisan social:video {slug} --out=./tmp/reel.mp4
php artisan social:video {slug} --skip-lint                # debugging ONLY

cd social-video && npm i                                   # first run only
cd social-video && npx remotion studio                     # live preview
```

Output: `storage/app/social-export/{slug}/reel.mp4` (gitignored, like the PNGs).
The caption is the post's own — `social:export` writes `caption.txt` next to it.

Lint is a **gate**, same as in export: text that overflows the canvas does not
throw, it just leaves the frame — and in video you would only find out after
minutes of rendering.

## Music: added in Instagram, never rendered in

**The MP4 is silent by design.** Music goes on when you upload, from Instagram's
own audio library — do not ask for it to be baked into the render.

Instagram's library is licensed for in-app use, so there is nothing to attribute
and nothing to get muted. More importantly, **audio is a discovery surface**: a
track picked in-app makes the Reel reachable from that track's page, and trending
audio is a distribution channel a burned-in file cannot touch. An MP3 muxed into
the render buys none of that and risks an audio-match claim.

It also stays out of the repo. Slides and PNGs are gitignored because they are
**derivable from the `.md`** — an audio file is not, so it would have to be
committed as binary or silently break renders on a fresh clone.

Burning audio in is only worth reopening if the same file must ship to YouTube
Shorts / TikTok, where there is no in-app licensed library to draw on. That is a
real fork, not a config flag: it needs an `<Audio>` in `Reel.tsx`, a `music` field
staged by `ReelStager` (music is content, so PHP picks it), and a licence the blog
can actually use commercially — "no copyright" on Pixabay or the YouTube Audio
Library usually means a specific licence with conditions, not public domain.

## Which posts make good Reels

| Type       | Works as a Reel because                                              |
|------------|---------------------------------------------------------------------|
| `carousel` | Native fit — slides already are a sequence. Best default.            |
| `quote`    | One idea, 5–8s. Cheapest thing to ship.                              |
| `announce` | Course launches; the tech logo carries the motion.                   |
| `story`    | Already 1080x1920 → renders full-bleed, no letterbox.                |

## Preview loop

Rendering the whole Reel to check a change is slow. Use Studio, or a single frame:

```bash
php artisan social:video {slug} --stage-only
cd social-video
npx remotion still Reel out.png --frame=200 --scale=0.5 \
  --props='{"slug":"{slug}","manifest":null,"html":[]}'
```

`--frame` is zero-based and absolute across the whole Reel, not per slide. Slide
boundaries are in `social-video/public/slides/{slug}/reel.json`.

## Where things live

| Concern                            | Where                                        |
|------------------------------------|----------------------------------------------|
| Slide duration, accent, slide count| `ReelStager` (PHP) → `reel.json`             |
| Motion only                        | `social-video/src/Slide.tsx`                 |
| Framing, backdrop, progress bar    | `social-video/src/Reel.tsx`                  |
| Timing knobs                       | `config('social.video.timing')`              |

**PHP owns content, Remotion owns motion.** Do not move a content decision into
`.tsx` — `social:lint` and the PHP tests cannot see it there.

## Rules that bite

- **Slide duration comes from content volume**, not a constant. Tune it in
  `config/social.php` (`social.video.timing`), never per post in TSX.
- **Never repurpose `.bar` for the progress bar.** Skins own it and outrank you
  (`.canvas.style-x .bar` = 0,3,0); `card` hides it entirely. The progress bar is
  drawn by Remotion in the letterbox band.
- **Scope any new CSS rule with `.reel-slide-{i}`.** The injected `<style>` is
  global; an unscoped rule hits every mounted slide.
- **Fix overflow by shortening text, never by shrinking the font** — same rule as
  the PNGs. The canvas is the canvas.
- Editing the post `.md` invalidates its review verdict (fingerprint) — that is
  intended, and it applies to Reels too.

Full reasoning, the three mines, and the licensing note: **CLAUDE.md → Reels / wideo**.
