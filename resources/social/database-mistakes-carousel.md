---
slug: database-mistakes-carousel
type: carousel
language: en
title: "Common database design mistakes"
topic: database
source_type: article
source: database-design-mistakes
link: https://oatllo.com/database-design-mistakes
publish_at: 2026-08-03 19:00
status: ready
formats: [post, reel]
hashtags: [database, sql, architecture, backend, webdev]
caption: |
  Your invoice totals are off by a cent because someone typed the amount column as FLOAT.

  Floating point can't hold 0.1. The drift is invisible in testing and shows up
  the day finance sums 40,000 rows. Three schema decisions that bill you later.

  Full write-up linked in bio.

  Which one bit you first: FLOAT money, a comma-separated tags column, or MySQL's fake utf8?
---

## Your invoice totals are off by a cent and nothing threw

Nobody wrote a bug. The column type wrote it, two years ago.

<!-- slide -->

## FLOAT can't hold 0.1

```sql
-- Don't
amount FLOAT

-- Do
amount DECIMAL(12, 2) NOT NULL
```

40,000 invoices, each off by a cent or two. Money is not an approximation.

<!-- slide -->

## The tags column you can't query

```sql
tags VARCHAR(50) -- "php,mysql,laravel"
```

Every article tagged `mysql`? That's `LIKE '%mysql%'`. It also matches `mysqld`,
and it can't use an index.

<!-- slide -->

## One extra table pays for itself

```sql
CREATE TABLE article_tag (
    article_id BIGINT NOT NULL,
    tag_id     BIGINT NOT NULL,
    PRIMARY KEY (article_id, tag_id)
);
```

Now it's a plain indexed join. Renaming a tag is a one-row update.

<!-- slide -->

## The charset named utf8 lies to you

In MySQL, `utf8` is `utf8mb3`: three bytes per character, max. One emoji in a
display name and the insert truncates with `Incorrect string value`.

<!-- slide role="cta" -->

## utf8mb4 is the real one

```sql
ALTER TABLE users CONVERT TO
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;
```

Set the connection charset too, or the client mangles characters on the way in.
