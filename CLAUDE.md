# Oatllo — notatki projektowe (dla Claude)

Blog dla programistów (PHP/Laravel/JS/architektura/DevOps/AI) + darmowe kursy. Laravel 11, PHP 8.4.
Dwujęzyczność sterowana env: `APP_LOCALE` (en/pl), `LANGUAGE_MODE` (`strict` = tylko jeden język,
`normal` = wszystkie). `APP_LANG_HTML` = wartość atrybutu `lang`.

## Architektura treści (WAŻNE)

Artykuły pochodzą z **dwóch źródeł**, scalanych po `slug` (**`.md` ma pierwszeństwo**):
1. **Baza** — model `App\Models\Article`.
2. **Pliki `.md`** — `App\Services\Article\MarkdownArticleRepository` (parsowane w pamięci przez
   `MarkdownArticleParser`, `exists=false`, brak wiersza w bazie). Katalog: `config('articles.path')`
   (domyślnie `storage/app/articles`). Upload/edycja/usuwanie przez API `ArticleImportController`.

Wspólny punkt renderu obu źródeł: **`Article::getDisplayContents()`** — tu dzieje się:
- **`ContentSanitizer`** (`app/Services/Article/ContentSanitizer.php`): em/en dashe → `-` + słownik anti‑AI.
- **`InternalLinker`** (`app/Services/Article/InternalLinker.php`): linkowanie wewnętrzne **przy renderze**
  (nietrwałe, uniwersalne dla bazy i `.md`). Indeks fraz→URL (keys_link + tytuły + tagi) cache'owany per
  język. Konfiguracja: `config/articles.php` → `internal_linking`. Frazy‑kotwice można podać we frontmatterze
  `.md` (`keys_link:` / `keywords:`). Każdy poziom opakowany w try/catch — **linkowanie nigdy nie wywala 500**.
- Wynik zmemoizowany na instancji modelu (widok woła metodę 2×: word count + render).

Stary `InternalUrlsGenerator` generuje już **tylko `keys_link`** (faza utrwalania linków wyłączona).

## CSS / Tailwind (WAŻNE — inaczej „popsują się" style)

Część publiczna **NIE używa** `cdn.tailwindcss.com` (był render‑blocking, FOUC, zły LCP/FCP).
Tailwind jest **kompilowany do statycznego pliku** i podpięty zwykłym `<link>`:
- Wejście: `resources/css/public.css` → wyjście: **`public/assets/css/tailwind.css`** (wersjonowany w git).
- Build: **`npm run css:public`** (skrypt = `tailwindcss -i resources/css/public.css -o public/assets/css/tailwind.css --minify`).
- **Po dodaniu nowych klas Tailwind w szablonach `.blade.php` trzeba PRZEBUDOWAĆ CSS** (`npm run css:public`)
  i zacommitować wynik. Deploy **nie** wymaga builda na serwerze (plik jest w repo).
- Klasy budowane dynamicznie w Blade (np. `hover:text-{{ $accent }}-400` w `partials/site_footer.blade.php`)
  są w **`safelist`** w `tailwind.config.js` — inaczej build je usunie.
- Treść artykułów NIE wprowadza klas Tailwind (stylizuje ją `.prose` / inline `<style>`), więc rebuild
  dotyczy tylko zmian w szablonach.

## Design system v2

Ciemny motyw `neutral-950`, akcent **rose** (blog/artykuły) lub **emerald** (kursy). Font **Montserrat**.
Sticky „glass" nav. **UWAGA:** menu mobilne musi być **poza `<header>`** — `backdrop-filter` na headerze
tworzy containing‑block dla `position:fixed` i menu przestaje działać po przewinięciu.
Wspólne partiale: `resources/views/partials/site_footer.blade.php` (stopka‑huby: kategorie + tagi + About + Mapa,
dane cache'owane, `$accent` = rose/emerald) oraz `resources/views/views_basic/partials/article_card.blade.php`.
Strony błędów: `resources/views/errors/{404,500}.blade.php` (samowystarczalne, **bez zapytań do bazy**).

## Wydajność / bezpieczeństwo zapytań

- **Nie używać `ORDER BY RAND()`** (`inRandomOrder()`) na tabeli artykułów — powoduje MySQL
  „Out of sort memory". Używać `Article::randomPublished($limit, $excludeId, $language)`
  (losowanie id w PHP + pobranie po PK).

## SEO

- `robots.txt` wskazuje `Sitemap: https://oatllo.com/sitemap.xml` (+ `Disallow: /api/`).
- XML sitemap generuje `App\Services\SitemapService` (artykuły baza+`.md`, kategorie, tagi, kursy, `/mapa`, `/about-us`).
- HTML „mapa strony": trasa `site.map` → `/mapa` (`HomeController::siteMap`) → `views_basic/sitemap.blade.php`.
- Wyszukiwarka bloga i puste strony tagów → `noindex`. Paginacja → self‑canonical z `?page=N`.

## Checklist wdrożenia (produkcja)

1. `php artisan course:process --force` — regeneracja `content_html` lekcji nowym parserem (CommonMark).
   Bez tego stare lekcje mają zepsuty HTML (m.in. kursywa z `UPPER_CASE`).
2. Upewnić się, że **`public/assets/css/tailwind.css`** jest wdrożony (jest w repo — deploy = git pull; nie trzeba budować).
3. Po dodaniu nowych klas Tailwind: `npm run css:public` + commit przed deployem.
