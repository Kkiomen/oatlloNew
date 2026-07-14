# Artykuły z plików Markdown (commitowane w repo)

Artykuły mają **dwa źródła**:

1. **Baza danych** – model `Article`.
2. **Pliki `.md`** – w katalogu **`resources/articles/`**, commitowane w repo i
   renderowane dynamicznie bez zapisu do bazy (analogicznie do kursów w
   `resources/courses/`).

Przy tym samym `slug` **plik `.md` ma pierwszeństwo** przed bazą danych.

## Workflow (WAŻNE)

Nie ma już API do uploadu. Jedynym źródłem plików jest git:

1. Twórz / edytuj plik `resources/articles/{slug}.md` **lokalnie**.
2. `git add` + `git commit`.
3. Deploy = `git pull` na produkcji.
4. Po deployu (opcjonalnie, dla szybszego zgłoszenia do Bing):
   `php artisan indexnow:submit-sitemap --regenerate`.

Widoczność liczona jest z frontmattera: plik z `published_at` w przyszłości jest
**ukryty aż do terminu** (bez wiersza w bazie, bez crona). `/api/cron` służy już
tylko do regeneracji `sitemap.xml` (+ publikacji zaplanowanych artykułów z bazy).

## Konfiguracja

Katalog domyślny: `resources/articles/`. Można nadpisać w `.env`:

```env
# ARTICLES_MD_PATH=/inna/sciezka    # opcjonalnie: własny katalog na pliki .md
```

> Uwaga przy deployu: upewnij się, że produkcja **nie** ma starego
> `ARTICLES_MD_PATH=storage/app/articles` w `.env` — inaczej czytałaby stary
> katalog zamiast wersjonowanego `resources/articles/`.

## Kodowanie plików

Pliki muszą być **UTF-8** (CommonMark odrzuca inne). Parser próbuje ratować
pliki nie-UTF-8 (`MarkdownArticleParser::normalizeEncoding`), ale zapisuj w UTF-8,
żeby uniknąć zepsutych polskich znaków — szczególnie edytując z Windowsa.

## Format pliku `.md`

Frontmatter YAML + treść w Markdown:

```markdown
---
name: "Tytuł artykułu"          # WYMAGANE
slug: tytul-artykulu            # opcjonalne (domyślnie z nazwy pliku lub tytułu)
short_description: "Krótki opis dla listy bloga i SEO."
image: https://.../cover.jpg
language: en                    # domyślnie APP_LOCALE
published_at: 2026-07-08 10:00:00
is_published: true              # domyślnie true
category: nazwa-kategorii       # opcjonalne (slug istniejącej kategorii w bazie)
tags: [laravel, php]            # opcjonalne
keys_link: [fraza-kotwica]      # opcjonalne — frazy do linkowania wewnętrznego
---

## Nagłówek

Treść w **Markdown** – listy, [linki](https://...), bloki kodu itd.
```

Slug ustalany jest w kolejności: `slug` z frontmatteru → nazwa pliku → `Str::slug(name)`.
Nazwa pliku powinna odpowiadać slugowi: `{slug}.md`.
