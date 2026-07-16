# Narrated video ("clips") — architektura (propozycja)

**Status:** propozycja do zatwierdzenia. Zero kodu napisanego. Ten dokument opisuje
CAŁY pipeline, żeby można było go przejrzeć i przyklepać, zanim cokolwiek powstanie.

**Cel:** krótkie, narrowane wideo pod TikToka / YouTube Shorts / Instagram Reels.
Scenariusz (tekst) → narracja ElevenLabs → Remotion składa sceny animowane
zsynchronizowane z głosem → napisy wypalone + efekty dźwiękowe → MP4 1080x1920.

Decyzje podjęte z użytkownikiem (2026-07-16):
- **Model animacji:** biblioteka scen + scenariusz (NIE bespoke TSX per wideo).
- **Język narracji:** angielski (strona prowadzona po angielsku).
- **TTS na start:** zamockowany (klucz ElevenLabs później; pipeline działa bez klucza).
- **Kolejność:** najpierw ten dokument, potem kod.

---

## 1. Dlaczego to OSOBNY byt, a nie rozbudowa reela

Obecny `reel` (`social:video`, `Reel.tsx`, `ReelStager`) to świadomie:
- **niemy** (muzyka dokładana w Instagramie),
- **bez własnych animacji** (tylko wjazd slajdów; 10 skórek × 4 typy — zero bespoke TSX),
- **kadr 4:5 na podkładzie** (kalibrowany pod feed, `.story-footer` kotwiczy do `.stage`),
- **timing z objętości treści** (PHP liczy z liczby słów/linii kodu),
- **ten sam HTML co PNG** (jedno źródło designu — żywy DOM z Blade).

Clip jest **odwrotnością każdego z tych punktów**: ma głos, ma bespoke ruch per scena,
timing bierze z długości audio, a wizual to natywne komponenty React (nie wstrzyknięty
Blade). Wciskanie tego w `Reel.tsx` zniszczyłoby działający moduł. Dlatego:

> **Clip to drugi, niezależny pipeline OBOK reela.** Współdzielą tylko: projekt Node
> `social-video/`, motywy technologii (`TechThemeResolver`), wklejony font (base64),
> zasadę „artefakty gitignorowane, `.md` jest źródłem prawdy", brak bazy, render lokalny.

Nazewnictwo: **reel** = niema karuzela w ruchu (jest). **clip** = narrowany explainer (nowe).

---

## 2. Podział ról (ta sama DNA co reszta modułu)

| Warstwa | Odpowiada za | Nie wie o |
|---|---|---|
| `.md` scenariusz | treść: sceny, narracja, kod, timing intencji | jak to animować |
| PHP (`App\Services\Clip`) | parsowanie, lint, TTS, timing z audio, manifest | React, klatki |
| Manifest `clip.json` | kontrakt: sceny + audio + długości + cue'y | — |
| Remotion (`Clip` composition) | WYŁĄCZNIE ruch: animacje scen, napisy, mix audio | skąd wzięła się treść |

Ta sama granica co przy reelu: **PHP wie wszystko o treści, Remotion tylko o ruchu.**
Scenariusz nie ucieka do TSX-a, gdzie nie sięga lint ani testy PHP.

---

## 3. Format źródłowy: `resources/clips/{slug}.md`

Ta sama architektura co posty/kursy/artykuły: plik `.md` commitowany w repo,
czytany przez repozytorium do DTO, zero bazy, zero crona. Katalog: `config('clip.path')`.

### Frontmatter (globalne)

```yaml
---
slug: eloquent-n1-explainer
title: "The N+1 query that's killing your Laravel app"
topic: laravel            # → TechThemeResolver: akcent + logo (współdzielone z kursami)
voice: narrator_en        # klucz z config('clip.voices') → voice_id ElevenLabs
source: eloquent-n-plus-one   # slug artykułu źródłowego (do weryfikacji faktów)
music: none               # none | {klucz z config('clip.music')} — opcjonalny podkład
platforms: [tiktok, shorts, reels]   # etykieta docelowa; wszystkie 9:16 1080x1920
caption: |                # podpis do wklejenia (TikTok/YT/IG description)
  ...
hashtags: [laravel, php, webdev]
---
```

### Ciało: sceny rozdzielone `<!-- scene -->`

Separator `<!-- scene -->` (jak `<!-- slide -->` w postach — świadomie NIE `---`,
bo nieodróżnialne od frontmattera; patrz CLAUDE.md). Każda scena to **blok YAML**
(w pełni strukturalny — Claude autoryzuje niezawodnie, kod idzie jako literał `|`).

```yaml
<!-- scene -->
type: title
narration: The N plus one query problem is silently killing your Laravel app.
text: "N+1 is killing your app"

<!-- scene -->
type: code-reveal
narration: >
  Here is the innocent looking code. It looks like one query,
  but it fires a hundred.
lang: php
highlight: [3]
code: |
  $users = User::all();
  foreach ($users as $user) {
      echo $user->posts->count();
  }

<!-- scene -->
type: bullets
narration: The fix is eager loading. One query instead of a hundred.
items:
  - "User::with('posts')"
  - "1 query, not 101"
  - "N+1 gone"

<!-- scene -->
type: outro
narration: Full breakdown on oatllo dot com. Follow for more.
cta: "oatllo.com"
```

### Reguła nadrzędna: scena = (narracja, wizual)

**Każda scena MA `narration`.** To jednocześnie:
1. tekst wysyłany do ElevenLabs,
2. **wyznacznik długości sceny** (scena trwa dokładnie tyle, ile jej audio),
3. źródło napisów (timestampy na poziomie znaku → słowa → napisy karaoke).

To jest kręgosłup synchronizacji. Wizual sceny pokazuje się DOPÓKI gra jej narracja.

---

## 4. Biblioteka scen (v1)

Każda scena to komponent React o typowanych propsach + długość z audio. Współdzielą
`theme.ts` (neutral-950, akcent z `TechThemeResolver`, Montserrat base64) — wyglądają
jak Oatllo. Starter set (8 typów, pokrywa 90% dev-contentu):

| `type` | Wizual | Ruch |
|---|---|---|
| `title` | wielki headline (hook) | kinetic type in/out |
| `code-reveal` | blok kodu, podświetlone linie | linie wjeżdżają / typewriter, highlight pulsuje |
| `bullets` | lista punktów | stagger wjazdu, aktywny punkt podświetlony |
| `statement` | jedno mocne zdanie | zoom + fade |
| `compare` | split „źle / dobrze" (zły vs dobry kod) | dwie połowy wjeżdżają z boków |
| `terminal` | atrapa terminala: komenda + output | typewriter komendy, output się wypisuje |
| `callout` | liczba / metryka (np. „100x faster") | odliczanie / spring |
| `outro` | CTA, `@oatllo`, „follow" | logo + akcent, subtelny loop |

Nowa scena = nowy komponent + wpis w mapie `type → component` + budżet w lincie + test,
że każdy dozwolony `type` ma komponent. `diagram` (boxy+strzałki) świadomie **v2** —
najtrudniejszy, nie blokuje startu.

**Napisy (burned-in) to nie scena — to warstwa NAD każdą sceną.** Na TikToku/Shorts
animowane napisy to główny driver retencji. Z timestampów ElevenLabs → słowa z czasami
→ napis u dołu z podświetlanym aktualnym słowem. Używamy `@remotion/captions`.

---

## 5. Audio: narracja (TTS) + timing + SFX

### 5.1 Narracja — ElevenLabs `with-timestamps`

Endpoint `text-to-speech/{voice_id}/with-timestamps` zwraca base64 audio + `alignment`
(znaki + start/end w sekundach). Z tego:
- **długość sceny** = długość mp3 zaokrąglona w górę do klatek (fps=30),
- **napisy** = grupowanie znaków w słowa/linie z czasami,
- (v2) **cue'y animacji** — „odsłoń linię kodu, gdy narracja dojdzie do słowa X".

### 5.2 Audio jest ARTEFAKTEM, nie źródłem — cache po treści

Dokładnie jak PNG-i i reele: **audio jest wyliczalne ze scenariusza (ale kosztuje
API), więc NIE trafia do gita.** Cache w `storage/app/clip-audio/{sha1(narration+voice)}.mp3`
(+ `.json` z timestampami). Zmiana narracji → nowy hash → regeneracja. Niezmieniona
narracja między renderami = zero kosztu. `--force` wymusza.

To ten sam wzorzec co `verified: fingerprint` w postach: **odcisk treści domyka pętlę.**

### 5.3 Provider TTS za interfejsem — mock na start

```
interface TtsProvider {
    synthesize(text, voiceId): Narration   // mp3 bytes + word timings
}
```
- `ElevenLabsProvider` — realny (guard: pusty `ELEVENLABS_API_KEY` = wyjątek z jasnym
  komunikatem, jak IndexNow-owy no-op).
- `MockTtsProvider` — **cisza o oszacowanej długości** (słowa ÷ tempo mówienia z configu)
  + syntetyczne timestampy rozłożone równo. Dzięki temu **cały pipeline działa bez klucza**:
  renderujesz nieme wideo z poprawnym timingiem i napisami, dokładasz głos później.
  Wybór providera: `config('clip.tts.driver')`.

### 5.4 SFX — mała licencjonowana biblioteka COMMITOWANA

W odróżnieniu od narracji (per-wideo, kosztuje) SFX to **stałe, małe, wielokrotnie
używane assety** — jak font. Commitowane w `social-video/public/sfx/*.mp3`. Scena deklaruje:
```yaml
sfx: whoosh            # nazwa z config('clip.sfx')
# lub: sfx: [{name: pop, at: 0.4}]   # przy konkretnym ułamku sceny
```
**Licencja jest twarda:** tylko CC0 / royalty-free z prawami komercyjnymi (Pixabay,
Freesound CC0). Claude NIE zmyśla plików audio — zestaw SFX dostarczasz Ty (osobne
zadanie „skompletuj bibliotekę SFX"). Bez pliku scena po prostu gra bez SFX (warning w lincie).

### 5.5 Muzyka — opcjonalny podkład (v2)

`music:` we frontmatterze → jeden ściszony bed pod całość. Ta sama licencja co SFX,
plik commitowany. W odróżnieniu od reela (niemy z premedytacją) tu muzyka MA sens —
ale to v2, nie blokuje startu.

---

## 6. Manifest — kontrakt PHP → Remotion

`social-video/public/clips/{slug}/clip.json` (gitignorowany — wyliczalny z `.md`):

```json
{
  "slug": "eloquent-n1-explainer",
  "fps": 30,
  "canvas": { "width": 1080, "height": 1920 },
  "theme": { "accent": "#f43f5e", "logo": "laravel" },
  "music": null,
  "scenes": [
    {
      "type": "title",
      "durationInFrames": 96,
      "params": { "text": "N+1 is killing your app" },
      "narration": {
        "audio": "audio/01.mp3",
        "words": [ { "text": "The", "start": 0.0, "end": 0.18 }, ... ]
      },
      "sfx": [ { "file": "sfx/whoosh.mp3", "atFrame": 0 } ]
    }
  ]
}
```

Audio scen ląduje w `public/clips/{slug}/audio/NN.mp3` (kopiowane z cache przy stage).
Remotion (`calculateMetadata`) wczytuje `clip.json`, sumuje `durationInFrames`, ustawia
długość kompozycji — tak jak dziś robi to `Root.tsx` dla reela.

---

## 7. Remotion: kompozycja `Clip` (obok istniejącego `Reel`)

Rozbudowa `social-video/`, NIE nowy projekt (jedna instalacja Node, jedna licencja,
jeden `npm i`). `Reel.tsx` NIETKNIĘTY.

```
social-video/src/
  Root.tsx            # rejestruje Reel (jest) + Clip (nowe)
  clip/
    Clip.tsx          # sekwencjonuje sceny; każda scena = <Sequence> + <Audio> narracji + SFX
    theme.ts          # tokeny designu Oatllo (akcent, tło, font)
    Captions.tsx      # napisy karaoke z timestampów (@remotion/captions)
    scenes/
      Title.tsx  CodeReveal.tsx  Bullets.tsx  Statement.tsx
      Compare.tsx  Terminal.tsx  Callout.tsx  Outro.tsx
    registry.ts       # mapa type → component (test pilnuje kompletności)
```

`Clip.tsx` dla każdej sceny:
1. `<Sequence from={elapsed} durationInFrames={scene.durationInFrames}>`
2. w środku: komponent sceny z `registry[scene.type]` + jego propsy,
3. `<Audio src={narration.audio}>` (głos sceny),
4. SFX `<Audio>` przy `atFrame`,
5. `<Captions words={narration.words}>` na wierzchu.

**Miny z reela, które przenoszą się tu (z CLAUDE.md):**
- reguły animacji **scopować numerem sceny** (`.clip-scene-{i}`) — wstrzyknięty `<style>`
  jest globalny, `useCurrentFrame()` liczy od `<Sequence>`; bez scope dwie sceny na ekranie
  animują się nawzajem,
- czekać na `document.fonts.ready` (`delayRender`) — font base64, inaczej pierwsze klatki
  fontem zastępczym,
- napisy/dekoracje na **stałej wysokości** kolidują z UI platformy — dolne ~390px zasłania
  UI TikToka/Shorts; napisy sadzać wyżej niż środek (jak `SLIDE_TOP` w reelu).

---

## 8. Komendy (mirror `social:video`)

| Komenda | Rola |
|---|---|
| `clip:lint {slug}` | **bramka** — budżety narracji/kodu, nieznany `type` = ERROR, brak `narration` = ERROR |
| `clip:tts {slug} [--force]` | generuje/cache'uje narrację + timestampy (mock gdy brak klucza) |
| `clip:stage {slug}` | buduje `clip.json` + kopiuje audio do `public/clips/{slug}` |
| `clip:render {slug} [--stage-only]` | lint → tts → stage → render → `storage/app/social-export/{slug}/clip.mp4` |
| podgląd | `cd social-video && npx remotion studio` (kompozycja `Clip`, slug w propsach) |

`clip:render` to orkiestrator (jak dziś `social:video`), z tą samą bramką lintu i tym
samym `RemotionRenderer` (parametryzowany nazwą kompozycji `Clip` zamiast `Reel`).

---

## 9. Lint (`clip:lint`) — bramka formatu

Analogicznie do `social:lint`. ERRORY (blokują render):
- nieznany `type` sceny (literówka wypadłaby z wideo bez śladu),
- brak `narration` w scenie,
- kod: > `code_lines_max` linii lub > `code_cols_max` kolumn (overflow za krawędź),
- znak `→`/`←` w napisach jeśli font subset ich nie ma (jak w reelu).

WARNINGI: narracja sceny dłuższa niż budżet (scena zrobi się długa/nudna), SFX bez pliku,
brak `topic` (fallback theme).

**Weryfikacja merytoryczna:** narracja twierdzi rzeczy o świecie (porty, API, liczby).
Rozszerzenie `social-verify` na clipy — **v2**. Na start: człowiek czyta scenariusz.

---

## 10. Publikacja — świadomie POZA zakresem v1

Reele autopublikują się na IG przez Zernio (wymaga hostowanych URL-i). Clipy celują w
**TikToka / Shorts / Reels**:
- TikTok Content Posting API i YouTube Data API to ciężkie integracje OAuth — osobny epik.
- Na v1: **render MP4 → ręczny upload.** To jest OK: wartość jest w produkcji wideo,
  nie w automacie wysyłki. Autopublikację dokładamy później, gdy format się sprawdzi.

---

## 11. Testy (PHP, bez bazy, keyless)

- parser scenariusza → DTO (sceny, narracje, kod),
- lint: nieznany `type` = ERROR, brak narracji = ERROR, budżety kodu,
- cache TTS: ta sama narracja → ten sam plik; zmiana → nowy hash,
- `MockTtsProvider`: cisza o długości ≈ słowa÷tempo, timestampy pokrywają całość,
- manifest: `durationInFrames` sceny = długość audio w klatkach; suma = długość kompozycji,
- registry: każdy dozwolony `type` ma komponent (test po stronie... patrz niżej),
- theme: `topic` → akcent/logo przez `TechThemeResolver` (współdzielone z kursami).

Kompletność registry to test w projekcie Remotion (`npm run lint` = `eslint + tsc`);
po stronie PHP test pilnuje, że lint zna wszystkie typy z `config('clip.scene_types')`.

---

## 12. Konfiguracja: `config/clip.php`

```php
'path'        => resource_path('clips'),
'fps'         => 30,
'canvas'      => ['width' => 1080, 'height' => 1920],
'tts' => [
    'driver'     => env('CLIP_TTS_DRIVER', 'mock'),   // mock | elevenlabs
    'words_per_min' => 150,                            // mock: szacunek długości
    'elevenlabs' => [
        'key'      => env('ELEVENLABS_API_KEY'),
        'model'    => 'eleven_multilingual_v2',
    ],
    'cache_path' => storage_path('app/clip-audio'),
],
'voices' => [ 'narrator_en' => env('CLIP_VOICE_EN', '<voice_id>') ],
'scene_types' => ['title','code-reveal','bullets','statement','compare','terminal','callout','outro'],
'sfx' => [ 'whoosh' => 'whoosh.mp3', 'pop' => 'pop.mp3', ... ],
'music' => [ /* v2 */ ],
'timing' => [ 'min' => 60, 'max' => 360, 'gap' => 6 ],  // klatki
'limits' => [ 'code_lines_max' => 8, 'code_cols_max' => 46, 'narration_max_words' => 45 ],
```

---

## 13. Plan wdrożenia (fazy)

1. **Szkielet PHP** — `Clip` DTO, parser scenariusza, `clip:lint`, `config/clip.php`,
   przykładowy `resources/clips/eloquent-n1-explainer.md`. Testy parsera + lintu.
2. **TTS z mockiem** — interfejs `TtsProvider`, `MockTtsProvider`, cache po hashu,
   `clip:tts`. Testy cache + długości ciszy. (Bez ElevenLabs — pipeline już renderuje niemo.)
3. **Manifest + stage** — `ClipStager`, `clip.json`, kopiowanie audio, `clip:stage`. Testy kształtu.
4. **Remotion: kompozycja Clip + 3 sceny** — `Clip.tsx`, `theme.ts`, `Title/CodeReveal/Bullets`,
   registry, rejestracja w `Root.tsx`. Pierwszy **niemy** MP4 end-to-end (`clip:render`).
5. **Napisy** — `Captions.tsx` z timestampów (na mocku: równe słowa). Retencja.
6. **Reszta scen** — `Statement/Compare/Terminal/Callout/Outro`.
7. **ElevenLabs realny** — `ElevenLabsProvider`, klucz w `.env`. Zamiana ciszy na głos bez
   zmiany reszty (interfejs się nie rusza).
8. **SFX** — mix `<Audio>` przy cue'ach (po skompletowaniu licencjonowanej biblioteki).
9. **v2:** muzyka, scena `diagram`, cue'y animacji na słowo, `clip-verify`, autopublikacja.

Po każdej fazie jest coś, co realnie działa — faza 4 daje pierwszy oglądalny film.

---

## 14. Otwarte kwestie (do decyzji przy realizacji, nie blokują startu)

- **Voice ElevenLabs:** który głos (męski/żeński, tempo) — wybierzesz przy fazie 7.
- **SFX biblioteka:** skąd bierzemy pliki CC0 — osobne zadanie kompletacji.
- **Napisy — styl:** karaoke (podświetlane słowo) vs blok — proponuję karaoke, do podglądu w f.5.
- **Długość docelowa filmu:** TikTok/Shorts lubią 20–40 s; lint może pilnować sumy narracji.
