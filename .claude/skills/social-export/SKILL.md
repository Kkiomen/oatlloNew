---
name: social-export
description: >-
  Render Oatllo Instagram posts from resources/social/*.md into ready-to-upload
  PNGs plus a caption file, and troubleshoot the rendering (missing browser,
  wrong PNG size, wrong font, clipped text). Use when a post file is ready and
  the graphics need generating, or when the user asks "wygeneruj grafiki",
  "wyeksportuj posta", "zrób pngi", "export the post", "render the carousel".
---

# social-export — turn post files into uploadable graphics

The pipeline is: **`.md` file → HTML (embedded font) → headless browser → PNG**.
Everything runs locally. There is no API, no upload, no server.

## Commands

```bash
php artisan social:list                      # what's in the queue
php artisan social:list --status=ready

php artisan social:lint --all                # validate; exit != 0 on errors
php artisan social:lint eloquent-n1-carousel

php artisan social:export eloquent-n1-carousel        # -> PNGs
php artisan social:export --all                       # everything status: ready
php artisan social:export --all --status=draft
php artisan social:export {slug} --html-only          # no browser; .html to eyeball
php artisan social:export {slug} --out=./tmp

php artisan social:publish {slug}            # export + manual upload checklist
php artisan social:publish {slug} --dry-run

php artisan social:styles                    # the style pack + what each post got
php artisan social:styles {slug}             # one post rendered in ALL six styles
php artisan social:styles {slug} --html-only
```

## The style pack

**Ten styles ship:** `midnight` (base), `paper` (light), `spotlight` (accent fills the
canvas), `terminal` (shell window), `blueprint` (technical grid), `editorial`
(minimal, giant ghost numeral), `neon` (horizon grid + glow), `aurora` (mesh
gradient), `card` (content on a card above the accent), `brutalist` (light, thick
black frame, hard shadow). A style is a **CSS skin** in
`resources/views/social/styles/`, not a separate view — **one layout, ten skins, four
post types.** That is the whole point: separate views would mean 10 x 4 = 40 of them.

The style is **picked automatically** and deterministically (see `social-writer`).
`php artisan social:styles` shows the pack and what each post got.
`php artisan social:styles {slug}` renders one post in every style so you can
compare them on real content instead of imagining it.

## What lands in the folder

`storage/app/social-export/{slug}/`:

- **`01.png` .. `NN.png`** — slides. **Filename order IS the Instagram order.**
- **`caption.txt`** — caption + hashtags, ready to paste.
- **`post.json`** — manifest (a future publisher reads this).

**These files are gitignored (`storage/app/*`) and must NEVER be committed.**
Only the `.md` source belongs in git.

## Lint is a gate

`social:export` refuses to build a post with lint ERRORs. That is deliberate: a
graphic with over-long text **builds without complaint** and simply runs past the
canvas edge — you would only find out on Instagram. `--skip-lint` exists for
debugging, never for publishing.

## Manual upload checklist

1. Open `storage/app/social-export/{slug}/`.
2. Add the slides **in filename order** (`01.png`, `02.png`, ...).
3. Paste the text from `caption.txt`.
4. Point link-in-bio at the post's `link:`.
5. Set `status: published` in `resources/social/{slug}.md` and commit.

`php artisan social:publish {slug}` prints this list filled in for the post.

## Troubleshooting

**"Nie znaleziono przeglądarki"** — set `SOCIAL_BROWSER_BINARY` in `.env` to your
`msedge.exe` / `chrome.exe`. Candidates are in `config/social.php`. Or use
`--html-only` to iterate without a browser.

**PNG is 2160x2700 instead of 1080x1350** — the rasterizer lost
`--force-device-scale-factor=1`. On a HiDPI screen `--window-size` is multiplied
by the display scale. `HeadlessBrowserRasterizer` verifies every PNG with
`getimagesize()` and throws rather than shipping the wrong size — if you see this,
the flag was removed.

**The font looks wrong (not Montserrat)** — the woff2 is inlined as base64 by
`EmbeddedFontProvider`. Montserrat is **not** a Windows system font, so any
failure to embed means the browser silently substitutes a *proportional* system
font with different metrics. Check `public/assets/fonts/montserrat/` exists and
that the exported `.html` contains `data:font/woff2;base64`.

**Text is clipped at the edge** — that is an **authoring** bug, not a render bug.
CSS wraps for real; the canvas has `overflow: hidden`. Shorten the copy (see the
budgets in `social-writer`). **Do not shrink the font** — it breaks the visual
system across posts.

**Arrows render in a different font** — `→` (U+2192) is not in the latin subset.
Use `->`. `social:lint` catches this as an ERROR.

**A style renders on some post types but not others** — almost certainly a CSS
comment problem. **Never put a Blade directive inside a CSS comment**: Blade
expands it, the pasted block contains its own comments, and **CSS comments do not
nest** — the first terminator closes the outer comment early, and the orphaned one
is a parse error that swallows the whole skin rule. Use `{{-- --}}` instead.
`SocialStyleTest` guards this.

**A code block renders as an empty rectangle** — a skin overrode `.body code`,
which has higher specificity than the base `.body pre code`, so it recolored the
code *inside* the block to match its own dark background. Scope inline-code rules
with `:not(pre) > code`.

## Iterating on the design

Use `--html-only` and open the `.html` in a browser at 1080x1350. The document is
self-contained (font inlined, zero external resources), so it renders identically
to the final PNG **without** a server or network. This is the fast loop — no
browser automation involved.

With `SOCIAL_PREVIEW=true` in `.env` you also get `/social/{slug}/preview`
(all slides, scaled) and `/social/{slug}/slide/{n}` (exact canvas). Those routes
do not exist unless the flag is on.
