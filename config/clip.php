<?php

/*
|--------------------------------------------------------------------------
| Moduł "clip" — narrowane wideo (TikTok / YouTube Shorts / Instagram Reels)
|--------------------------------------------------------------------------
|
| Clip to DRUGI, niezależny pipeline OBOK reela (`social:video`). Reel to niema
| karuzela w ruchu; clip to narrowany explainer: scenariusz -> narracja
| ElevenLabs -> Remotion składa sceny animowane zsynchronizowane z głosem ->
| napisy + SFX -> MP4 1080x1920.
|
| Ta sama DNA co reszta modułu social: źródłem prawdy jest plik .md w repo
| (resources/clips), zero bazy, zero crona. Audio to ARTEFAKT (wyliczalny ze
| scenariusza, ale kosztuje API) — cache w storage/app, gitignorowane, jak PNG-i.
|
| Pełna architektura: docs/narrated-video-architecture.md.
|
*/

return [

    /*
    |--------------------------------------------------------------------------
    | Katalog ze scenariuszami (.md)
    |--------------------------------------------------------------------------
    */

    'path' => env('CLIP_MD_PATH', resource_path('clips')),

    /*
    |--------------------------------------------------------------------------
    | Katalog eksportu (MP4)
    |--------------------------------------------------------------------------
    |
    | Współdzielony z reelem: storage/app/social-export/{slug}/clip.mp4.
    | storage/app/* jest gitignorowane — render NIGDY nie trafia do repo.
    |
    */

    'export_path' => env('CLIP_EXPORT_PATH', storage_path('app/social-export')),

    /*
    |--------------------------------------------------------------------------
    | Kanwa i klatkaż
    |--------------------------------------------------------------------------
    |
    | 9:16 1080x1920 pokrywa TikToka, Shorts i Reels naraz. 30 fps to standard
    | tych platform i tyle, ile trzeba dla płynnych napisów.
    |
    */

    'fps'    => (int) env('CLIP_FPS', 30),
    'canvas' => ['width' => 1080, 'height' => 1920],

    'default_language' => env('CLIP_LANG', 'en'),

    /*
    |--------------------------------------------------------------------------
    | Dozwolone typy scen
    |--------------------------------------------------------------------------
    |
    | Nieznany `type:` sceny to ERROR lintu — literówka wypadłaby z wideo bez
    | śladu (jak nieznany `formats:` w postach). Każdy typ MUSI mieć komponent
    | w bibliotece Remotiona (social-video/src/clip/scenes) — tego pilnuje tsc.
    |
    | `diagram` świadomie POZA v1 (najtrudniejszy — boxy + strzałki).
    |
    */

    'scene_types' => [
        'title', 'code-reveal', 'bullets', 'statement',
        'compare', 'terminal', 'callout', 'outro',
    ],

    /*
    |--------------------------------------------------------------------------
    | Text-to-speech (narracja)
    |--------------------------------------------------------------------------
    |
    | Provider za interfejsem TtsProvider. `mock` = cisza o oszacowanej długości
    | + syntetyczne timestampy: cały pipeline renderuje (niemo, z poprawnym
    | timingiem i napisami) BEZ klucza ElevenLabs. Głos podmieniasz później,
    | zmieniając driver — reszta się nie rusza.
    |
    */

    'tts' => [
        'driver' => env('CLIP_TTS_DRIVER', 'mock'),  // mock | elevenlabs

        // Mock: ile słów na minutę zakłada, licząc długość ciszy. 150 wpm to
        // spokojne tempo lektora — bliskie temu, co wyprodukuje ElevenLabs.
        'words_per_min' => (int) env('CLIP_TTS_WPM', 150),

        // Cache narracji: sha1(narracja + voiceId).mp3 + .json (timestampy).
        // Zmiana tekstu => nowy hash => regeneracja. Niezmieniona narracja
        // między renderami = zero kosztu API.
        'cache_path' => env('CLIP_TTS_CACHE', storage_path('app/clip-audio')),

        'elevenlabs' => [
            'key'      => env('ELEVENLABS_API_KEY'),
            'model'    => env('ELEVENLABS_MODEL', 'eleven_multilingual_v2'),
            'base_url' => env('ELEVENLABS_BASE_URL', 'https://api.elevenlabs.io'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Głosy
    |--------------------------------------------------------------------------
    |
    | Klucz z frontmattera `voice:` -> voice_id ElevenLabs. Mock ignoruje id
    | (i tak produkuje ciszę), więc pipeline działa z pustym env.
    |
    */

    'voices' => [
        'narrator_en' => env('CLIP_VOICE_EN', ''),
    ],

    'default_voice' => env('CLIP_DEFAULT_VOICE', 'narrator_en'),

    /*
    |--------------------------------------------------------------------------
    | SFX — mała biblioteka licencjonowana (CC0)
    |--------------------------------------------------------------------------
    |
    | Stałe, małe, wielokrotnie używane assety COMMITOWANE w social-video/public/sfx.
    | Scena deklaruje `sfx: whoosh` (nazwa z tej mapy). Brak pliku => WARNING lintu,
    | scena gra bez SFX. Licencja twarda: tylko CC0 z prawami komercyjnymi.
    |
    */

    'sfx' => [
        // 'whoosh' => 'whoosh.mp3',
        // 'pop'    => 'pop.mp3',
    ],

    /*
    |--------------------------------------------------------------------------
    | Muzyka (podkład) — v2
    |--------------------------------------------------------------------------
    */

    'music' => [
        // 'chill' => 'chill-bed.mp3',
    ],

    /*
    |--------------------------------------------------------------------------
    | Timing scen (klatki)
    |--------------------------------------------------------------------------
    |
    | Długość sceny bierze się z DŁUGOŚCI JEJ AUDIO (narracja wyznacza czas).
    | To są tylko bezpieczniki: `min`/`max` przycinają skrajności, `gap` to
    | oddech między scenami, `lead`/`tail` to cisza przed/po głosie w scenie.
    |
    */

    'timing' => [
        'min'  => 60,   // 2 s — nawet krótka scena musi dać się przeczytać
        'max'  => 360,  // 12 s — dłuższą narrację lepiej rozbić na sceny
        'gap'  => 0,    // klatki między scenami (0 = cięcie na styk)
        'lead' => 6,    // cisza przed narracją (wjazd wizualu)
        'tail' => 9,    // cisza po narracji (wyjazd / oddech)
    ],

    /*
    |--------------------------------------------------------------------------
    | Limity lintu (bramka formatu)
    |--------------------------------------------------------------------------
    |
    | Jak w social:lint — overflow to błąd AUTORSKI, nie renderu (CSS zawija
    | naprawdę, kanwa ma overflow:hidden). Kolumny kodu 46 policzone dla fontu
    | mono 30px na kanwie 1080 (patrz CLAUDE.md). Narracja liczona w SŁOWACH:
    | dłuższa niż budżet = scena robi się długa i nudna.
    |
    */

    'limits' => [
        'code_lines_max'      => 10,   // clip ma więcej pionu niż kafelek 4:5
        'code_cols_max'       => 46,
        'narration_max_words' => 45,   // ~18 s przy 150 wpm — górna granica sceny
        'total_max_words'     => 320,  // cały film ~2 min narracji; wyżej = WARNING
        'scenes_min'          => 2,
        'scenes_max'          => 30,
    ],

    /*
    |--------------------------------------------------------------------------
    | Napisy (burned-in)
    |--------------------------------------------------------------------------
    |
    | Główny driver retencji na TikToku/Shorts. `karaoke` = aktualne słowo
    | podświetlone; `block` = statyczny blok. Z timestampów ElevenLabs (mock:
    | słowa rozłożone równo).
    |
    */

    'captions' => [
        'enabled' => true,
        'mode'    => env('CLIP_CAPTIONS_MODE', 'karaoke'),  // karaoke | block
    ],

    /*
    |--------------------------------------------------------------------------
    | Marka
    |--------------------------------------------------------------------------
    */

    'brand' => [
        'domain' => env('SOCIAL_BRAND_DOMAIN', 'oatllo.com'),
        'handle' => env('SOCIAL_BRAND_HANDLE', '@oatllo'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Projekt Remotion
    |--------------------------------------------------------------------------
    |
    | TEN SAM projekt co reel (social-video/) — jedna instalacja Node, jedna
    | licencja. Reel i Clip to dwie kompozycje w jednym Root.tsx. Render lokalny.
    |
    */

    'video' => [
        'project_path'  => env('SOCIAL_VIDEO_PROJECT', base_path('social-video')),
        'composition'   => 'Clip',
        'timeout'       => (int) env('CLIP_RENDER_TIMEOUT', 900),
    ],
];
