---
name: blog-upload
description: >-
  Publish a finished Markdown article to Oatllo by committing it to
  resources/articles/ and deploying via git. Use when a .md article is ready to
  publish, or when the user asks to "upload/publish the post", "wgraj/opublikuj
  artykuł", "wyślij na oatllo".
---

# blog-upload — publish a Markdown article to oatllo.com (git workflow)

Articles are **committed Markdown files** in `resources/articles/`, exactly like
courses in `resources/courses/`. There is **no upload API** — the only way to
publish is to add the `.md` file to the repo and deploy with `git pull`. Full
reference: `docs/markdown-articles-api.md`.

Once committed and pulled on production, the site renders the article on the blog,
its own page, tag/category pages, RSS, and sitemap. Visibility is driven by
frontmatter: a `published_at` in the future stays hidden until its time passes —
no database row, no cron needed.

## Steps

1. **Place the file.** Write the finished article to
   `resources/articles/<slug>.md`. The filename must equal the slug. Ensure the
   file is **UTF-8** (CommonMark rejects other encodings).

2. **Sanity-check the frontmatter.** `name` is required. Set `published_at`
   (past = live now, future = scheduled) and `language`. Confirm the slug is
   unique (no existing file or DB article with the same slug — `.md` wins on
   conflict, so a clash silently overrides the DB one).

3. **Verify it parses locally** (optional but recommended):

```bash
php artisan tinker --execute="echo app(\App\Services\Article\MarkdownArticleRepository::class)->findBySlug('<slug>')?->name;"
```

4. **Commit.**

```bash
git add resources/articles/<slug>.md
git commit -m "Blog: publish <slug>"
```

5. **Deploy** = push + `git pull` on the server (per the project's normal deploy).
   Only commit/push when the user asks. If on `main`, follow the repo's usual
   flow.

6. **After deploy, ping IndexNow** (optional, speeds up Bing discovery):

```bash
php artisan indexnow:submit-sitemap --regenerate
```

7. **Confirm live.** Fetch the article URL and check HTTP 200 + the title:

```bash
curl -sk -o /dev/null -w "%{http_code}\n" https://oatllo.com/<slug>
```

   A future-dated (scheduled) article correctly returns 404 until its
   `published_at` passes — that's not an error.

## Safety

- Publishing is an outward-facing, live action. In autonomous flows (e.g. the
  `blog-post` pipeline) publishing (commit + deploy) is expected. Outside those
  flows, if the user hasn't clearly asked to go live, confirm before committing
  or pushing.
- Confirm success from the actual git result and the live HTTP status — never
  assume it worked.
