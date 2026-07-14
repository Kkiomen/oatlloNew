---
name: course-cover
description: >-
  Generate and customize the auto-generated SVG cover image for an Oatllo course
  (the "tech logo" theme: big technology logo + "Free course" badge + chapter
  dots, accent-colored to the topic). Use when the user wants a course thumbnail /
  cover / og:image, wants to add a logo for a new technology (Docker, Kubernetes,
  etc.), or asks "grafika/okładka pod kurs", "logo kursu", "cover kursu".
---

# course-cover — auto-generated course cover graphics for Oatllo

Courses get a **dynamically generated SVG cover** (og:image + hero), the same idea
as article covers but visually distinct: instead of a "code window" it shows a
**large technology logo**, a **"Free course" pill**, the course title, a
**chapter count**, and **chapter dots** — all tinted with a per-technology accent
color. Pure SVG, no PHP image extensions, works identically locally and on prod.

This is the course analog of the article cover system (`config/covers.php` +
`CoverImageService`).

## How it works (files)

- **`config/course-covers.php`** — themes. Each theme = `accent` (color) + `label`
  (badge text) + `logo` (inline SVG mark on a `0 0 100 100` canvas). The theme is
  picked by matching `keywords` against the course name/slug/symbol/description
  (first match wins; else `default`, which is emerald with a graduation-cap logo).
- **`app/Services/Course/CourseCoverImageService.php`** — `renderForCourse(Course)`:
  resolves the theme, lays out + auto-scales the title, renders the Blade view.
- **`resources/views/covers/course-cover.blade.php`** — the SVG template.
- **`app/Http/Controllers/CourseCoverController.php`** + route
  **`/courses/{slug}/cover.svg`** (`route('course.cover')`) — serves it live
  (24h cache). Resolves course by `.md` first, then DB (same precedence as everywhere).
- **`app/Console/Commands/GenerateCourseCover.php`** — `php artisan course:cover`
  writes the SVG to a file for **offline preview only** (not needed to deploy).

## Pointing a course at its cover

In the course's `course.md` frontmatter, set:

```yaml
image: auto
```

`auto` (or an empty/missing `image`) makes `MarkdownCourseRepository` set the course
image to `route('course.cover', ...)`. To override with a custom picture, put a real
URL in `image:` instead. That's the only wiring — the cover then appears as the
course og:image / hero automatically. **No build step, no command; commit + deploy.**

## Adding a logo for a NEW technology

Edit **`config/course-covers.php`** → add an entry to `'themes'`:

```php
'rust' => [
    'keywords'     => ['rust', 'cargo', 'crate'], // matched vs name/slug/desc, lowercase
    'accent'       => '#dea584',                   // brand color (hex) – drives the SVG cover
    'accent_color' => 'orange',                    // Tailwind palette name – drives the whole course page
    'label'        => 'Rust',                       // badge text
    'logo'         => '<g fill="currentColor">...</g>', // inline SVG, canvas 0 0 100 100
],
```

Logo rules:
- Draw on a **`0 0 100 100`** canvas. The view scales it to ~260px and centers it.
- Use **`fill="currentColor"` / `stroke="currentColor"`** so the mark is rendered in
  the view's ink color (near-white `#f8fafc`); the accent provides the color identity
  (glow, badge, underline, dots). Keep marks **monochrome** for a consistent set.
- Prefer simple, recognizable geometric marks built from `rect`/`circle`/`path`
  (see the existing Docker whale, DB cylinder, terminal, PHP ellipse). **Do not paste
  copyrighted vendor logo path data** — draw a clean, generic mark instead.
- **Ordering matters**: put specific themes before generic ones (e.g. `docker` and
  `kubernetes` before the generic `devops`/terminal theme; `laravel` before `php`).

Existing themes: `docker`, `kubernetes`, `laravel`, `php`, `node`, `javascript`,
`python`, `database`, `git`, `devops` (terminal), `ai`, plus `default` (grad cap).

## Per-course PAGE accent (not just the cover)

Each theme also has **`accent_color`** — a **Tailwind palette name** (`emerald`,
`sky`, `blue`, `red`, `green`, `amber`, `cyan`, `orange`, `rose`) that colors the
**whole course page** (course / chapter / lesson views) and the course's card on the
`/courses` listing. So a Docker course reads blue end-to-end, a PHP course green, etc.
`CourseCoverImageService::accentColor(Course)` resolves it (same theme match as the
cover); the views compute `$accent` (palette) + `$accentHex` (glow) from it.

Keep `accent` (hex) and `accent_color` (palette) **the same color** so the cover and
the page match (e.g. Docker `#2496ed` + `sky`; PHP is green on purpose: `#34d399` +
`emerald`).

**Adding a NEW page-accent color = you MUST safelist it.** The course views build
classes like `text-{{ $accent }}-400` dynamically, which Tailwind's scanner can't see.
`tailwind.config.js` has a `safelist` generator with an `accentColors` array — add the
new color there, then rebuild: `npm run css:public` and commit
`public/assets/css/tailwind.css`. Reusing a color already in that array needs no rebuild.
Prefer reusing an existing palette color over adding a new one.

## Preview a cover offline

```bash
php artisan course:cover docker-basics        # one course -> storage/app/course-covers/*.svg
php artisan course:cover --all                # all file-based courses
php artisan course:cover docker-basics --out=./tmp
```

To eyeball it as an image, open the `.svg` in a browser, or render with headless
Edge/Chrome:

```bash
"/c/Program Files (x86)/Microsoft/Edge/Application/msedge.exe" --headless --disable-gpu \
  --screenshot="OUT.png" --window-size=1200,630 "file:///ABS/PATH/TO/cover.svg"
```

The generated `storage/app/course-covers/` files are throwaway previews (storage is
git-ignored) — the real cover is always served live from the route.

## Workflow

1. If the course's topic already has a theme (check `config/course-covers.php`
   keywords), just set `image: auto` in `course.md` — done.
2. If it's a new technology, add a theme (keywords + accent + label + logo) as above,
   then set `image: auto`.
3. Preview with `php artisan course:cover {slug}` and (optionally) render to PNG.
4. Tell the user to commit (`config/course-covers.php` + the `course.md` change) and
   deploy. Nothing to build.
