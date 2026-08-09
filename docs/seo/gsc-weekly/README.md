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
- **Eksport 3-miesięczny z 2026-07-14** (`raw/2026-07-14-performance-3m/`) to historyczna baza
  sprzed przejścia na cadence tygodniowy. Porównywalny tylko po średnich dziennych i po CTR.

## Trend (uzupełniać co tydzień)

Performance = okno 7 dni. Coverage = stan na dzień eksportu.

| Eksport | Okno danych | Klik. | Wyśw. | CTR | Śr. poz. | Zindeks. | Niezindeks. | Zeskan. nieindeks. |
|---|---|---|---|---|---|---|---|---|
| 2026-07-14/15 (baza 3M) | 13.04-12.07 | 93 | 106157 | 0.09% | ~13 | 48 | 209 | 71 |
| **2026-07-27** | 19-25.07 | **15** | **6249** | **0.24%** | **17.5** | **183** | **119** | **84** |
| **2026-08-09** | 01-07.08 | **19** | **15438** | **0.12%** | **20.9** | **260** | **136** | **56** |

Wiersz „baza 3M" jest tam dla kontekstu, nie do arytmetyki tydzień-do-tygodnia: 106157 wyświetleń
to 91 dni (~1167/dzień), a 6249 to 7 dni (~893/dzień).

W oknie 01-07.08 jeden dzień (05.08) dał 5661 wyświetleń przy ~1500 w pozostałe. Bez niego średnia
to 1629/dzień zamiast 2205 - przy porównaniu z kolejnym tygodniem pamiętać, że 2205 nie jest poziomem.

## Wskaźniki, które śledzimy (i dlaczego akurat te)

1. **Zindeksowane strony** - jedyne wąskie gardło, które w czerwcu naprawdę blokowało wzrost.
2. **„Zeskanowana, ale niezindeksowana"** - sygnał jakościowy. Rośnie = Google ogląda i odrzuca.
3. **CTR domeny** - przy naszych pozycjach (10-15) to najtańsza dźwignia: tytuł i opis, nie treść.
4. **Lista „duże wyświetlenia / 0 kliknięć, pozycja 8-16"** - kolejka robocza dla `lesson-seo`.
   To z niej bierzemy paczkę lekcji do poprawy w danym tygodniu.
   **Od 09.08 próg zawężony do pozycji < 12.** Dziesięć lekcji poprawionych 27.07 nie oderwało się
   CTR-em od trendu domeny (0.15% -> 0.07% przy domenie 0.24% -> 0.12%), więc przepisywanie tytułu
   na pozycji 12-16 nie jest udowodnioną dźwignią. Szczegóły w `2026-08-09.md`.
5. **CTR artykułów `.md` kontra CTR lekcji kursów** - nowy wskaźnik od 09.08. Artykuły robiły 47%
   kliknięć domeny z 10% wyświetleń (0.58% vs 0.07-0.20%). Jeśli to się utrzyma, główną dźwignią
   jest tempo publikacji artykułów, a nie optymalizacja lekcji.
