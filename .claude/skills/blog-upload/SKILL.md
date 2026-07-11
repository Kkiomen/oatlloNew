---
name: blog-upload
description: >-
  Upload/publish a finished Markdown article to Oatllo (oatllo.com) via the
  articles API, and confirm it went live. Use when a .md article is ready to
  publish, or when the user asks to "upload/publish the post", "wgraj/opublikuj
  artykuł", "wyślij na oatllo".
---

# blog-upload — publish a Markdown article to oatllo.com

Publishes a Markdown article to **https://oatllo.com** through the articles
import API. The API saves the `.md` file on the server and the site renders it on
the blog, its own page, tag/category pages, RSS, and sitemap. Full API reference:
`docs/markdown-articles-api.md`.

## Endpoint

```
POST https://oatllo.com/api/articles
Authorization: Bearer <ARTICLE_API_TOKEN>
```

Two ways to send the article (pick one):
- **file** (multipart) — upload the `.md` file.
- **content** (JSON) — send the raw Markdown string.

## Token

The request needs a bearer token that **matches `ARTICLE_API_TOKEN` configured on
the production server** (oatllo.com's `.env`).

Resolve the token in this order:
1. `OATLLO_ARTICLE_API_TOKEN` environment variable, else
2. `ARTICLE_API_TOKEN` from the local project `.env`.

If neither is set, **stop and ask the user for the token** — do not proceed.

> Note: the token generated in local `.env` only works locally. For oatllo.com,
> the same token value must exist in the production server's `.env`. If uploads
> return 401, the tokens don't match.

## Steps

1. Resolve the target base URL (default `https://oatllo.com`).
2. Resolve the token (see above).
3. Upload the draft file. Prefer the **file** form:

```bash
TOKEN="${OATLLO_ARTICLE_API_TOKEN:-$(grep '^ARTICLE_API_TOKEN=' .env | cut -d= -f2)}"
curl -sS -X POST https://oatllo.com/api/articles \
  -H "Authorization: Bearer $TOKEN" \
  -H "Accept: application/json" \
  -F "file=@blog-drafts/<slug>.md" \
  -w "\nHTTP %{http_code}\n"
```

   If the caller only has the raw Markdown (no file), send it as JSON instead:

```bash
curl -sS -X POST https://oatllo.com/api/articles \
  -H "Authorization: Bearer $TOKEN" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  --data @- <<'JSON'
{"content": "<escaped markdown here>"}
JSON
```

4. **Check the response.**
   - `201` created / `200` updated → success. Report `data.url`, `data.slug`,
     `data.is_published`.
   - `401` → token mismatch (see Token note). Ask the user to align the token.
   - `422` → validation error (missing content, or frontmatter missing `name`).
     Fix the article and retry.
   - Other/non-JSON → report the raw output; do not claim success.

5. Report the live URL to the user.

## Other operations (same auth)

- List published `.md` articles: `GET https://oatllo.com/api/articles`
- Fetch one (raw): `GET https://oatllo.com/api/articles/{slug}`
- Delete one: `DELETE https://oatllo.com/api/articles/{slug}`

## Safety

- Publishing is an outward-facing, live action. In autonomous flows (e.g. the
  `blog-post` pipeline) it's expected to publish directly. Outside those flows,
  if the user hasn't clearly asked to go live, confirm first.
- Never print the token value in output or logs.
- Confirm success from the actual HTTP status/JSON — never assume it worked.
