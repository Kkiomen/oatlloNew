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
stare at a 1045 for twenty minutes. Checklist linked in bio.
