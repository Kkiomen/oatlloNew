---
slug: fixing-sqlstate-hy000-general-error-laravel-carousel
type: carousel
language: en
title: "Reading SQLSTATE HY000"
topic: laravel
source_type: article
source: fixing-sqlstate-hy000-general-error-laravel
link: https://oatllo.com/fixing-sqlstate-hy000-general-error-laravel
publish_at: 2026-08-24 19:00
status: ready
formats: [post, reel]
hashtags: [laravel, php, mysql, debugging, database]
caption: |
  SQLSTATE[HY000] is not a bug. It's six different bugs in one costume.

  HY000 is the generic bucket. The bracketed driver code is the real signal:
  2002 reachability, 1045 credentials, 1049 missing schema, 1364 bad insert.

  Full checklist linked in bio.

  Which of them cost you the most hours?
verified:
  verdict: approved
  at: 2026-07-16 07:15
  fingerprint: 92082705e97c42830586c8ab139025122dedbfd7
  checks:
    - "every driver code is correct against MySQL reality, not just the article: 2002 client cannot connect, 1045 access denied, 1049 unknown database, 2006 server has gone away, 1364 field has no default value"
    - "the premise holds for a non-obvious reason worth stating: 1045 and 1049 have their own native SQLSTATEs (28000 and 42000), but PDO reports connect-phase failures as SQLSTATE[HY000] [nnnn], which is exactly the format Laravel users see - so grouping them under HY000 is right"
    - localhost forcing a Unix socket while 127.0.0.1 forces TCP is real MySQL client behaviour, and No such file or directory as the socket flavour of 2002 matches the article
    - in-container 127.0.0.1 pointing at the app container not MySQL - matches the article Docker/Sail note
    - 1364 via mass assignment silently dropping a column missing from  is real Eloquent behaviour and is the article first fix
    - config:clear serving yesterday credentials and the twenty minutes staring at a 1045 is the article anecdote verbatim
    - slide 3 vocabulary line is the article closing sentence verbatim, same five codes in the same order
  notes: |
    One thing to eyeball, resolvable but loose. The hook says six different bugs while slide 3 (labelled the whole vocabulary, in one slide, thats the entire skill) lists five codes. Six is defensible - the article checklist has six error rows because 2002 splits into a TCP flavour and a socket flavour, and slide 4 does cover that split - so it is bugs, not codes. But a reader who counts slide 3 gets five and the reconciliation only arrives a slide later. The article itself says a dozen unrelated ones wearing the same coat, so six is the posts own arithmetic, not a quote.
---

## HY000 is six different bugs in one costume

It's the SQL standard's bucket for "general error". Googling the whole string
wastes an hour. The useful part is somewhere else in the message.

<!-- slide -->

## Skip straight to the bracketed number

```
SQLSTATE[HY000] [2002] Connection refused
(SQL: select * from `users` where `id` = 1)
```

HY000 is noise. `[2002]` is the driver code and the real signal. Read the
sentence after it literally.

<!-- slide -->

## The whole vocabulary, in one slide

`2002` reachability. `1045` credentials. `1049` missing schema. `2006` a
dropped connection. `1364` a bad insert. That's the entire skill.

<!-- slide -->

## 2002 is almost always DB_HOST

```env
# in a container 127.0.0.1 = app, not MySQL
DB_HOST=db

# on the host, localhost forces a socket
DB_HOST=127.0.0.1
```

"No such file or directory" is the socket variant of the same code.

<!-- slide -->

## 1364 is not a connection problem at all

```
Field 'title' doesn't have a default value
```

You inserted a NOT NULL column with no value. Usually it's missing from
`$fillable`, so mass assignment silently dropped it.

<!-- slide role="cta" -->

## Edited .env and nothing changed?

```bash
php artisan config:clear
```

Cached config serves yesterday's credentials. People rotate a password and
stare at a 1045 for twenty minutes.
