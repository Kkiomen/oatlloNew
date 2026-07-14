# Oatllo — notatki projektowe (dla Claude)

Blog dla programistów (PHP/Laravel/JS/architektura/DevOps/AI) + darmowe kursy. Laravel 11, PHP 8.4.
Dwujęzyczność sterowana env: `APP_LOCALE` (en/pl), `LANGUAGE_MODE` (`strict` = tylko jeden język,
`normal` = wszystkie). `APP_LANG_HTML` = wartość atrybutu `lang`.

## Architektura treści (WAŻNE)

Artykuły pochodzą z **dwóch źródeł**, scalanych po `slug` (**`.md` ma pierwszeństwo**):
1. **Baza** — model `App\Models\Article`.
2. **Pliki `.md`** — `App\Services\Article\MarkdownArticleRepository` (parsowane w pamięci przez
   `MarkdownArticleParser`, `exists=false`, brak wiersza w bazie). Katalog: `config('articles.path')`
   (domyślnie **`resources/articles/`**, commitowane w repo — jak kursy). **Workflow: twórz/edytuj pliki
   `.md` lokalnie → commit → deploy przez `git pull`.** Nie ma już API do uploadu — jedynym źródłem plików
   jest git. Widoczność liczona z frontmattera (`published_at` / `is_published`): plik z datą w przyszłości
   jest ukryty aż do terminu, bez wiersza w bazie i bez crona. Nazwa pliku = `{slug}.md`.

Wspólny punkt renderu obu źródeł: **`Article::getDisplayContents()`** — tu dzieje się:
- **`ContentSanitizer`** (`app/Services/Article/ContentSanitizer.php`): em/en dashe → `-` + słownik anti‑AI.
- **`InternalLinker`** (`app/Services/Article/InternalLinker.php`): linkowanie wewnętrzne **przy renderze**
  (nietrwałe, uniwersalne dla bazy i `.md`). Indeks fraz→URL (keys_link + tytuły + tagi) cache'owany per
  język. Konfiguracja: `config/articles.php` → `internal_linking`. Frazy‑kotwice można podać we frontmatterze
  `.md` (`keys_link:` / `keywords:`). Każdy poziom opakowany w try/catch — **linkowanie nigdy nie wywala 500**.
- Wynik zmemoizowany na instancji modelu (widok woła metodę 2×: word count + render).

Stary `InternalUrlsGenerator` generuje już **tylko `keys_link`** (faza utrwalania linków wyłączona).

**Kursy też mają dwa źródła** (analogicznie do artykułów, `.md` wygrywa po slug):
- **Baza**: `Course` → `CourseCategory` → `CourseCategoryLesson`.
- **Pliki `.md`** (commitowane w repo): `App\Services\Course\MarkdownCourseRepository`, katalog
  `config('articles.courses_path')` (domyślnie `resources/courses/`). Struktura: `{course-slug}/course.md`
  (frontmatter kursu), `{NN-chapter}/_chapter.md` (rozdział), `{NN-lesson}.md` (lekcja: frontmatter + Markdown
  → `content_html` przez CommonMark). Prefiks `NN-` ustala kolejność; slug z frontmatteru lub nazwy pliku.
  Repozytorium buduje niepersystowane modele z ustawionymi relacjami (course↔category↔lesson), więc `getRoute()`
  działa i renderują się przez te same widoki. Kontrolery kursów rozwiązują kurs przez `HomeController::resolveCourse()`
  (plik → fallback baza), a `mergedCourses()` scala listy. `CourseHelper::lessonGo` porównuje lekcje po `getRoute()`
  (nie po `id`, którego pliki nie mają). To NIE to samo co `CourseMarkdownService` (ten importuje `.md` DO bazy przez
  `php artisan course:process` — starsza ścieżka, nadal działa).
  **Okładki kursów** (og:image + hero): generowane dynamicznie jako SVG (motyw „logo technologii" —
  duże logo + pigułka „Free course" + kropki rozdziałów, akcent per‑technologia). To odpowiednik okładek
  artykułów (`config/covers.php`), ale wizualnie inny. Serwis: `App\Services\Course\CourseCoverImageService`,
  widok `resources/views/covers/course-cover.blade.php`, trasa `/courses/{slug}/cover.svg` (`course.cover`),
  motywy: `config/course-covers.php`. W `course.md` ustaw `image: auto` (lub pusto) → `MarkdownCourseRepository`
  podstawi trasę okładki; własny obrazek = pełny URL w `image:`. Nowa technologia = dopisz motyw (keywords +
  accent + label + logo SVG na kanwie 0 0 100 100, `currentColor`) w `config/course-covers.php`. Podgląd offline:
  `php artisan course:cover {slug}`. **Kolor per‑kurs**: każdy motyw ma też `accent_color` (nazwa palety
  Tailwind, np. docker→`sky`, php→`emerald`) — steruje akcentem CAŁEJ strony kursu (course/chapter/lesson)
  i karty na `/kursy`. Widoki liczą `$accent` (klasy utility) + `$accentHex` (poświata) z
  `CourseCoverImageService::accentColor()`. Klasy `text-{{ $accent }}-400` są dynamiczne → kolory akcentów są
  w **`safelist`** w `tailwind.config.js` (tablica `accentColors`); nowy kolor = dopisz tam + `npm run css:public`.
  Szczegóły w skillu **`course-cover`** (`.claude/skills/course-cover/SKILL.md`).
  **Jak tworzyć kursy/lekcje z plików**: użyj skilla **`course-writer`** (`.claude/skills/course-writer/SKILL.md`)
  — zawiera pełny format (struktura katalogów, frontmatter kursu/rozdziału/lekcji, konwencje treści, URL‑e).
  Przykład wzorcowy w repo: `resources/courses/laravel-basics/`. Nie trzeba żadnej komendy — commit plików i deploy.

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
- XML sitemap generuje `App\Services\SitemapService` (artykuły baza+`.md`, kategorie, kursy, `/mapa`, `/about-us`).
  **Tagów CELOWO nie ma w sitemapie** — patrz niżej.
- HTML „mapa strony": trasa `site.map` → `/mapa` (`HomeController::siteMap`) → `views_basic/sitemap.blade.php`.
- Wyszukiwarka bloga → `noindex`. Paginacja → self‑canonical z `?page=N`.
- **Strony tagów (WAŻNE — nie cofać):** wszystkie są `noindex, follow` i **poza sitemapą**.
  Tag to nawigacja, nie treść. Historia: `TagForArticleGenerator` generował do `tags.description`
  esej ~900 słów per tag, a `blog_tag.blade.php` go renderował → 256 doorway pages (65% z 393 URL‑i
  sitemapy), które kanibalizowały realne artykuły. Google odmówił indeksacji 203 z nich
  („discovered/crawled – currently not indexed") i indeksacja domeny spadła 70 → 48. Dlatego:
  generowanie `description` jest wyłączone, widok go nie renderuje, sitemap nie zawiera `/blog/tag/*`.
  Pilnuje tego test `tests/Feature/SitemapTagExclusionTest.php`. Kolumna `tags.description` została
  w bazie jako martwe dane (nic jej nie czyta).
- **IndexNow** (Bing/Yandex/Seznam): powiadamianie wyszukiwarek o zmianach URL. Klucz w `INDEXNOW_KEY`
  (env), plik weryfikacyjny hostowany dynamicznie pod `/{key}.txt` (trasa `indexnow.key`, `routes/web.php`
  przed łapaczami `/{articleSlug}`). Serwis `App\Services\IndexNowService` (guard: pusty klucz = no‑op,
  każdy ping w try/catch — nigdy nie wywala operacji na treści). Artykuły i kursy `.md` publikujesz
  commitem + deployem (brak runtime eventu), więc po deployu odpal komendę `php artisan indexnow:submit-sitemap`
  — wysyła batch wszystkich URL‑i z `sitemap.xml` (`--regenerate` = najpierw przebuduj mapę). Ten sam klucz
  musi być na produkcji co w pliku `/{key}.txt`.

## Checklist wdrożenia (produkcja)

1. `php artisan course:process --force` — regeneracja `content_html` lekcji nowym parserem (CommonMark).
   Bez tego stare lekcje mają zepsuty HTML (m.in. kursywa z `UPPER_CASE`).
2. Upewnić się, że **`public/assets/css/tailwind.css`** jest wdrożony (jest w repo — deploy = git pull; nie trzeba budować).
3. Po dodaniu nowych klas Tailwind: `npm run css:public` + commit przed deployem.
4. **IndexNow**: ustaw `INDEXNOW_KEY` na produkcji (ten sam co lokalnie). Po deployu z nowymi/zmienionymi
   artykułami lub kursami: `php artisan indexnow:submit-sitemap --regenerate` (zgłasza URL‑e do Bing).
   Sprawdź raz, że `https://oatllo.com/{INDEXNOW_KEY}.txt` zwraca klucz.
5. **Artykuły `.md`** są teraz w `resources/articles/` (commit + `git pull`) — upewnij się, że produkcja
   nie ma w `.env` starego `ARTICLES_MD_PATH=storage/app/articles` (domyślnie czyta `resources/articles`).
