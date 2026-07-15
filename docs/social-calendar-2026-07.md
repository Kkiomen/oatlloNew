# Instagram — plan publikacji 16.07 – 15.08.2026

Pierwszy pełny miesiąc według playbooka **`.claude/skills/social-growth`**. Dowody i źródła każdej
liczby: `.claude/skills/social-growth/references/research.md`.

**Stan: NAPISANE.** Wszystkie 35 plików ma `status: ready`, `social:lint` przechodzi **0 błędów,
0 ostrzeżeń**. Publikacja jest ręczna: `php artisan social:export {slug}` → wrzucasz PNG-i sam.

## ⚠️ Haki, które zmieniły się względem pierwotnego planu — i dlaczego

**Za każdym razem powód był ten sam: draft miał liczbę, której artykuł źródłowy nie potwierdza.**
Zasada „nigdy nie zmyślaj liczby" wygrywa z zasadą „trzymaj się planu".

| Post | Plan (draft) | Rzeczywistość | Powód |
|---|---|---|---|
| `eloquent-n1` | „200 queries" | **„51 queries"** | Artykuł liczy 50 postów -> 51 zapytań. 200 było wzięte z sufitu. |
| `database-indexing` | „4 seconds at 100,000" | **„Fine on 100 dev rows. A full scan at 100 million."** | Artykuł **nie zawiera żadnego pomiaru czasu** ani liczby 100 000. „4 sekundy" byłoby zmyślone. |
| `docker-image-size` | „1.2 GB" | **„1.4 GB"** | Artykuł mówi 1.4 GB; tabela before/after startuje z ~1.3 GB. `1.2` nie pada nigdzie. |
| `sql-explain` | „You added the index. The query still ignores it." | **„Eleven seconds in prod. 40ms on your laptop."** | **Kolizja treści**: karuzela o indeksach (22.07) już zużywa `YEAR()` i `EXPLAIN ANALYZE`. Przestawione na czytanie planu (estymacja vs rzeczywistość, `ANALYZE TABLE`). |
| `php-match` | „'1' == 1" | **„switch thinks '1e1' and 10 are the same thing"** | Oba prawdziwe, ale `'1e1' == 10` to przykład wiodący artykułu i jest mocniejszy. |
| `form-request` | „controller is 60 lines, 40 of them validation" | **„Thirty lines of validate(). Then again in update()."** | Artykuł mówi o 400-liniowych kontrolerach i 30 liniach `validate()`. 60/40 było zmyślone. |
| `database-mistakes` | „second address" | **„Your invoice totals are off by a cent and nothing threw"** | Mocniejszy, prawdziwy przykład z artykułu. |
| `announce-php-course` | „SPRAWDZIĆ W REPO" | **7 rozdziałów** | Policzone: `resources/courses/php/` ma 7 katalogów. |

**Draftowe „SPRAWDZIĆ W REPO" zadziałało dokładnie tak, jak miało.** Zamiast wpisać prawdopodobną
liczbę, draft wymusił sprawdzenie — i liczba okazała się inna, niż podpowiadała intuicja (7, nie 8).

---

## Po co ten rytm — w jednym akapicie

Nie ma czegoś takiego jak „sygnał aktywności". Wolumen działa z prozaicznego powodu, który Mosseri
podaje wprost: *„more people discover you, more people share your content, and there's more content to
be discovered"*. Kara za ciszę istnieje, ale jest **malutka (−0,08 SD)**. Buffer (2,1 mln postów,
**account fixed-effects** — jedyny model odporny na mylenie skutku z przyczyną) pokazuje, że **zasięg
NA POST rośnie** z częstotliwością: 3-5/tydz. **+12%**, 6-9/tydz. **+18%**, 10+/tydz. **+24%**.
Kanibalizacja zasięgu **nie istnieje** — to folklor.

Ten plan siedzi w przedziale **6-9 publikacji tygodniowo**. Nie dlatego, że więcej = lepiej bez końca,
tylko dlatego, że **największy przeskok jest między 1-2 a 3-5**, a dalej zyski płasko rosną. Powyżej
5/tydz. ograniczeniem jest jakość i wypalenie, **nie algorytm**.

---

## Rytm tygodnia

| Dzień | Co | `formats` | Dlaczego |
|---|---|---|---|
| **Pon** | karuzela | `[post, reel]` | Reel = zasięg (2,3× karuzeli poniżej 500K obs.) |
| **Wt** | karuzela | `[post]` | Sam feed — nie każda karuzela musi iść w ruch |
| **Śr** | karuzela | `[post, reel]` | |
| **Czw** | statyk (`quote`/`announce`) | `[post]` | **Limit 1/tydzień** — statyki umierają (−22% zasięgu r/r) |
| **Pt** | karuzela | `[post, reel]` | |
| **Sob/Nd** | tylko stories | — | Feed odpoczywa |

**= 5 postów w feedzie + 3 reele = 8 publikacji/tydzień + stories w 3 dni.**

**Godzina: 19:00. Jedna, na stałe, i nie wracamy do tematu.** Buffer (9,6 mln postów) **nie publikuje
rozmiaru efektu** — a Metricool ma „najlepsze dni", które **przeskoczyły między dwiema edycjami tego
samego badania**. Optymalizacja godziny to ujemny zwrot. Wieczór jest rozsądnym priorem i tyle.

⚠️ **Karuzela z `formats: [post, reel]` to dwie publikacje TEGO SAMEGO DNIA** (tak liczy je
`SocialCalendar`). Człowiek wrzuca karuzelę o 19:00, a reela później tego dnia — `publish_at` to jedno
pole i notatka dla człowieka, nic się samo nie publikuje.

---

## Stories: moduł renderuje klatkę, ankietę dodaje człowiek

**To jest realne ograniczenie, nie niedoróbka.** `story` wymaga **dokładnie 1 slajdu**
(`SocialPostType::slideRange()` → `[1,1]`), a **ankiet, pytań i quizów NIE DA SIĘ wyrenderować do
PNG** — to natywne naklejki Instagrama, dodawane w aplikacji przy wrzucaniu.

Dlatego jedna pozycja „story" w tym planie = **jedna firmowa klatka-kotwica z repo** + **2-3 klatki
natywne**, które człowiek dokłada w aplikacji:

1. klatka z repo (wyrenderowany PNG 1080x1920),
2. **ankieta albo pytanie** (naklejka IG) — to jest cały sens,
3. podbicie karuzeli z tego tygodnia (reshare) — nie kanibalizuje feedu, Mosseri: *„it's not going to
   meaningfully change your reach overall"*.

**Po co klaster:** klatka 1 ma **najgorszy exit rate w całym formacie (23,8%)**, a **zasięg szczytuje
przy klatkach 6-13**. Pojedyncza samotna klatka płaci pełny podatek wyjścia i nigdy nie dochodzi do
strefy szczytu. **Stories to powierzchnia relacji, nie zasięgu** — Buffer wyrzucił je z badania wzrostu
*„due to their limited role in audience growth"*. Ale **odpowiedź na story to DM**, a DM to relacja,
z której później bierze się *send*.

**Czego NIE robimy:** stories, które są skróconą karuzelą („200 queries. Nowy post. Swipe up"). Tak
wyglądało 12 z 24 skasowanych postów. To megafon. Story ma **pytać**.

---

## Kalendarz

Legenda: **R** = idzie też jako reel (`formats: [post, reel]`) · **S** = statyk · **📱** = story

### Tydzień 0 — start (16-19.07)

| Data | Dzień | Slug | Typ | Temat | Hook |
|---|---|---|---|---|---|
| **16.07** | Czw | `eloquent-n1-carousel` | carousel **R** | laravel | Your Blade loop is running 51 queries |
| **17.07** | Pt | `laravel-419-carousel` | carousel **R** | laravel | Your form worked. Then it returned 419. |
| **19.07** | Nd | `story-week-0` | 📱 | laravel | *Ankieta: N+1 czy 419 — co ugryzło cię ostatnio?* |

**Start mocny i celowo:** dwa najczęściej przeżywane bóle Laravela, oba z reelem. Pierwszy tydzień jest
niepełny (start w czwartek), więc zamiast rozcieńczać — dwa najsilniejsze haki, jakie mamy.

### Tydzień 1 (20-26.07)

| Data | Dzień | Slug | Typ | Temat | Hook |
|---|---|---|---|---|---|
| **20.07** | Pon | `php-autoload-carousel` | carousel **R** | php | The class is right there. PHP says it doesn't exist. |
| **21.07** | Wt | `cors-error-carousel` | carousel | javascript | Your API works in Postman and dies in the browser. |
| **21.07** | Wt | `story-cors` | 📱 | javascript | *Ankieta: Postman OK, przeglądarka nie — ile razy?* |
| **22.07** | Śr | `database-indexing-carousel` | carousel **R** | database | Fine on 100 dev rows. A full scan at 100 million. |
| **23.07** | Czw | `announce-docker-basics` | **S** announce | docker | New free course: Docker Basics (8 rozdziałów) |
| **23.07** | Czw | `story-docker-course` | 📱 | docker | *Pytanie otwarte: co blokuje cię w Dockerze?* |
| **24.07** | Pt | `docker-image-size-carousel` | carousel **R** | docker | Your PHP image is 1.4 GB. It doesn't need to be. |
| **26.07** | Nd | `story-week-1` | 📱 | docker | *Ankieta: jak duży jest twój obraz PHP?* |

### Tydzień 2 (27.07-02.08)

| Data | Dzień | Slug | Typ | Temat | Hook |
|---|---|---|---|---|---|
| **27.07** | Pon | `commit-messages-carousel` | carousel **R** | git | Your git log reads: fix, fix2, final fix, actually fix. |
| **28.07** | Wt | `php-execution-time-carousel` | carousel | php | Maximum execution time exceeded |
| **28.07** | Wt | `story-execution-time` | 📱 | php | *Ankieta: podnosisz limit czy szukasz przyczyny?* |
| **29.07** | Śr | `env-secrets-carousel` | carousel **R** | devops | You committed .env once. It is in the history forever. |
| **30.07** | Czw | `quote-prevent-lazy-loading` | **S** quote | laravel | Make the silent N+1 loud |
| **30.07** | Czw | `story-lazy-loading` | 📱 | laravel | *Ankieta: masz to w AppServiceProvider?* |
| **31.07** | Pt | `sql-explain-carousel` | carousel **R** | database | Eleven seconds in prod. 40ms on your laptop. |
| **02.08** | Nd | `story-week-2` | 📱 | devops | *Ankieta: .env trafił kiedyś do historii gita?* |

### Tydzień 3 (03-09.08)

| Data | Dzień | Slug | Typ | Temat | Hook |
|---|---|---|---|---|---|
| **03.08** | Pon | `database-mistakes-carousel` | carousel **R** | database | Your invoice totals are off by a cent and nothing threw |
| **04.08** | Wt | `debounce-throttle-carousel` | carousel | javascript | Your search box fires on every keystroke |
| **04.08** | Wt | `story-debounce` | 📱 | javascript | *Quiz: debounce czy throttle do wyszukiwarki?* |
| **05.08** | Śr | `form-request-carousel` | carousel **R** | laravel | Thirty lines of validate(). Then again in update(). |
| **06.08** | Czw | `announce-php-course` | **S** announce | php | Free PHP course (7 rozdziałów, start przed pierwszą linią kodu) |
| **06.08** | Czw | `story-php-course` | 📱 | php | *Pytanie otwarte: co było najtrudniejsze na starcie z PHP?* |
| **07.08** | Pt | `php-match-carousel` | carousel **R** | php | switch thinks '1e1' and 10 are the same thing |
| **09.08** | Nd | `story-week-3` | 📱 | php | *Ankieta: match czy switch w nowym kodzie?* |

### Tydzień 4 — domknięcie (10-15.08)

| Data | Dzień | Slug | Typ | Temat | Hook |
|---|---|---|---|---|---|
| **10.08** | Pon | `zero-downtime-migration-carousel` | carousel **R** | laravel | The migration locked the table. The site was down for 40 seconds. |
| **11.08** | Wt | `php-enums-carousel` | carousel | php | Your status column takes any string you can typo |
| **11.08** | Wt | `story-enums` | 📱 | php | *Ankieta: enum czy string w kolumnie status?* |
| **12.08** | Śr | `race-conditions-carousel` | carousel **R** | php | Two clicks. One order. Two charges. |
| **13.08** | Czw | `quote-php-property-hooks` | **S** quote | php | The getter that did nothing is gone |
| **13.08** | Czw | `story-property-hooks` | 📱 | php | *Ankieta: jesteś już na 8.4?* |
| **14.08** | Pt | `git-commands-carousel` | carousel **R** | git | You learned add, commit, push. Then you stopped. |
| **15.08** | Sob | `story-month-wrap` | 📱 | php | *Pytanie otwarte: co mamy rozłożyć na części w przyszłym miesiącu?* |

---

## Bilans miesiąca

| Pozycja | Liczba |
|---|---|
| Karuzele | **18** |
| Reele (z tych karuzel) | **14** |
| Statyki (`quote` + `announce`) | **4** — dokładnie 1/tydzień |
| Stories (kotwice z repo) | **13** |
| **Postów w feedzie** | **22** |
| **Publikacji łącznie** (feed + reele) | **36** |
| Średnio na tydzień | **~8 publikacji** → przedział 6-9 = **+18% zasięgu/post** |

**Rozkład tematów** (powtarzanie tematu jest OK — **nie ma mechanizmu zmęczenia tematem**, a spójność
tematyczna pomaga systemowi sklasyfikować konto):

| Temat | Karuzele |
|---|---|
| php | 6 |
| laravel | 5 |
| database | 3 |
| git | 2 |
| javascript | 2 |
| docker | 1 (+ kurs) |
| devops | 1 |

Trzon to **PHP + Laravel (11 z 18)** — świadomie. To jest marka Oatllo i to klasyfikuje konto.

---

## Czego w tym planie CELOWO nie ma

- **Zero „Comment WORD below and I'll DM you the link".** Narzędzie (ManyChat) jest legalnym partnerem
  Mety, ale **ten caption to podręcznikowy engagement bait** — ryzykuje rekomendowalnością całego konta
  dla jednego tapnięcia.
- **Zero podów i zero komentowania pod większymi kontami.** Nie dlatego, że karane — **dlatego, że nie
  działa**. Pody uczą rankera pokazywać posty **złej publiczności**, a „$1.80 strategy" pochodzi
  z [wpisu Gary'ego Vee z 2017 bez ani jednej liczby](https://www.garyvaynerchuk.com/); Meta mówi
  odwrotnie niż folklor: komentowanie u X podbija **X-a w TWOIM feedzie**, nie ciebie u X-a.
- **Zero kupowania obserwujących.** Jedyna pozycja na liście, która kończy się **banem**, a nie
  obniżeniem zasięgu — i dla Oatllo samobójcza: kupieni obserwujący nie są programistami, więc nigdy nie
  wejdą na kurs. **To wzrost, który zjada własny cel.**
- **Zero optymalizacji godziny.** Patrz wyżej: największe badanie nie podaje rozmiaru efektu.
- **Więcej niż 5 hashtagów.** Twardy limit platformy od 18.12.2025.

## Czego ten plan NIE obiecuje

**Realistyczna baza, żeby za miesiąc nie uznać, że moduł nie działa:**

- Mediana zasięgu posta dla konta **0-1 tys. obserwujących: 33 osoby**. Dla 1-5 tys.: **185**.
- Mediana wzrostu: **0,5%/miesiąc**. Najlepsza kohorta Buffera (10+ postów/tydz.): **+32 obserwujących
  tygodniowo** względem tygodni ciszy.
- Wasza branża (**Tech & Software**) ma ER **~0,3-0,44%** — **przy dnie** wszystkich branż. Kuszące
  2,4% z „Higher Education" bierze się z dumy instytucjonalnej (absolwenci, kampus) i **blog dla devów
  nie ma jak tego pożyczyć**.
- Zaangażowanie na całej platformie: **−24% do −26% r/r**.

**Uczciwy cel na miesiąc: pobić bazową stopę i mieć 36 publikacji, z których da się CZEGOŚ SIĘ
NAUCZYĆ** — bo dla niszy dev-edu **nie istnieje żaden opublikowany benchmark**, a **zero danych** mówi,
które tematy programistyczne działają na IG. Insights tego konta to jedyny zbiór danych, który
kiedykolwiek odpowie na to pytanie.

---

## Co mierzyć po drodze

Nie lajki. **`sends per reach` i `average watch time`** — dwa z trzech nazwanych sygnałów, które widać
w Insights (trzeci to `likes per reach`).

**Długość reeli: ZMIERZONA, nie zgadywana.** 14 wyrenderowanych reelsów tego miesiąca ma **32,3 s -
42,8 s, średnio ~38 s** — czyli przedział **30-45 s** (0,30% ER, 8 564 mediany wyświetleń). To
przyzwoity koszyk, **nie najsłabszy**. Szczyt (45-60 s: 0,35%, 10 374) jest **~7 sekund dalej, czyli
mniej więcej jeden slajd**.

*(Wcześniejsza wersja tej notatki przewidywała „~15-35 s, najsłabszy przedział". Była błędna —
wnioskowała z configu zamiast wyrenderować jednego reela i zmierzyć. Render trwa 37 sekund.)*

⚠️ **Nie „naprawiać" tego pompowaniem `timing` w configu** — dłuższy reel to nie lepiej oglądany reel,
a watch time liczy **sekundy bezwzględne**. Droga do 45-60 s wiedzie przez **więcej slajdów albo gęstszą
treść**, czyli decyzję autorską. **I nagroda jest mała: 0,05 pp ER między koszykami.** Nie przebudowywać
modułu pod to — sprawdzić na Insightach.

## Dług, który wciąż siedzi w kodzie

**`config/social.php` ma `'hashtags_max' => 30`** z komentarzem „twardy limit Instagrama" — **nieprawda
od 18.12.2025**. `SocialPostLinter::lintHashtags()` przepuści posta z sześcioma hashtagami, którego
Instagram odrzuci. Skille mówią „max 5", **bramka tego nie egzekwuje**. Do naprawy razem z tym planem.
