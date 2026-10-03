# Cotygodniowa analiza GSC (oatllo.com)

Katalog na **cotygodniowe** zdjęcia stanu Google Search Console, żeby dało się porównywać tydzień
do tygodnia, a nie tylko oglądać jeden wykres w panelu.

## Jak to robić co tydzień

1. W GSC wyeksportuj **dwa** raporty (przycisk „Eksportuj" -> CSV):
   - **Skuteczność w wyszukiwarce**, filtr daty **Ostatnich 7 dni** (nie 3 miesiące - okna muszą
     być tej samej długości, inaczej nic nie porównasz).
   - **Indeksowanie stron** (Coverage).
2. Rozpakuj do `raw/{RRRR-MM-DD}-performance-7d/` i `raw/{RRRR-MM-DD}-coverage/`.
   Data w nazwie = **dzień eksportu**, nie koniec okna danych.
3. Napisz `{RRRR-MM-DD}.md` wg schematu z poprzedniego tygodnia i **dopisz wiersz do tabeli trendu
   poniżej**.

## Pułapki przy czytaniu tych danych

- **Zanim ocenisz zmianę, sprawdź, czy w ogóle wyszła z laptopa: `git status -sb`.** 03.10 wyszło,
  że analiza i przepisane lekcje z 17.09 (grupa testowa `lesson-seo`) stały w lokalnym `main`
  (`ahead 2`) i nigdy nie zostały wypchnięte. Produkcja nie miała czego pociągnąć, a `curl`
  z checklisty wdrożenia tego nie łapie - sprawdza tylko, czy serwer pociągnął to, co jest w originie.
  Prawie trzy tygodnie „testu" bez testu. Dla zmian treści dodatkowo `curl` na `<title>` zmienionej strony.
- **GSC ma ~2-3 dni opóźnienia.** Okno „ostatnich 7 dni" kończy się ~2 dni przed eksportem, więc
  eksport z 27.07 pokrywa 19-25.07. Nie porównuj końcówek okien.
- **Średnia pozycja spada, gdy przybywa zindeksowanych stron** i to nie jest regres. Nowe strony
  wchodzą na głębokich pozycjach i rozwadniają średnią. Patrzeć na pozycję konkretnych URL-i,
  nie na średnią domeny.
- **GSC poprawia historię wstecz.** Dla 10.07 eksport z 15.07 podaje 38 zindeksowanych, a eksport
  z 27.07 podaje dla tego samego dnia 48. To nie błąd przepisania - Google przelicza szereg.
  Dlatego **w tabeli trendu wpisujemy wartości z NAJNOWSZEGO eksportu**, a nie te, które
  zanotowaliśmy tydzień wcześniej. Surowe CSV zostają w `raw/`, więc zawsze da się to odtworzyć.
- **Coverage i Performance liczą co innego.** Wyświetlenia w Coverage (kolumna w `Wykres.csv`) to
  inny szereg niż w Performance - nie zestawiać ich w jednej tabeli.
- **Zanim wyjaśnisz zero wyświetleń SEO-em, sprawdź, czy strona w ogóle stoi.** 23.08 artykuł
  z terminem 19.08 miał zero wyświetleń, bo zwracał **404**: produkcja była o commit do tyłu
  i publikowała wg starego harmonogramu. Przy każdej analizie warto puścić `curl -I` po kilku
  najświeższych slugach z `resources/articles/` i sprawdzić, czy są w `sitemap.xml`. To 10 sekund,
  a odróżnia „Google nas nie indeksuje" od „tej strony nie ma".
- **Przekrój na JEDNĄ stronę robi się osobnym eksportem i pokazuje ułamek danych.** W panelu:
  Skuteczność -> zakładka Strony -> klik w URL (to zakłada filtr) -> Eksportuj. W katalogu nazywamy
  to `raw/{data}-performance-{okno}-page-{slug}/`. **Uwaga: przekroje sumują się do znacznie mniej
  niż `Wykres.csv`.** Dla `caching-static-assets`: wykres 650 wyświetleń, a `Kraje`, `Zapytania`
  i `Urządzenia` po **56**. GSC ukrywa rzadkie zapytania i wycina te wyświetlenia ze wszystkich
  przekrojów naraz. Nie czytać tych sum jak procentów całości - to próbka 8.6%.
  **Ogólniejszy wniosek: każda „pozycja" z `Strony.csv` jest średnią po wyświetleniach, których
  w większości nie da się obejrzeć.**
- **Nie budować wniosku na zgodności trzech liczb w dwóch agregatach.** 06.09 hipoteza „nasze dobre
  pozycje to ruch ze ZEA" opierała się na tym, że nginx poz. 7-10 (1539 wyśw. / 8.67 / 0 klik.)
  i ZEA (1371 wyśw. / 8.60 / 0 klik.) prawie się pokrywają. Przekrój na stronę obalił ją w dwie
  minuty: ZEA to 18%, USA 80%. **Dwa agregaty mogą mieć te same trzy liczby, opisując rozłączne
  wyświetlenia.** Zgodność agregatów to powód, żeby zrobić przekrój, a nie żeby go pominąć.
- **„Ogon `www.` wygaśnie sam" dotyczy tylko URL-i, których wyświetlenia SPADAJĄ.** 17.09
  `http://www.oatllo.com/laravel-job-batching` urósł 68 -> 77 w oknie przesuniętym za naprawę
  kanonikalizacji, a apeks tego artykułu nie występuje w `Strony.csv` wcale. Serwer był poprawny
  (301 + canonical) - to Google trzyma stary kanoniczny. Rosnący `www.` przy braku apeksu =
  „Sprawdź URL" w GSC na apeksie, nie czekanie.
- **Coverage „Nie znaleziono (404)" ma u nas ROSNĄĆ i nie jest to usterka.** Wycofane artykuły
  (`config/articles.php` -> `retired_slugs`) oddają świadomie **410 Gone** (`HomeController:188`),
  a GSC wrzuca 410 do tego samego kubełka co 404. Licznik 2 -> 7 (06.09) to Google skanujący
  wycofane treści, czyli dokładnie to, o co chodziło. Analogicznie **rosnący licznik `noindex`**
  (15 -> 28) to strony tagów, które mają być noindex - dowód, że dyrektywa działa. Alarmować ma
  „zeskanowana, niezindeksowana", nie te dwa.
- **Eksport 3-miesięczny z 2026-07-14** (`raw/2026-07-14-performance-3m/`) to historyczna baza
  sprzed przejścia na cadence tygodniowy. Porównywalny tylko po średnich dziennych i po CTR.

## Trend (uzupełniać co tydzień)

Performance = okno 7 dni. Coverage = stan na dzień eksportu.

| Eksport | Okno danych | Klik. | Wyśw. | CTR | Śr. poz. | Zindeks. | Niezindeks. | Zeskan. nieindeks. |
|---|---|---|---|---|---|---|---|---|
| 2026-07-14/15 (baza 3M) | 13.04-12.07 | 93 | 106157 | 0.09% | ~13 | 48 | 209 | 71 |
| **2026-07-27** | 19-25.07 | **15** | **6249** | **0.24%** | **17.5** | **183** | **119** | **84** |
| **2026-08-09** | 01-07.08 | **19** | **15438** | **0.12%** | **20.9** | **260** | **136** | **56** |
| _(wycinek)_ | 08-14.08 | 9 | 14819 | 0.06% | 25.6 | 320 | 134-152 | - |
| **2026-08-23** | 15-21.08 | **8** | **12056** | **0.07%** | **29.5** | **371** | **148** | **52** |
| _(wycinek)_ | 22-28.08 | 17 | 7319 | 0.23% | 25.3 | 423 | 171 | - |
| **2026-09-06** | 29.08-04.09 | **17** | **7225** | **0.24%** | **21.6** | **423** | **171** | **49** |
| _(wycinek)_ | 01-07.09 | 14 | 7444 | 0.19% | 15.2 | - | - | - |
| **2026-09-17** | 08-14.09 | **10** | **5930** | **0.17%** | **13.4** | _brak eksportu_ | - | - |
| _(wycinek)_ | 16-22.09 | 25 | 4571 | 0.55% | 10.9 | - | - | - |
| **2026-10-03** | 23-29.09 | **21** | **4511** | **0.47%** | **9.4** | _brak eksportu_ | - | - |

Eksport z 03.10: filtr „Ostatnich 28 dni" (02.09-29.09), **bez Coverage**. Dzień 15.09 (3 klik.)
wypada między wierszami. Na 28 dni: **71 kliknięć** (17.09: 49) - pierwszy realny wzrost kliknięć.
Szczegóły w `2026-10-03.md`.

Eksport z 17.09: filtr „Ostatnich 28 dni" (18.08-14.09), **bez Coverage**. Wycinek 01-07.09
zachodzi 4 dniami na wiersz 29.08-04.09 - wyrównanie do końca okna zamiast dziury. Szczegóły
w `2026-09-17.md`.

Eksport z 06.09 też zrobiono z filtrem „Ostatnich 28 dni" (08.08-04.09) i z tego samego powodu -
poprzednia analiza jest sprzed dwóch tygodni. Coverage w obu wierszach z 06.09 to stan na dzień
eksportu (szereg Coverage kończy się 28.08), więc jest ten sam.

**Wyświetlenia spadły o połowę przy dwukrotnym wzroście kliknięć i to nie jest sprzeczność.**
Pozycje spadających stron poprawiły się albo stanęły - Google zwęził zestaw zapytań, na których
nas pokazuje, zamiast zdegradować rankingi. Od 06.09 **wyświetlenia nie są u nas miarą wzrostu**;
patrzymy na kliknięcia i pozycję ważoną, wyświetlenia tylko jako mianownik CTR. Szczegóły
w `2026-09-06.md`, ustalenie nr 1.

Eksport z 23.08 zrobiono z filtrem **„Ostatnich 28 dni"** (25.07-21.08), bo poprzednia analiza jest
sprzed dwóch tygodni i okno 7-dniowe zostawiłoby dziurę. Wiersze wyżej to **wycinki 7-dniowe
z `Wykres.csv`** tego eksportu; dzienne szeregi zgadzają się z eksportem z 09.08 co do jednego
kliknięcia, więc wycinanie jest legalne. Pozycja w tych dwóch wierszach to **średnia ważona
wyświetleniami**, a nie średnia z dziennych średnich (GSC liczy tak samo, ale wcześniejsze wiersze
przepisano wprost z panelu - stąd możliwa różnica na pierwszym miejscu po przecinku).
Przekroje strona/zapytanie/kraj w `raw/2026-08-23-performance-28d/` dotyczą **całych 28 dni**,
nie wiersza z tabeli.

Wiersz „baza 3M" jest tam dla kontekstu, nie do arytmetyki tydzień-do-tygodnia: 106157 wyświetleń
to 91 dni (~1167/dzień), a 6249 to 7 dni (~893/dzień).

W oknie 01-07.08 jeden dzień (05.08) dał 5661 wyświetleń przy ~1500 w pozostałe. Bez niego średnia
to 1629/dzień zamiast 2205 - przy porównaniu z kolejnym tygodniem pamiętać, że 2205 nie jest poziomem.

## Pozycja LUDZKICH zapytań - główna miara od 17.09 (uzupełniać co tydzień)

**Średnia pozycja domeny kłamie i to kosztowało nas miesiąc złudzenia.** Między 23.08 a 17.09
spadła z 29.5 do 13.4, stron na pozycji 5-10 przybyło 18 -> 97, a kliknięcia stały (59 -> 51 -> 49
na 28 dni). Na pozycji 5-10 mieliśmy CTR **0.26%** przy normie 2-5%: poprawa szła wyłącznie na
długich, maszynowych zapytaniach (ustalenie nr 3 z 06.09). Dlatego śledzimy **pozycję ważoną
wyświetleniami zapytań 1-3-wyrazowych** z `Zapytania.csv` - to tam siedzą ludzie i kliknięcia.

| Eksport | Okno | Zapytań 1-3 sł. | Wyśw. | Klik. | Poz. ważona |
|---|---|---|---|---|---|
| 2026-07-14 | 3M | 438 | 7610 | 6 | 23.87 |
| 2026-07-27 | 7d | 391 | 977 | 1 | 23.00 |
| 2026-08-09 | 7d | 508 | 1487 | 1 | 30.51 |
| 2026-08-23 | 28d | 544 | 6725 | 4 | 30.55 |
| 2026-09-06 | 28d | 543 | 6974 | 2 | 30.37 |
| 2026-09-17 | 28d | 559 | 5366 | 3 | 28.52 |
| **2026-10-03** | 28d | **571** | **3664** | **2** | **23.57** |

Liczba jest z próbki (GSC ukrywa rzadkie zapytania), ale liczona zawsze tak samo, więc trend jest
porównywalny. Skok 23 -> 30.5 w sierpniu to wejście ~200 nowych stron na głębokie pozycje, nie
utrata rankingów. **Cel: zejść poniżej 20.** Kliknięć z tej tabeli nie czytać wprost - większość
kliknięć GSC nie przypisuje do żadnego zapytania.

## Wskaźniki, które śledzimy (i dlaczego akurat te)

1. **Zindeksowane strony** - jedyne wąskie gardło, które w czerwcu naprawdę blokowało wzrost.
2. **„Zeskanowana, ale niezindeksowana"** - sygnał jakościowy. Rośnie = Google ogląda i odrzuca.
3. **CTR domeny** - przy naszych pozycjach (10-15) to najtańsza dźwignia: tytuł i opis, nie treść.
4. **Kolejka robocza dla `lesson-seo` - GRUPA TESTOWA 5 lekcji PHP** (krótkie ludzkie
   zapytania, przepisane pod lukę wobec SERP-a 17.09, baseline w `2026-09-17.md`). **03.10: NIE BYŁA
   WDROŻONA** (niewypchnięty commit) - ocena 3-4 tygodnie od faktycznego wdrożenia, patrz `2026-10-03.md`.
   Wcześniej (06.09) na pauzie, a próg pozycji okazał się fałszywym kryterium. Historia: startowo „pozycja 8-16", 09.08 zawężone do <12 (dziesięć lekcji
   poprawionych 27.07 nie oderwało się CTR-em od trendu domeny), 06.09 **próg pozycji wyrzucony
   w całości**. Powód: przekrój na jedną stronę pokazał, że `caching-static-assets` jest na
   **pozycji 6.18 w USA** i ma zero kliknięć, a wszystkie widoczne zapytania to permutacje jednej
   dwunastowyrazowej linijki configu nginxa (100% desktop). Na całej domenie zapytania 8-20-wyrazowe
   mają **najlepszą pozycję (12.94) i zero kliknięć**, a 1-3-wyrazowe najgorszą (30.37) i wszystkie
   kliknięcia. **Wysoka pozycja przy zerowym CTR znaczy u nas „po drugiej stronie nie ma człowieka",
   nie „popraw tytuł".** Nowa reguła kwalifikacji: **kształt zapytań** (krótkie, 1-4 słowa,
   brzmiące jak pytanie człowieka), sprawdzany eksportem GSC z filtrem na tę jedną stronę.
   Szczegóły w `2026-09-06.md`, ustalenie nr 3.
5. **CTR artykułów `.md` kontra CTR lekcji kursów** - wskaźnik od 09.08. **Przewaga wynosi ok. 6x
   i jest stabilna** (06.09: 0.602% vs 0.100%, 25% kliknięć domeny z 5.2% wyświetleń). Liczba
   „10-20x" z 09.08 była artefaktem małej próbki (12 artykułów, 15 kliknięć) - nie cytować jej.
   Kierunek się nie zmienia: artykuł to najtańsze kliknięcie w tym repo.
6. **Czas od publikacji artykułu do pierwszych wyświetleń** - wskaźnik od 06.09, bo to on rządzi
   tempem publikacji. 09.08: 3-4 tygodnie i pięć najnowszych artykułów z rzędu na zerze. 06.09:
   dwa z ostatnich ośmiu weszły w **kilka dni** i od razu na pozycję 8-9, pozostałe sześć na zerze.
   **Wyzwalacz rewizji tempa jest spełniony w połowie** - patrz `2026-09-06.md`, ustalenie nr 4.
