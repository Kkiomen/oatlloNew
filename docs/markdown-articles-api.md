# Artykuły z plików Markdown + API importu

Artykuły mają teraz **dwa źródła**:

1. **Baza danych** – jak dotychczas (model `Article`).
2. **Pliki `.md`** – w katalogu `storage/app/articles/`, renderowane dynamicznie
   bez zapisu do bazy.

Przy tym samym `slug` **plik `.md` ma pierwszeństwo** przed bazą danych.

Cel: lokalny Claude na komputerze może wgrywać artykuły przez API (plik `.md`
lub jego treść), zamiast robić to ręcznie na serwerze.

---

## Konfiguracja

W `.env`:

```env
ARTICLE_API_TOKEN=<sekret>          # token autoryzacyjny API (już wygenerowany)
# ARTICLES_MD_PATH=/inna/sciezka    # opcjonalnie: własny katalog na pliki .md
```

Katalog domyślny: `storage/app/articles/`.

---

## Format pliku `.md`

Frontmatter YAML + treść w Markdown:

```markdown
---
name: "Tytuł artykułu"          # WYMAGANE
slug: tytul-artykulu            # opcjonalne (domyślnie z nazwy pliku lub tytułu)
short_description: "Krótki opis dla listy blogа i SEO."
image: https://.../cover.jpg
language: en                    # domyślnie APP_LOCALE
published_at: 2026-07-08 10:00:00
is_published: true              # domyślnie true
category: nazwa-kategorii       # opcjonalne (slug istniejącej kategorii w bazie)
tags: [laravel, php]            # opcjonalne
---

## Nagłówek

Treść w **Markdown** – listy, [linki](https://...), bloki kodu itd.
```

Slug ustalany jest w kolejności: `slug` z żądania → `slug` z frontmatteru →
nazwa pliku → `Str::slug(name)`.

---

## Endpointy API

Wszystkie wymagają nagłówka: `Authorization: Bearer <ARTICLE_API_TOKEN>`.

| Metoda | Ścieżka                | Opis                                    |
|--------|------------------------|-----------------------------------------|
| POST   | `/api/articles`        | Utwórz/zaktualizuj artykuł              |
| GET    | `/api/articles`        | Lista artykułów `.md`                   |
| GET    | `/api/articles/{slug}` | Surowa zawartość pliku `.md`            |
| DELETE | `/api/articles/{slug}` | Usuń plik `.md`                         |

### Upload jako treść (JSON)

```bash
curl -X POST https://twoja-domena/api/articles \
  -H "Authorization: Bearer $ARTICLE_API_TOKEN" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{"content":"---\nname: \"Mój artykuł\"\nslug: moj-artykul\nlanguage: en\n---\n\n## Cześć\n\nTreść."}'
```

### Upload jako plik `.md` (multipart)

```bash
curl -X POST https://twoja-domena/api/articles \
  -H "Authorization: Bearer $ARTICLE_API_TOKEN" \
  -H "Accept: application/json" \
  -F "file=@artykul.md"
```

### Odpowiedź (sukces)

```json
{
  "success": true,
  "message": "Artykuł utworzony.",
  "data": {
    "slug": "moj-artykul",
    "name": "Mój artykuł",
    "language": "en",
    "is_published": true,
    "url": "https://twoja-domena/moj-artykul",
    "file": ".../storage/app/articles/moj-artykul.md",
    "created": true
  }
}
```

Kody: `201` utworzony, `200` zaktualizowany/OK, `401` zły token,
`422` brak treści lub brak `name` we frontmatterze, `404` nie znaleziono.

---

## Gdzie pojawiają się artykuły

Artykuły z plików `.md` są scalane z artykułami z bazy we **wszystkich** listach
i podstronach (plik `.md` ma pierwszeństwo przy tym samym slug):

- **Lista bloga** (`/blog`) – scalone i posortowane po dacie, z paginacją.
- **Podstrona artykułu** (`/{slug}`) – renderuje plik `.md`, jeśli istnieje; inaczej baza.
- **Podstrona z kategorią** (`/{categorySlug}/{slug}`) – jw. (gdy artykuł ma `category`).
- **Strona tagu** (`/blog/tag/{tag}`) – artykuły z bazy i md z danym tagiem. Tag może
  istnieć wyłącznie w plikach `.md` (nie musi być w tabeli `tags`).
- **Strona kategorii** (`/blog/lista/{slug}`) – artykuły z bazy i md w danej kategorii.
- **RSS** (`/feed`) – 20 najnowszych z obu źródeł.
- **Sitemap** (`public/sitemap.xml`) – artykuły md, ich strony tagów (także tagi
  istniejące tylko w md) i strony kategorii trafiają do mapy strony przy jej
  regeneracji (`SitemapService::generateSitemap()`).

### Kategoria i tagi w pliku `.md`

- `category: <slug>` we frontmatterze jest dopasowywany do istniejącej kategorii w bazie
  (po `slug`). Dzięki temu artykuł md pojawia się na stronie tej kategorii, a jego URL
  przyjmuje formę `/{categorySlug}/{slug}`.
- `tags: [...]` tworzy lekkie tagi w pamięci (nie są zapisywane do bazy), po których
  działa filtrowanie na stronie tagu.

---

## Pliki implementacji

- `config/articles.php` – ścieżka i token.
- `app/Services/Article/MarkdownArticleParser.php` – parsowanie md → model `Article` (w pamięci).
- `app/Services/Article/MarkdownArticleRepository.php` – odczyt/zapis plików `.md`.
- `app/Http/Middleware/VerifyArticleApiToken.php` – autoryzacja tokenem.
- `app/Http/Controllers/Api/ArticleImportController.php` – endpointy API.
- `routes/api.php` – rejestracja tras.
- `app/Http/Controllers/HomeController.php` – scalanie źródeł w `blog()` i `article()`.
