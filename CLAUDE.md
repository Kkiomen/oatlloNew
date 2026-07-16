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

## Social media / Instagram (moduł `App\Services\Social`)

**Posty to pliki `.md` w `resources/social/`** — dokładnie ta sama architektura co artykuły i kursy:
commit + deploy, **zero bazy, zero migracji, zero crona**. `MarkdownSocialPostRepository` czyta katalog
(`config('social.path')`) i zwraca **DTO `SocialPost`** (NIE Eloquent — nie ma tabeli). **`publish_at`
i `status` NICZEGO NIE PUBLIKUJĄ** — to notatki człowieka; `status` filtruje tylko to, co bierze eksport.

**UWAGA — kolizja nazw:** `App\Models\InstagramPost` + tabela `instagram_posts` + `InstagramPostController`
to STARA, działająca galeria kafelków „follow me" trzymana w bazie. **To co innego. Nie łączyć, nie ruszać.**
Nowy moduł żyje wyłącznie pod `Social*`.

**Format pliku**: frontmatter (`type` = `carousel|quote|announce|story`, `topic`, `source`, `link`,
`formats`, `hashtags`, `caption`, `notes`) + slajdy rozdzielone **`<!-- slide -->`**.

**`caption` to TEKST DO WKLEJENIA, `notes` to instrukcja dla CIEBIE — i to nie jest kosmetyka.**
Story **nie ma na Instagramie pola podpisu**, więc `caption:` w story wyglądał na wolne miejsce i wylądowały
tam notatki produkcyjne („dodaj natywną ankietę przy wrzucaniu") — czyli w polu, które wychodzi do
`caption.txt` (plik „zaznacz i wklej") i do panelu recenzji jako podpis pod postem. Jedyne dwa miejsca w module,
które znaczą „to idzie w świat", pokazywały instrukcję dla autora. Dlatego `notes:` ma własny klucz: nigdy nie
wchodzi do `captionWithHashtags()` (jedyna metoda, której wynik trafia na Instagrama), ląduje w `post.json`
i w instrukcjach `social:publish`, a panel rysuje je POZA atrapą telefonu, na amber. Tam wpisujesz to, czego
renderer z definicji nie zrobi, bo to funkcja apki: ankiety, naklejki, cluster ze story.
**Wymóg niepustego `caption` przy `status: ready` wisi na `formats`, NIE na `type`**: `formats: [story]` =
nie ma gdzie wkleić, więc pusty podpis jest poprawny, a NIEpusty to WARNING (bezpiecznik na dokładnie ten błąd).
Kadr 9:16 z `formats: [post]` idzie do feedu i podpisu wymaga normalnie.

**`type` to KSZTAŁT slajdów, `formats` to CO PUBLIKUJESZ** — dwa różne pytania i dlatego dwa pola.
Jeden plik idzie w świat i jako karuzela w feedzie, i jako reel z tych samych slajdów (`social:video`):
`formats: [post, reel]`. Bez tego pola „reel z karuzeli" nie dałby się w ogóle zapisać. Dozwolone wartości
w `config('social.formats')` (post/story/reel/video; `reel` renderuje ten moduł, `video` NIE — to etykieta
na materiał nagrywany poza modułem). Brak pola => zestaw domyślny z typu (`config('social.default_formats')`:
story→story, reszta→post), więc **istniejące posty nie wymagały edycji ani migracji**. Nieznana wartość =
ERROR lintu: literówka (`reels`) wypadłaby z kalendarza BEZ ŚLADU, a dzień świeciłby pustką. Separatorem NIE jest `---` (nieodróżnialne
od frontmattera; `---` w treści ma zostać zwykłym `<hr>`) ani `===` (to setext H1 — zjada poprzednią linię).
W slajdzie pierwszy `##` = headline, reszta = treść. Pełny format w skillu **`social-writer`**.

**Render: HTML, NIE SVG — i to jest zamierzona rozbieżność wobec okładek.**
Okładki artykułów/kursów zostają czystym SVG (serwowane po HTTP, mają być małe i cache'owalne).
Grafiki social celują w RASTER (Instagram nie przyjmie SVG), więc i tak przechodzą przez headless —
a wtedy przeglądarka **łamie tekst naprawdę** i odpada cała ręczna matematyka z `CourseCoverImageService`
(`CHAR_WIDTH_RATIO`, `layoutTitle`, `wrap`). Font **Montserrat jest wklejany w base64**
(`EmbeddedFontProvider`), bo nie jest fontem systemowym Windows i dokument spod `file://` nie doczyta
zewnętrznego `url(...)` — bez tego headless podmieniłby go na font PROPORCJONALNY i popsuł layout.
Efekt uboczny: dokument nie ma podzasobów, więc eksport działa bez `php artisan serve` i bez sieci.
**Widoki social nie używają Tailwinda** → moduł NIGDY nie wymaga `npm run css:public`.

**Motywy technologii są WSPÓŁDZIELONE z kursami**: `App\Services\Theme\TechThemeResolver::fromText()`
czyta `config/course-covers.php` (12 logo istnieje tylko tam), a `CourseCoverImageService::resolveTheme()`
do niego deleguje. Dzięki temu kurs o Dockerze i post o Dockerze mają to samo logo i akcent.

**Ale FALLBACK już NIE jest wspólny — i to jest granica tego DRY.** Gdy nic nie pasuje, `fromText()` zwraca
motyw `default` z `course-covers`: czapkę absolwenta + etykietę **„Free course"**. Na okładce kursu poprawne,
na poście o cachingu czy code review — nieprawdziwe (etykieta ląduje w pigułce na grafice). Dlatego
`SocialImageService::theme()` pyta `TechThemeResolver::keyFromText()` (zwraca klucz albo **null**) i przy
`null` bierze `config('social.fallback_theme')`: **bez logo**, etykieta z `topic:` autora (fallback: „Oatllo"),
a **akcent ROTOWANY po `crc32(slug)`** z palety — bo inaczej wszystkie luźne posty byłyby emerald i feed
znudziłby się kolorem tak samo, jak wcześniej znudziłby się stylem. Rotacja dotyczy WYŁĄCZNIE treści bez
technologii, więc niczyjej marki nie podszywa. Te akcenty trafiają też pod `spotlight` (tekst NA nich), więc
każdy musi przejść WCAG z `inkFor()` — pilnuje tego test.

**`nginx` ma własny motyw i MUSI stać w configu przed `devops`** — tam `nginx` jest jednym ze słów kluczowych
(siatka bezpieczeństwa dla starych treści), a wygrywa PIERWSZY trafiony motyw. Przeniesienie go niżej cicho
odbiera nginxowi markę (firmowa zieleń + heksagon „N”) i przywraca ogólne okno terminala. Test to łapie.
Okładki ARTYKUŁÓW celowo NIE korzystają z resolvera — mają inny kształt motywu
(`filename`/`header`/`comment`/`footer`), więc wspólny resolver byłby fałszywym DRY.
**Dopasowanie jest PODCIĄGOWE i wrażliwe**: haystack MUSI być opakowany spacjami (keywordy `' oop'`,
`' js'`, `' ts'` udają granicę słowa), keyword `ai` trafia w środek wyrazu („av**ai**lable"), a `compose`
trafiał w `composer` (naprawione zawężeniem do `docker compose`). Gdy temat nie jest oczywisty — ustaw
jawny `topic:`.

**Pakiet stylów (10 skórek)**: `midnight` (bazowy), `paper` (jasny), `spotlight` (akcent na całej kanwie),
`terminal` (okno powłoki), `blueprint` (siatka techniczna), `editorial` (minimalizm + wielki numer),
`neon` (siatka horyzontu + poświata), `aurora` (mesh gradient), `card` (treść na karcie nad akcentem),
`brutalist` (jasny, gruba czarna rama, pełny cień).
Styl to **SKÓRKA CSS** (`resources/views/social/styles/{nazwa}.blade.php`) nakładana na wspólny layout,
a NIE osobny widok — inaczej byłoby 10 stylów × 4 typy = 40 widoków. Wszystko, co skórka zmienia, jest
zmienną CSS (`--bg`, `--ink`, `--muted`, `--rule`, `--code-bg`…). Config: `config/social-styles.php`.
Dobór jest **automatyczny i DETERMINISTYCZNY** (`SocialStyleResolver`): jawny `style:` → język kodu
(```bash → terminal) → **typ** → temat → rotacja po `crc32(slug)`. **Typ idzie PRZED tematem** — typ mówi
o formie, temat tylko o treści; przy odwrotnej kolejności zapowiedź kursu Dockera dostawała chrome
terminala gryzący się z jej własnym logo. Fallback to crc32, **nigdy rand()** — inaczej każdy eksport
dawałby inną grafikę. Podgląd: `php artisan social:styles [{slug}]`.

**Typ daje PULĘ stylów (`type_rotation`), nie jeden styl — i to jest sedno przy dużym wolumenie.**
Pojedyncza afinicja typu znaczyła, że KAŻDE story dostaje `spotlight`: przy 24 postach 12 było story,
czyli połowa feedu to był ten sam kafelek, a rotacja ich nawet nie dotykała (typ rozstrzyga wcześniej).
Pule dobrane pod formę: story = style, które krzyczą (bez `midnight` — najcichszy w pakiecie), quote =
style znoszące pustkę, announce = ciemne, bo ma logo jako bohatera (`spotlight` zabarwia kanwę akcentem
i logo się w nim gubi). Wybór z puli to ten sam `crc32(slug)`. Pusta pula => stary tryb (afinicja `types`).
**Hash się klastruje przy małej próbce** — to normalne, nie błąd; kto chce przybić wygląd, ustawia `style:`.
**Dopisanie stylu do rotacji/puli przetasowuje WSZYSTKIE posty** (crc32 % liczba pozycji), także te już
zaakceptowane w panelu — a że `.md` się nie zmienia, panel NIE poprosi o ponowną ocenę.

**Kolor tekstu na `spotlight` liczy się z luminancji WCAG** (`SocialImageService::inkFor()`), nie na oko:
akcenty bywają jasne (amber) i ciemne, a wyniki są nieoczywiste — na czerwieni Laravela ciemny tekst daje
5.66:1, a jasny tylko 3.71:1. Test pilnuje kontrastu ≥3:1 na KAŻDYM akcencie z pakietu.

**CZTERY MINY W SKÓRKACH (nie powtarzać):**
1. **Nigdy dyrektywa Blade'a wewnątrz komentarza CSS.** Blade ją rozwinie, wklejona sekcja stylów sama
   zawiera komentarze, a **komentarze CSS SIĘ NIE ZAGNIEŻDŻAJĄ** → pierwszy domykacz zamyka komentarz za
   wcześnie, osierocony domykacz to błąd parsowania, który **zjada całą regułę skórki**. Objaw: styl działa
   na jednych typach postów, a na innych nie. Używać `{{-- --}}`.
2. **Inline `code` w skórce zawsze przez `:not(pre) > code`.** Samo `.body code` ma wyższą specyficzność
   niż bazowe `.body pre code`, więc przebarwia kod WEWNĄTRZ bloku na kolor tła → blok kodu wychodzi jako
   pusty prostokąt.
3. **Zmieniasz geometrię `.stage` — przelicz `.story-footer`.** To jedyny element w pakiecie z
   `position: absolute` (`bottom: $padBottom - 60`) i kotwiczy się do `.stage`. W stylu `card` `.stage`
   TO KARTA odsunięta od krawędzi, więc bezpieczny margines story liczył się dwa razy i stopka wjeżdżała
   pod przycisk „Link in bio". Pilnuje tego test.
4. **Żadnych dekoracji na stałej wysokości (`bottom: X%`) przez całą kanwę.** `neon` miał linię horyzontu
   na 30% — na story szła PRZEZ „Link in bio", na quote muskała ostatnią linijkę i czytała się jak
   przekreślenie. Treść siedzi na różnych wysokościach zależnie od typu i kanwy, więc każdy stały procent
   w końcu w coś trafi. Miękki gradient/maska — tak, twarda linia — nie.

**Komendy**: `social:list`, `social:lint` (**bramka** — eksport odmawia budowy przy błędach),
`social:export [--html-only] [--out=]`, `social:styles` (podgląd pakietu), `social:publish` (szew pod przyszłe API).
Eksport → `storage/app/social-export/{slug}/` (`01.png..NN.png` + `caption.txt` + `post.json`).
**`storage/app/*` jest gitignorowane — wyeksportowanych grafik NIGDY nie commitujemy**, w gicie żyje tylko `.md`.

**Rasteryzacja**: headless Edge/Chrome, zero nowych zależności. **`--force-device-scale-factor=1` jest
obowiązkowe** (bez tego HiDPI robi z `--window-size=1080,1350` PNG 2160x2700), a każdy zrzut jest
weryfikowany `getimagesize()`. Binarka: `SOCIAL_BROWSER_BINARY` lub autodetekcja z `config/social.php`.

**Overflow tekstu to błąd AUTORSKI, nie renderu**: CSS zawija naprawdę, kanwa ma `overflow:hidden`, więc
nadmiar po prostu znika za krawędzią. Dlatego są budżety w loncie (hook ≤70, headline ≤55, treść ≤180,
kod ≤8 linii / **≤46 kolumn** — 46 jest policzone, nie zgadnięte). **Skracać tekst, nie zmniejszać fontu.**

**Zakaz `→`/`←`** (U+2192/U+2190 **nie ma** w unicode-range subsetu latin naszego woff2 — wypadają do fontu
systemowego w środku linii; lint = ERROR). `—`/`–` to tylko WARNING (są w foncie, ale kłócą się ze stylem domu).

**Podgląd** `/social/{slug}/preview` jest za flagą `SOCIAL_PREVIEW` i rejestrowany **warunkowo** — na
produkcji tras fizycznie nie ma w routingu. Eksport i tak nie potrzebuje HTTP.

**Panel akceptacji** `/social/review` (ta sama flaga `SOCIAL_PREVIEW`, `SocialReviewController`): jeden post
naraz, wyrenderowany jak w feedzie Instagrama (slajdy w `<iframe>` z prawdziwej kanwy, skalowane transformem —
to samo co podgląd, więc widać dokładnie to, co zrzuci rasteryzator). Czerwony → modal z powodem, zielony →
akceptacja. **Werdykt to plik `.md`** w `resources/social/reviews/{slug}.md` (`config('social.reviews_path')`)
— powód w CIELE pliku, nie we frontmatterze, żeby wielolinijkowy tekst od człowieka nie przechodził przez
escaping YAML-a. Zero bazy, commitowane jak posty. Katalog `reviews/` leży wewnątrz `resources/social`, ale
`MarkdownSocialPostRepository` czyta katalog **płasko** (`File::files`, nie `allFiles`) — recenzje nigdy nie
zostaną wzięte za posty (pilnuje tego test).

### Weryfikacja merytoryczna (`verified:` + skill `social-verify`)

**`social:lint` pilnuje FORMATU, weryfikacja pilnuje PRAWDY — i to są dwie różne bramki.**
Post może przejść lint idealnie (46 kolumn, wszystkie budżety) i twierdzić, że Xdebug słucha na 9000
albo że Anthropic ma endpoint embeddings. Lint nie widzi żadnej z tych rzeczy, a publiczność widzi
i komentuje. Claude czyta post RAZEM z artykułem źródłowym, sprawdza każde twierdzenie, kod, literówki
i spójność, po czym stempluje plik: `php artisan social:verify {slug} --verdict=approved --check=... --note=...`.
Komenda **niczego nie sprawdza** — utrwala werdykt; sprawdzanie to czytanie. Panel `/social/review`
pokazuje stan nad przyciskami akceptacji, więc człowiek wie, czy ktoś patrzył na fakty.

**Pieczątka niesie ODCISK TREŚCI, bo inaczej „zweryfikowane" znaczyłoby „zweryfikowane kiedyś,
w nieznanej wersji"** — dokładnie ten błąd, który werdykty człowieka rozwiązują `fingerprint`em.
Poprawka treści unieważnia weryfikację i panel świeci wtedy na CZERWONO („NIEAKTUALNA"), bo martwa
pieczątka jest groźniejsza niż jej brak: wygląda jak zielone światło.

**Odcisk liczy się z treści BEZ bloku `verified:` — i to jest nieoczywiste, ale konieczne.** Po pierwsze
byłby cykliczny (wpisanie odcisku zmienia plik, czyli i odcisk). Po drugie **`SocialReviewRepository::fingerprint()`
też go pomija**: człowiek ocenia TREŚĆ, nie cudzą adnotację o niej. Bez tego dopisanie weryfikacji do 150
postów skasowałoby wszystkie gotowe zielone werdykty. Dla plików bez bloku `strip()` jest tożsamością,
więc odciski sprzed tej zmiany zostały nietknięte (pilnuje tego test).
**Kolejność: Claude weryfikuje → człowiek akceptuje → publikacja.** Odwrotna jest nieszkodliwa, ale bez sensu —
panel istnieje po to, żeby POKAZAĆ werdykt recenzentowi.

**`fingerprint` (sha1 treści posta) domyka pętlę i to jest sedno**: werdykt dotyczy KONKRETNEJ wersji pliku.
Poprawka posta rozjeżdża odcisk → `SocialReviewQueue` znów pokazuje post do obejrzenia. Dlatego skill **NIE
kasuje ani nie edytuje pliku recenzji** — sama poprawka odsyła post do ponownej oceny. Bez odcisku
„zaakceptowane" znaczyłoby „zaakceptowane kiedyś, w nieznanej wersji". W kolejce nie ma `status: published`.

`/social/review` ma **2 segmenty**, więc mieści się we wzorcu łapacza `/{categorySlug}/{articleSlug}` — wygrywa
tylko dlatego, że blok tras social jest zdefiniowany **przed** łapaczami. Nie przenosić go na koniec pliku.

**Kalendarz** `/social/calendar` (`SocialCalendar` + `SocialCalendarEntry`): co i kiedy jest gotowe.
**Jednostką jest para (post × format), NIE post** — karuzela z `formats: [post, reel]` to tego samego dnia
dwie pozycje, bo to dwie osobne publikacje. Pokazuje **wyłącznie zaakceptowane** (zielony werdykt pasujący
do aktualnej treści): post „do poprawy" nie jest zaplanowany, tylko w robocie. Ale dziura w planie nie może
być niewidzialna, więc dzień pokazuje też licznik nieocenionych — inaczej dzień z samymi nieocenionymi
postami wyglądałby jak wolny. Post bez `publish_at` NIE dostaje dnia na siłę, tylko sekcję „Bez terminu"
(kalendarz ma pokazywać plan, a nie go zmyślać). `?m=YYYY-MM` i `?day=YYYY-MM-DD` bezstanowo w URL-u.
Nazwy miesięcy wymuszone na `pl` — panel jest narzędziem roboczym po polsku, a `APP_LOCALE` steruje serwisem.
Klik w pozycję otwiera **podgląd w modalu**, nie przenosi na inną stronę (przeglądając plan nie chcesz gubić
miesiąca). Pozycje w siatce zostają `<a href>` mimo to — ctrl/środkowy przycisk nadal otwiera kartę. Slajdy
buduje JS **dopiero po kliknięciu** i czyści je przy zamknięciu: w siatce bywa kilkadziesiąt pozycji, a każdy
slajd to osobny dokument w `<iframe>`. URL-e slajdów idą do JS jako **szablony z `route()`** (`__SLUG__`),
bo sklejanie ścieżek z palca rozjeżdża się przy pierwszej zmianie w `routes/web.php`.

Nawigacja w panelu: `?i=N` to **bezstanowy kursor** po kolejce (obejrzenie następnego posta BEZ werdyktu —
werdykt zdejmuje post z kolejki, więc bez kursora nie dałoby się nic odłożyć). Slajdy: klik w lewą/prawą
połowę kanwy, strzałki, przeciągnięcie. „Wszystkie slajdy obok siebie" to modal z `<iframe data-src>`
podstawianym przy otwarciu — bez tego wejście na panel ładowałoby każdy slajd DWA razy.

**TRZY MINY W PANELU (nie powtarzać):**
1. **Nigdy `disabled` na strefie klikania.** Wyłączony przycisk w Chromium nie generuje ŻADNYCH zdarzeń
   wskaźnika, a strefy `.nav` przykrywają po 42% kanwy → na pierwszym slajdzie martwa robiła się cała lewa
   połowa obrazka (klik nic, swipe zaczęty tam nie dostawał nawet `pointerdown`). Wygaszamy klasą `.off`
   + `aria-disabled`; `show()` i tak przycina zakres.
2. **`pointerup` dla swipe'a łapiemy na `window`, nie na kanwie.** Kanwa ma ~400px — przeciągnięcie zaczęte
   przy krawędzi kończy się poza nią i gest ginął.
3. **Flagę `dragged` kasować w `pointerdown`, nie w handlerze `click`.** Przeciągnięcie ze strefy na kanwę
   kończy się na innym elemencie, więc `click` W OGÓLE nie leci — flaga zostawała `true` i zjadała następne
   prawdziwe kliknięcie (objaw: „czasem nie reaguje").

Odczyt werdyktów: `php artisan social:review [--changes|--approved|--pending] [--json]` (`--changes --json` to
wejście dla skilla). Panel jest jedynym miejscem w module, które **zapisuje** pliki — i tylko w `reviews/`.

**Publikacja RĘCZNA** (`SocialPublisher`/`FolderPublisher`, `social:publish`) nadal działa i zostaje jako
ścieżka awaryjna: eksport + checklista do wklepania z ręki.

### Autopublikacja przez Zernio (`/api/cron` + `social:push`)

**Hosting obrazków był „prawdziwą robotą" i to się nie zmieniło — tylko ją zrobiliśmy.** Instagram (i przez
Graph API, i przez Zernio) nie przyjmuje plików, tylko **publiczne URL-e HTTPS**. PNG-i powstają lokalnie
z headless Edge, reele z Remotiona, a jedno i drugie jest gitignorowane (wyliczalne z `.md`) — **produkcja
nie ma grafik i nie umie ich zrobić**. Stąd trzy kroki: renderujesz lokalnie → `social:push {slug}` wysyła
pliki na serwer → serwer hostuje je pod `/storage/social/{slug}/01.png` → tick podaje te URL-e Zernio.

**Dlaczego nie upload do Zernio** (`/v1/media/presign`, oficjalnie wspierany): ich media leży w temporary
storage i **wygasa 7 dni od uploadu** („Make sure posts referencing an upload publish within 7 days of
uploading") — liczone od WGRANIA, nie od publikacji. Przy paczce planowanej na miesiąc wszystko po ~8. dniu
opublikowałoby się z martwym linkiem. Nasze URL-e nie wygasają.

**Dlaczego tick, skoro Zernio ma własny scheduler** (`scheduledFor`): bo wtedy plan istniałby w dwóch
miejscach i zmiana `publish_at:` w pliku wymagałaby synchronizacji z ich stanem. Tick godzinowy + `publishNow`
zostawia **`.md` jedynym źródłem prawdy** — zmiana terminu to commit, jak wszystko inne w tym repo.

**URL-i NIE MA w `.md`** — są wyliczane ze sluga i numeru slajdu (`SocialMediaStore`). Adres i tak wynika
z nazwy pliku, a edycja `.md` rozjeżdża `fingerprint` i odesłałaby wszystkie posty do ponownej recenzji.
Inny host = `SOCIAL_MEDIA_BASE_URL`, nie edycja 35 plików.

**Tick publikuje WYŁĄCZNIE zaakceptowane** (zielony werdykt pasujący do aktualnej treści — to samo, co
pokazuje kalendarz) i **wyłącznie pary (post × format)**, których `publish_at` minął. Post „do poprawy",
nieoceniony, bez terminu albo bez grafik na serwerze **nie idzie w świat** — każdy z tych przypadków ma test.

**PIĘĆ MIN W AUTOPUBLIKACJI (nie powtarzać):**
1. **Timeout NIE znaczy „nie poszło".** Żądanie mogło dojść i się wykonać, a urwać się dopiero odpowiedź.
   Ponowienie robi DUBLA na profilu, którego nie da się cofnąć. Dlatego `ConnectionException` => status
   `unknown` i para jest **zablokowana do decyzji człowieka**; ponawiamy tylko po jawnym błędzie HTTP
   (wtedy wiemy, że nic nie wyszło). Kasowanie pliku z `storage/app/social-published/` odblokowuje.
2. **`upload_max_filesize` PHP to domyślnie 2M, a reel waży ~3 MB.** To PIERWSZE, co się psuje. Gorzej:
   Laravel na błąd walidacji odpowiada **przekierowaniem** (302), klient idzie za nim, dostaje 200 ze strony
   głównej i **odrzucona wysyłka melduje sukces**. Dlatego `social:push` wysyła `Accept: application/json`,
   nie idzie za przekierowaniami i traktuje odpowiedź bez `url` jako błąd. Na serwerze podnieś
   `upload_max_filesize`, `post_max_size` ORAZ `client_max_body_size` w nginxie.
3. **Endpoint uploadu wącha MAGIC BYTES, nie nazwę i nie Content-Type** — jedno i drugie pisze klient, a pliki
   lądują w katalogu serwowanym publicznie. Nazwę skleja serwer z numeru slajdu, slug idzie przez `Str::slug`.
   Bez tokenu **trasy nie ma w routingu** (jak podgląd za `SOCIAL_PREVIEW`) — wyłączone znaczy „nie istnieje".
4. **`/api/cron` jest publicznym GET-em i taki zostaje** (nie psujemy działającego n8n), ale część socialowa
   wymaga `SOCIAL_CRON_TOKEN`. Artykuły mogą być otwarte, bo obcy strzał co najwyżej przyspieszy naszą własną
   publikację; wysyłka na CUDZĄ platformę pali limity API i zostawia publiczny ślad.
5. **`max_per_tick` (domyślnie 3) i `grace_minutes` (180) to bezpieczniki, nie kaprys.** Paczka z przeszłymi
   datami bez limitu wyplułaby na profil wszystko naraz, a post przegapiony przez kilkudniową awarię wyszedłby
   po naprawie o 4 nad ranem. Nadmiar jest **raportowany**, nie gubiony po cichu.

**Stan wysyłek to pliki w `storage/app/social-published/{slug}__{format}.json`**, nie tabela i nie `status:`
w `.md`: plik na produkcji jest kopią z gita, więc zapis do niego rozjechałby working tree i pierwszy
`git pull` by go cofnął albo wywalił konflikt. `storage/app` przeżywa deploy, jest gitignorowane i nie wymaga
migracji — a testy social dalej nie potrzebują bazy.

**Komendy**: `social:export {slug}` → (`social:video {slug}` dla reela) → `social:push {slug}`.
Konfiguracja: `SOCIAL_MEDIA_TOKEN` (serwer), `SOCIAL_PUSH_TOKEN` + `SOCIAL_PUSH_TARGET` (twój laptop),
`ZERNIO_API_KEY` + `ZERNIO_ACCOUNT_ID`, `SOCIAL_AUTO_PUBLISH=true`, `SOCIAL_CRON_TOKEN`.
**Licencja/koszt Zernio: pierwsze 2 podpięte konta za darmo.**

Skille: **`social-post`** (orkiestrator), `social-ideas`, `social-writer`, `social-carousel`, `social-export`,
**`social-verify`** (sprawdza fakty/kod/literówki i stempluje `verified:` ZANIM post zobaczy człowiek),
**`social-review`** (bierze posty odesłane w panelu do poprawy i je poprawia — „przejrzałem zaplanowane posty
i teraz je opracuj").

### Story „nowy artykuł" (`social:article-stories` + skill `social-article-story`)

**Osobne story ogłaszające KAŻDY publikowany artykuł — „Słuchajcie, jest nowy artykuł".** Artykuły `.md`
publikują się ~3/tydzień z `published_at` w przyszłości, więc `php artisan social:article-stories` robi jedno
story na każdy **zakolejkowany** artykuł (data w przyszłości), datowane na dzień jego publikacji → naturalnie
3–4 dodatkowe story tygodniowo. To zwykłe `.md` w `resources/social/` (commit + deploy, zero bazy); generator
**niczego nie publikuje** — story idą przez lint, panel `/social/review` i autopublikację jak każdy post.

**Plik: `story-new-{slug-artykułu}.md`** — `type: story`, `formats: [story]`, `style: announce-article`,
`title`/`link` WYLICZONE z artykułu (`link` na `brand.domain` = `oatllo.com`, **nie** z `APP_URL` — lokalnie
to Herd), `publish_at` = `published_at` artykułu, `status: ready` + pieczątka `verified: approved` (tytuł/link
są z artykułu, więc fakt jest poprawny z definicji; odcisk liczy ta sama metoda co `social:verify`, więc panel
świeci zielono). **Idempotentny**: istniejącego story NIE nadpisuje (bez `--force`) — ręczne poprawki
przeżywają ponowne uruchomienie, a korekta artykułu nie rusza story (dwa osobne pliki). Flagi: `--all`
(też opublikowane), `--limit=N`, `--dry-run`.

**Skórka `announce-article`** (`resources/views/social/styles/announce-article.blade.php`) to skin CSS ze
STAŁYM banerem „New on the blog" (pseudo-element na `.stage`) jako znakiem rozpoznawczym serii; akcent i logo
zmienne per technologia (kolor mówi O CZYM jest artykuł). Baner używa `var(--accent-ink)` (WCAG), więc jest
czytelny na każdym akcencie. Stosowana **wyłącznie** przez jawne `style:` — **CELOWO nie ma jej w `rotation`
ani `type_rotation`** (dopisanie tam przetasowałoby `crc32 % liczba` style wszystkich innych postów). Teksty:
`config('social.article_story')` (`kicker`, `intro`, `slug_prefix`).

**NIE MYLIĆ z pollowymi story `story-{skrót}`** (np. `story-cors`, `story-enums`): to inny gatunek — prowokacyjne
pytanie + natywna ankieta + reshare karuzeli, do ZAANGAŻOWANIA, nie do ogłaszania artykułu. Prefiks `story-new-`
trzyma oba zestawy rozłączne i gwarantuje brak kolizji nazw. Nie łączyć.

### Reels / wideo (Remotion) — `php artisan social:video {slug}`

**Reel to NIE nowy byt — to ten sam post `.md`, tylko w ruchu.** Remotion dostaje **dokładnie te dokumenty
HTML**, które idą na PNG (`SocialImageService::renderPost()`, bit w bit — pilnuje tego test), i dokłada
**wyłącznie animację**. Wygląd ma jedno źródło (Blade). Alternatywa — odtworzenie designu w React/TSX —
byłaby forkiem systemu: 10 skórek × 4 typy w dwóch miejscach, rozjeżdżających się przy każdej zmianie skórki.
Dlatego wstrzykujemy żywy DOM (`dangerouslySetInnerHTML`), a nie gotowe PNG-i: po PNG dałoby się animować
tylko całe slajdy, a po DOM-ie animują się **pojedyncze elementy** (`.headline`, `.body > *`, `.underline`).

**Podział ról jest sztywny: PHP wie wszystko o TREŚCI, Remotion tylko o RUCHU.** Ile slajdów, jaka skórka,
jaki akcent, jak długo trzyma się slajd — to `ReelStager` (`app/Services/Social/Video/`), bo inaczej decyzje
o treści uciekłyby do TSX-a, gdzie nie sięga ani `social:lint`, ani testy PHP. Kontrakt to
`social-video/public/slides/{slug}/reel.json` + `NN.html` (gitignorowane — **wyliczalne z `.md`**, jak PNG-i).

**Długość slajdu liczy się z objętości treści** (`config('social.video.timing')`), nie na sztywno: slajd
z kodem czyta się dłużej niż hook, więc stała długość albo urywałaby kod, albo trzymała pusty hook.
`per_code_line` > `per_word`, bo kod się **skanuje**, nie czyta. Bezpieczniki: 75 kl. (2.5 s) / 210 kl. (7 s).

**Kadr: slajd 4:5 ZOSTAJE 4:5, na podkładzie.** Kusi, żeby rozciągnąć `.canvas` do 1920 (flexowy `.stage`
sam by się rozłożył), ale moduł jest kalibrowany pod 4:5 — budżety lintu liczono dla tej kanwy, a
`.story-footer` kotwiczy się do geometrii `.stage` (patrz mina #3 w skórkach). Slajd ma **pełną szerokość**
kadru, więc typografia na telefonie czyta się tak jak w feedzie — letterbox dokłada tylko pasy, nie zmniejsza.
Slajd siedzi **wyżej niż środek** (`SLIDE_TOP = 170`): dolne ~390 px zasłania UI Instagrama.
**`story` jest wyjątkiem** — ma natywnie 1080x1920, więc leci full-bleed, bez podkładu.

**Podkład to TEN SAM HTML** rozciągnięty do 1920 z ukrytą treścią — tło maluje sama skórka, razem ze swoimi
zmiennymi, więc jasne skórki zostają jasne i nawet `spotlight` (tłem jest `var(--accent)`) trafia tu bez
linijki w manifeście. Poświaty w podkładzie są **wygaszone**: ma inną wysokość niż kanwa, więc nie trafiłyby
w te ze slajdu i zaznaczyłyby szew.

**TRZY MINY W REELACH (nie powtarzać):**
1. **Nie przejmować `.bar` na pasek postępu.** Element jest na górze kanwy i ma dokładnie tę rolę, ale skórki
   traktują go jak swoją dekorację i **biją specyficznością** `.canvas.style-x .bar` (0,3,0): `aurora` maluje
   go własnym gradientem, **`card` w ogóle go chowa**, `editorial` ścina do 3px, `brutalist` rozdyma do 18px.
   Postęp rysuje **sam Remotion**, w pasie letterboxa — tam nie sięga żaden CSS skórki. Tor paska jest
   z akcentu na niskiej alfie, **nie z bieli**: na `paper`/`brutalist` biały byłby niewidoczny.
2. **Reguły animacji scopować numerem slajdu** (`.reel-slide-{i}`). Wstrzyknięty `<style>` jest **globalny**,
   a `useCurrentFrame()` liczy się względem `<Sequence>` — przy dwóch slajdach na ekranie naraz selektor
   `.reel-slide` trafiłby w OBA i wychodzący animowałby się od nowa klatkami wchodzącego.
3. **Czekać na `document.fonts.ready`** (`delayRender`). Font jest wklejony w HTML jako base64 `@font-face`;
   bez czekania pierwsze klatki wyszłyby fontem zastępczym o innych metrykach — ta sama mina, przed którą
   po stronie PNG chroni `EmbeddedFontProvider`.

`HtmlInCanvas` z docsów Remotiona **nie jest do tego** — to post-processing na canvasie i wymaga Chrome 149+
z włączoną flagą. Żywy DOM działa zwyczajnie, bo Remotion *jest* przeglądarką.

**Remotion to osobny projekt Node w `social-video/`, NIE zależność Laravela.** Render jest wyłącznie lokalny,
jak PNG-i — produkcja nie ma `node_modules` i mieć nie będzie (deploy = `git pull`). Tailwinda w nim NIE MA
(scaffold dokłada go nawet przy `--no-tailwind` — wyrzucony ręcznie), więc moduł nadal nigdy nie wymaga
`npm run css:public`. **Licencja Remotiona: darmowa dla osób prywatnych i firm do 3 osób**, powyżej płatna.

**Reel jest NIEMY z premedytacją — muzykę dokłada się w Instagramie, przy wrzucaniu.** Biblioteka audio IG jest
licencjonowana na użycie w aplikacji (nie ma czego atrybuować ani co wyciszać), a dźwięk to **powierzchnia
odkrywania**: utwór wybrany w apce podpina Reela pod stronę tego utworu, czego wypalona ścieżka nie kupuje w ogóle.
Doszywanie mp3 do renderu nie jest też „flagą w configu": trzeba `<Audio>` w `Reel.tsx`, pole `music` w manifeście
(muzyka to TREŚĆ, więc wybiera ją `ReelStager`) i licencji, która przechodzi komercyjnie — „no copyright" na
Pixabay czy w YouTube Audio Library to konkretna licencja z warunkami, nie domena publiczna. Do tego plik audio,
w odróżnieniu od PNG-ów i slajdów, **nie jest wyliczalny z `.md`** — musiałby wjechać do repo jako binarka.
Wracać do tematu tylko, gdy ten sam plik ma iść na YouTube Shorts / TikToka, gdzie nie ma licencjonowanej
biblioteki w apce.

`social:video {slug}` = lint (ta sama bramka co eksport) → wsad → render → `storage/app/social-export/{slug}/reel.mp4`.
`--stage-only` buduje sam wsad pod podgląd w Studiu (`cd social-video && npx remotion studio`, slug w panelu
propsów). Pierwsze uruchomienie: `cd social-video && npm i`.

### Clip / narrowane wideo (`php artisan clip:render {slug}`) — moduł `App\Services\Clip`

**Clip to INNY gatunek wideo niż reel — i to jest sedno, nie pomylić.** Reel to niema karuzela w ruchu
(muzyka dokładana w IG, zero bespoke animacji, timing z objętości treści). Clip to **narrowany explainer**
pod TikToka/Shorts/Reels: scenariusz → narracja ElevenLabs → Remotion składa animowane sceny zsynchronizowane
z głosem → napisy karaoke + SFX → MP4 1080x1920. Odwrotność reela na każdym punkcie, więc to **drugi,
niezależny pipeline OBOK niego**, nie przeróbka `Reel.tsx`. Pełna architektura: `docs/narrated-video-architecture.md`.

**Scenariusze to pliki `.md` w `resources/clips/`** — ta sama DNA co posty/kursy/artykuły: commit + deploy,
zero bazy, zero crona. `MarkdownClipRepository` → DTO `Clip` + `ClipScene[]`. Format: frontmatter globalny
(`slug`, `title`, `topic`, `voice`, `source`, `music`, `platforms`, `caption`, `hashtags`) + **sceny
rozdzielone `<!-- scene -->`, każda to blok YAML** (nie Markdown — scena to spec animacji, nie proza;
`highlight: [3]`, `items: [...]`, `code: |` idą naturalnie w YAML). Separator `<!-- scene -->` jak
`<!-- slide -->` w postach — NIE `---` (nieodróżnialne od frontmattera).

**Zasada nadrzędna: SCENA = (narracja, wizual). Każda scena MA `narration`** — to jednocześnie tekst do
ElevenLabs, **wyznacznik długości sceny** (scena trwa tyle, ile jej audio) i źródło napisów. To kręgosłup
synchronizacji. Brak `narration` = ERROR lintu (scena bez głosu nie ma z czego liczyć długości ani napisów).

**Biblioteka scen (8 typów) w `social-video/src/clip/scenes/`**: `title`, `code-reveal`, `bullets`,
`statement`, `compare`, `terminal`, `callout`, `outro`. Nowa scena = komponent + wpis w `registry.ts` +
typ w `config('clip.scene_types')` (test `ClipRegistryTest` pilnuje zgodności obu list — nieznany typ w
scenariuszu = ERROR lintu, „dodałem typ do configu, zapomniałem komponentu" łapie `ClipRegistryTest`).
Sceny to natywny React (nie wstrzyknięty Blade jak w reelu) — dlatego mają własny lekki `theme.ts`
(neutral-950 + akcent + Montserrat). To druga, świadoma powierzchnia designu; zgodność wizualna, nie
współdzielony kod (kinetic type nie da się wyrenderować z Blade'a slajdów).

**Audio to ARTEFAKT, nie źródło** (jak PNG-i i reele): narracja wyliczalna ze scenariusza, ale kosztuje API,
więc cache po `sha1(provider+głos+tekst)` w `storage/app/clip-audio` (gitignorowane). Zmiana narracji = nowy
hash = regeneracja; provider wchodzi do hashu, żeby zmiana mock→elevenlabs unieważniła ciszę. `social-video/public/clips`
też gitignorowane (wsad wyliczalny). W gicie żyje tylko `.md`.

**TTS za interfejsem `TtsProvider` (driver w `config('clip.tts.driver')`):**
- `MockTtsProvider` (domyślny) — **cisza WAV o oszacowanej długości** (słowa÷tempo) + syntetyczne timestampy.
  Dzięki temu **cały pipeline renderuje BEZ klucza ElevenLabs** — dostajesz niemy film z poprawnym timingiem
  i napisami. Głos podmieniasz później zmieniając driver, reszta się nie rusza.
- `ElevenLabsTtsProvider` — realny głos przez endpoint `with-timestamps` (audio + wyrównanie znakowe →
  słowa). Guard: pusty `ELEVENLABS_API_KEY` = wyjątek z jasnym komunikatem.

**MINY W REELACH PRZENOSZĄ SIĘ NA CLIPY** (patrz sekcja reeli wyżej): reguły animacji scopować numerem sceny
(`.clip-scene-{i}`; wstrzyknięty `<style>` jest globalny), czekać na `document.fonts.ready` (`useFonts` +
`delayRender`; font base64), żadnych dekoracji na stałej wysokości przez cały kadr (dolne ~470px zasłania UI
platformy — `SceneFrame` trzyma treść w górnych 1180px, napisy w pasie 1210+, jak `SLIDE_TOP` w reelu).
**Dodatkowo: font MONO MUSI mieć ligatury OFF** (`MONO_STYLE`) — inaczej Cascadia/Fira renderują `->` jako
`→` (fałszuje kod I jest to zakazany glif spoza subsetu). Font kodu skaluje się do najdłuższej linii
(`codeFontSize`), żeby nie wyjechać za panel.

**Napisy karaoke** (`Captions.tsx`, główny driver retencji na TikToku) — aktualne słowo akcentem, z
timestampów; na mocku słowa rozłożone równo, więc działają i bez głosu. Czas liczony z offsetem `leadIn`
(głos startuje kilka klatek po scenie, żeby wizual wjechał).

**Motyw** (akcent + logo) z `TechThemeResolver` — współdzielony z kursami i postami (kurs o Dockerze i clip
o Dockerze mają to samo logo). Slug wchodzi do haystacka, więc uważaj na podciągi (`clip`→devops przez `cli`).

**Komendy**: `clip:lint {slug}` (**bramka** formatu — nieznany typ sceny / brak narracji / glif spoza fontu
= ERROR), `clip:tts {slug} [--force]` (grzeje cache narracji), `clip:stage {slug}` (manifest `clip.json` +
audio do `public/clips`), `clip:render {slug} [--stage-only] [--skip-lint]` (orkiestrator: lint → stage →
render → `storage/app/social-export/{slug}/clip.mp4`). Config: `CLIP_TTS_DRIVER`, `ELEVENLABS_API_KEY`,
`CLIP_VOICE_EN`. **SFX** (`config('clip.sfx')`) i **muzyka** — okablowane (stager + `Clip.tsx`), ale wymagają
plików CC0 w `social-video/public/sfx`; puste = clip gra bez efektu (WARNING lintu). **Publikacja: render
MP4 + ręczny upload** — TikTok/YT API (OAuth) świadomie poza v1; Zernio jest tylko dla IG.

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
- **Stare artykuły SEO‑first wycofane (`config/articles.php` → `retired_slugs`, WAŻNE — nie cofać):**
  44 artykuły z **bazy**, jedyne, co stało na produkcji przed harmonogramem `.md`. Pisane pod algorytm:
  marka wciśnięta w treść („Oatllo, a name synonymous with digital business continuity" — w tekście
  o backupach), fraza z tytułu wepchnięta w zdanie, CTA w każdym meta description. **Nie jest to kwestia
  gustu: wg GSC (2026‑07‑14) dają RAZEM 9 kliknięć z 94 na całej domenie, a 88% ruchu robi kurs PHP —
  pisany bez żadnego SEO.** Kanibalizowały też własnych następców (`master-php-enums-use-cases-tips`
  vs `php-enums-complete-guide.md`). Wygaszone (`is_published = false`), **nie skasowane** — nie mają
  plików `.md`, więc nie da się ich odtworzyć z gita.
  **MINA — `is_published = false` NIE ZNACZY TU „UKRYTY", tylko „opublikuj mnie jak najszybciej".**
  `CronController::publishDueArticles()` publikuje wszystko z `is_published = false` + datą w przeszłości,
  a te artykuły mają daty sprzed miesięcy. Dlatego lista ma **dwóch konsumentów i obaj są konieczni**:
  tick ją wygasza (idempotentnie) **i** pomija ją przy publikacji (`whereNotIn`). Bez tego drugiego
  `articles:retire-legacy` melduje sukces, po którym artykuły **wracają na stronę w ciągu godziny**.
  Pilnuje tego `tests/Feature/RetireLegacyArticlesTest.php` (test oblewa po usunięciu `whereNotIn`).
  Przywrócenie artykułu = usuń slug z configu, deploy, potem `--restore` (samo `--restore` nie wystarczy).
  **`site-map` NIE jest na liście** — wygląda jak slug artykułu i jest w sitemapie, ale to prawdziwa mapa
  strony (trasa `site.map`, `/mapa` na nią przekierowuje); wyłączenie zabrałoby nawigację. Też ma test.
- **Tempo publikacji: ~3 artykuły/tydzień i nie przyspieszamy.** 101 plików `.md` jest rozłożonych po
  ~13/miesiąc do lutego 2027 (`published_at` we frontmatterze — bez crona, bez bazy). Google nie karze za
  tempo, tylko za jakość, więc 3/tydzień nie jest spamem. Ale wąskim gardłem nie jest prędkość pisania
  (artykuły są gotowe) — tylko to, że **domena ma 48 z 257 stron w indeksie**. Dosypywanie URL‑i tego nie
  naprawi. Za to **przyspieszenie** mogłoby zaszkodzić: zrzut 97 artykułów na stronie, która miała ich 45,
  to skok wolumenu ×20. Wrócić do tematu dopiero, gdy nowe artykuły zaczną wchodzić do indeksu w kilka dni.
  **Kursy to inna sprawa — dodajemy w całości**: kurs to spójna hierarchia (kurs→rozdział→lekcja), Google
  oczekuje kompletu, a dowód jest własny — 86 podstron kursu PHP dodanych naraz to 88% ruchu domeny.
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
6. **Autopublikacja na Instagram** (jednorazowy setup na produkcji):
   - `php artisan storage:link` — bez tego `/storage/social/...` zwraca 404, a Zernio wymaga URL-a, który
     oddaje **bajty** z poprawnym Content-Type. Sprawdź: `curl -I https://oatllo.com/storage/social/{slug}/01.png`.
   - `upload_max_filesize` i `post_max_size` w PHP **≥ 8M** oraz `client_max_body_size 8m;` w nginxie —
     domyślne 2M odrzuci każdego reela (~3 MB).
   - `.env`: `SOCIAL_MEDIA_TOKEN` (ten sam sekret co lokalny `SOCIAL_PUSH_TOKEN`), `ZERNIO_API_KEY`,
     `ZERNIO_ACCOUNT_ID` (z `GET /v1/accounts`), `SOCIAL_CRON_TOKEN`, `SOCIAL_AUTO_PUBLISH=true`.
     **Dopóki `SOCIAL_AUTO_PUBLISH` nie jest `true`, tick nie dotyka Instagrama** — wdrożenie kodu samo
     z siebie nigdy nie zacznie publikować.
   - n8n: do istniejącego godzinowego strzału w `/api/cron` dołóż nagłówek
     `Authorization: Bearer {SOCIAL_CRON_TOKEN}`. Bez niego artykuły i sitemap działają jak dziś,
     a social zwraca `unauthorized` (czyli: nic się nie stanie po cichu).
   - Lokalnie, przed każdą paczką: `social:export {slug}` → `social:video {slug}` (jeśli reel) → `social:push {slug}`.
