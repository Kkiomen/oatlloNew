---
name: course-writer
description: >-
  Create or extend an Oatllo course (or a single lesson) as committed Markdown
  files in resources/courses/, in the exact structure the site renders directly
  from disk (no database, no upload). Use when the user wants to add a course,
  add/edit a chapter or lesson, or asks "nowy kurs", "dodaj lekcję",
  "stwórz kurs", "napisz lekcję kursu".
---

# course-writer — courses as Markdown files for Oatllo

Oatllo renders courses **directly from `.md` files** committed to the repo — the
same way as `.md` articles. There is **no database write and no upload**: you just
create files, the user commits them, and the pages work. (Courses also exist in the
DB; files win by slug when both exist.)

Handled by `App\Services\Course\MarkdownCourseRepository`; the base directory is
`config('articles.courses_path')` → **`resources/courses/`**.

## Directory structure

```
resources/courses/
  {course-slug}/                 # folder name = course slug (kebab-case)
    course.md                    # course metadata (frontmatter) + "About" body
    01-{chapter-slug}/           # chapter folder; the NN- prefix sets the order
      _chapter.md                # chapter title/description (frontmatter + body)
      01-{lesson-slug}.md        # lesson: frontmatter + Markdown body
      02-{another-lesson}.md
    02-{next-chapter}/
      _chapter.md
      01-{lesson}.md
```

Rules:
- **Ordering** comes from the numeric `NN-` prefix on chapter folders and lesson
  files (`01-`, `02-`, …). Use it; keep gaps small (10, 20, 30 is fine).
- **Slugs** come from frontmatter `slug`; if omitted, from the folder/filename with
  the `NN-` prefix stripped (`01-what-is-php.md` → slug `what-is-php`). Slugs become
  the URL, so keep them lowercase-hyphenated and stable.
- Reference example already in the repo: **`resources/courses/laravel-basics/`**.

## `course.md` (course metadata)

```markdown
---
name: "Course Name"
slug: course-slug                 # optional; defaults to the folder name
lang: en                          # matches the site locale (usually en)
image: https://picsum.photos/seed/course-slug/1200/630
title_list: "Short card title"
description_list: "One-line description shown on course cards/listing."
title_full: "Full course title shown in the hero"
description_full: "Short intro sentence shown under the hero title."
title_seo: "SEO <title> - Free X Course | Oatllo"
description_seo: "Meta description, <=155 chars, primary keyword + benefit."
is_published: true
---

## About this course

The body is Markdown. It renders as the "About this course" section on the course
page. Say who it's for and what they'll build. Use **bold** for key terms.
```

All fields are optional and have sensible defaults (e.g. `name`/titles fall back to
the folder slug). Provide at least `name`, `title_list`, `description_list`,
`image`, `title_seo`, `description_seo` for a good page.

## `_chapter.md` (chapter / section)

```markdown
---
title: "Chapter title"
slug: chapter-slug                # optional; defaults to folder name minus NN-
description: "One line describing the chapter."
---

Optional Markdown body -> rendered as the chapter's "About this chapter" section.
```

## Lesson file `NN-{slug}.md`

```markdown
---
title: "Lesson title"
slug: lesson-slug                 # optional; defaults to filename minus NN-
seo_title: "SEO title for this lesson"
seo_description: "Meta description for the lesson, <=155 chars."
---

## First section

Write the lesson body in Markdown. It's converted to HTML with CommonMark (GFM):

- Headings `##` / `###` for sections (do not use `#`; the lesson title is the H1).
- Fenced code blocks with a language for syntax highlighting:

  ```php
  <?php
  echo 'Hello';
  ```

- **bold**, *italic*, lists, tables, links — all standard Markdown.
```

## Teaching style — IMPORTANT

Write courses the way the **Laravel documentation** is written: **simple, clear and
approachable, so a complete beginner can follow along.** This is the single most
important rule of this skill.

- **Plain language.** Short sentences. Explain like you're talking to someone new to
  the topic. Avoid jargon; when you must use a technical term, define it the first
  time it appears.
- **One idea at a time.** Each lesson teaches one small, focused thing. Prefer more
  short lessons over one long one.
- **Show, then explain.** Give a minimal code example, then explain what each part
  does in prose. Keep examples runnable and as small as possible.
- **Never get ahead of the material — STRICT RULE.** Do **not** use a concept, term,
  function, class or feature that hasn't been introduced yet in an *earlier* lesson
  (or in the current one, above the point where you use it). The course must read
  top-to-bottom with nothing unexplained.
  - Before using anything, ask yourself: "was this explained in a previous lesson or
    earlier in this one?" If not, either (a) explain it first, or (b) don't use it yet.
  - If a concept truly needs something from later, restructure the outline so the
    prerequisite comes first. Order chapters/lessons by dependency.
  - It's fine to say "we'll cover X in a later lesson" — but then don't rely on X now.
- **Build up gradually.** Each lesson should assume only what earlier lessons taught.
  When you reuse an earlier concept, a one-line reminder is welcome; a forward
  reference to something not-yet-taught is not.
- **Beginner-friendly tone.** Encouraging, concrete, no fluff and no showing off.
  The goal is that *anyone* can understand it.

When outlining a new course, order the chapters and lessons so that every concept is
introduced before it's used. Sanity-check the finished course by reading it in order
and confirming nothing appears out of nowhere.

## Writing conventions

- **Language:** English, to match the public site (welcome/blog/articles are EN).
- **Dashes:** use plain hyphens `-`, not em dashes `—` (the site sanitizes em/en
  dashes to `-` on render anyway, but write them plain).
- **Code:** always tag fenced blocks with a language (```php, ```bash, ```sql,
  ```js) — highlight.js colors them.
- **Headings:** start lesson sections at `##`. Keep them descriptive (they help SEO
  and readability).
- **No HTML needed** in the body; write Markdown. (Frontmatter values are plain text.)
- **Slugs are URLs** — don't rename them after publishing without a redirect.

## URLs produced

For a course `laravel-basics`, chapter `getting-started`, lesson `installation`:
- Course:  `/course/laravel-basics`
- Chapter: `/course/laravel-basics/getting-started`
- Lesson:  `/course/laravel-basics/getting-started/installation`

The course also appears on `/courses`, the homepage courses section, and the XML
sitemap automatically.

## Workflow

1. Ask for (or infer) the course topic, target audience, and rough chapter/lesson
   outline. For a single lesson, ask which existing course + chapter it belongs to.
2. Create the folder(s) and files under `resources/courses/` following the structure
   above. For a new course: `course.md` + at least one chapter with 1-2 lessons.
   For a new lesson: add `NN-{slug}.md` in the right chapter folder (pick the next
   `NN-` prefix).
3. Write real, practical content (no fluff) — this is a developer course.
4. Tell the user the resulting URLs and that they just need to **commit the files
   and deploy** — no artisan command, no upload, nothing to run.

## Verify (optional, local)

Serve the app and open the URLs above, or check `HomeController::course()` /
`chapterEn()` / `courseLessonEn()` resolve the course via `resolveCourse()`
(file first, DB fallback). Pages should return 200 and show the content, chapters
and prev/next navigation.
