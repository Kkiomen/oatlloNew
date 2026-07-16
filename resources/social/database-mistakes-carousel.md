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
verified:
  verdict: approved
  at: 2026-07-16 07:07
  fingerprint: 92f9b08bb43dc5570e3a8b0f7e1cccabd3fb87c9
  checks:
    - utf8 = utf8mb3 (3 bajty) potwierdzone w dokumentacji MySQL, nie tylko w artykule
    - skladnia CONVERT TO CHARACTER SET utf8mb4 poprawna
    - composite PK w tabeli laczacej - poprawny SQL
  notes: |
    POPRAWIONE PO WERYFIKACJI: slajd mowil, ze insert 'truncates with Incorrect string value' - to sklejenie dwoch roznych trybow awarii. Przy strict mode (domyslnym w MySQL 5.7 i 8) insert jest ODRZUCANY bledem 1366; obcinanie to sciezka non-strict i przychodzi z ostrzezeniem, nie z tym bledem. Zmienione na 'is rejected'. RYZYKO STARZENIA: utf8mb3 jest deprecated, MySQL zapowiada, ze utf8 stanie sie aliasem utf8mb4 - na sierpien OK, ale nie trzymac tego w kolejce latami. Wymaga Twojej ostatecznej akceptacji.
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
display name and the insert is rejected: `Incorrect string value`.

<!-- slide role="cta" -->

## utf8mb4 is the real one

```sql
ALTER TABLE users CONVERT TO
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;
```

Set the connection charset too, or the client mangles characters on the way in.
