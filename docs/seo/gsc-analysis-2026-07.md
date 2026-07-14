# Analiza Google Search Console — Oatllo (kwiecień–lipiec 2026)

Źródło: eksport GSC `oatllo.com-Performance-on-Search-2026-07-14` (Zapytania, Strony,
Kraje, Urządzenia, Wykres). Okres: **2026-04-13 → 2026-07-12 (91 dni)**.

## TL;DR (wnioski w jednym akapicie)

Strona jest **bardzo widoczna, ale prawie nieklikana**: 106 157 wyświetleń, **93 kliknięcia,
CTR 0,088%, średnia pozycja ~13**. To nie jest problem indexacji ani braku treści — to problem
**pozycji (2. strona Google)** plus **ogromnego udziału wyświetleń o złej intencji** (ludzie
szukają oficjalnego manuala php.net, nie kursu). Realna dźwignia: przepchnąć strony tutorialowe
z pozycji 10–15 do top 5 i pisać pod intencję **tutorialową**, a nie „manual/reference".

## Liczby bazowe

| Metryka | Wartość |
|---|---|
| Wyświetlenia | 106 157 |
| Kliknięcia | 93 |
| CTR | 0,088% |
| Śr. pozycja | ~13 (2. strona) |
| Rozkład pozycji (wg wyświetleń) | poz 1-10: 42% · 11-20: 23% · 20+: 35% |

### Urządzenia
| Urządzenie | Wyświetlenia | CTR | Pozycja |
|---|---|---|---|
| Desktop | 98 791 (93%) | **0,07%** | 14,3 |
| Mobile | 7 311 | **0,31%** | 11,6 |

Mobile klika **4× lepiej** niż desktop — bo desktopowe wyświetlenia to w dużej mierze
zapytania „manual/reference" (dev przy komputerze szukający dokumentacji).

### Kraje (kluczowy sygnał)
| Kraj | Wyświetlenia | Kliknięcia | CTR |
|---|---|---|---|
| **USA** | **48 035 (45%!)** | 5 | **0,01%** |
| **Indie** | 8 863 | **32** | **0,36%** |
| Holandia | 912 | 6 | 0,66% |
| Reszta | drobne | — | 0,2–0,6% |

**To jest sedno.** USA generuje 45% wyświetleń i praktycznie zero klików — to zapytania typu
„php manual match expression", gdzie użytkownik chce php.net i nigdy nie kliknie nieoficjalnej
strony. Indie (intencja „naucz mnie": „constructor and destructor in php", „object properties
in php") to realna, klikająca publiczność.

## Wniosek 1 — „śmieciowe" wyświetlenia zawyżają statystyki

- Zapytania z „manual/documentation/official/docs" to **≥21% widocznych** wyświetleń
  (raport GSC pokrywa tylko ~15,7k z 106k; w długim ogonie i w USA jest ich znacznie więcej).
- Te wyświetlenia **nigdy nie dadzą klika** — php.net wygrywa intencję „reference".
- **Nie optymalizować pod nie.** Ignorować „vanity" 106k wyświetleń; liczy się podzbiór
  tutorialowy.

## Wniosek 2 — prawdziwa okazja: strony uwięzione na pozycji 9–15

Strony z dużymi wyświetleniami tuż pod topem. Przepchnięcie do top 5 = skok z ~0 do setek
klików/mies. Mapowanie strona → czyste frazy tutorialowe (z pominięciem „manual"):

| Strona (`/course/php/...`) | Wyświetl. | Poz. | Czyste frazy docelowe |
|---|---|---|---|
| `function/php-function-arguments-guide` | 17 731 | 10,1 | „php optional parameters" (349), „php function parameters" |
| `objective-programming/php-constructor-destructor-guide` | 12 563 | 12,8 | „constructor and destructor in php" (425), „php constructor" |
| `objective-programming/php-properties-methods-guide` | 9 736 | 15,0 | „object properties in php" (87, **CTR 2,3%**), „object methods in php" |
| `function/php-variable-scope-guide` | 7 164 | 11,5 | „php variable scope" (13), „…foreach" (79) |
| `php-basics/difference-single-double-quotes-php` | 6 393 | 11,9 | „php single vs double quotes" |
| `conditional-instructions/php-match-expression-guide` | 5 313 | **9,6** | „php match" (555), „match php" (135) — najbliżej strony 1 |
| `objective-programming/php-getter-setter-guide` | 5 278 | 12,3 | „getter and setter in php" (38, poz 8,3) |

Dodatkowo blisko top 3: `letter-i-in-solid` — „i in solid" poz **5,9**.

## Wniosek 3 — dowód, że przy dobrej intencji strona KONWERTUJE

„object properties in php" → pozycja 7,6 → **CTR 2,3%** (2 kliki z 87). Gdy zapytanie jest
tutorialowe i pozycja dobra, klikają. Czyli treść jest OK — brakuje **pozycji**, nie jakości.

## Rekomendacje (kolejność wg zwrotu)

1. **Content depth pod dokładne frazy tutorialowe** dla 7 stron z tabeli — to jedyne, co realnie
   rusza pozycję z 2. strony na 1. (skill `lesson-seo` per lekcja: keywords → przepisanie →
   weryfikacja dydaktyki). **Priorytet: `php-match-expression-guide`** (poz 9,6 — najbliżej).
2. **CTR: meta description + tytuły** front-ładujące dokładną wygrywającą frazę i benefit.
   Opis nie wpływa na ranking (zero ryzyka), tylko na klikalność.
3. **Nie gonić** zapytań „manual/official/documentation" ani „vanity" wyświetleń z USA.
4. **Linkowanie wewnętrzne** koncentrujące autorytet na 7 „money pages" (jest automat
   `InternalLinker`; warto dopilnować, że nowe artykuły linkują do tych lekcji).
5. **Świeżość** — migracja kursu do `.md` odświeżyła daty (mtime); aktualizacje lekcji +
   ping IndexNow podbijają sygnał świeżości.

## Co zostało już zrobione (kontekst techniczny, lipiec 2026)

- Kanonikalizacja hosta 301 (www/http → https://oatllo.com) — konsolidacja podzielonych
  sygnałów (te same strony były indeksowane pod 4 hostami).
- Lazy-load obrazów treści, degradacja H1 w treści, obrazy w sitemapie, poprawny `lastmod`.
- Migracja kursu PHP z bazy do `.md` — teraz edytowalny pod SEO w git (te 7 stron to lekcje `.md`).
