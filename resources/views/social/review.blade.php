{{--
    Panel akceptacji postów (tylko DEV). Jeden post naraz, w kolejce tylko te bez
    aktualnego werdyktu.

    Slajdy są renderowane w <iframe> o DOKŁADNYCH wymiarach kanwy i skalowane
    transformem – tak samo jak w social.preview. Dzięki temu ekran akceptacji
    pokazuje to, co realnie zrzuci rasteryzator, a nie przybliżenie.

    Widoki social nie używają Tailwinda (patrz CLAUDE.md), więc ten plik też nie –
    panel nigdy nie wymaga `npm run css:public`.
--}}
<!doctype html>
<html lang="pl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>social review{{ $item ? ': ' . $item->post->slug : '' }}</title>
    <style>
        :root {
            --bg: #020617;
            --panel: #0b1220;
            --border: #1e293b;
            --text: #e2e8f0;
            --muted: #64748b;
            --green: #10b981;
            --red: #ef4444;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0; padding: 32px 20px 64px; background: var(--bg); color: var(--text);
            font-family: ui-sans-serif, system-ui, 'Segoe UI', sans-serif;
            display: flex; flex-direction: column; align-items: center; gap: 20px;
        }
        a { color: #94a3b8; }
        .queue { font-size: 13px; color: var(--muted); letter-spacing: .04em; text-transform: uppercase; }
        .queue b { color: var(--text); }
        .flash {
            font-size: 13px; padding: 8px 14px; border-radius: 999px;
            border: 1px solid var(--border); background: var(--panel); color: var(--muted);
        }
        .flash.approved { color: var(--green); border-color: #064e3b; }
        .flash.changes { color: var(--red); border-color: #7f1d1d; }
        .warn {
            font-size: 13px; color: #fbbf24; border: 1px solid #78350f; background: #1c1206;
            padding: 8px 14px; border-radius: 8px;
        }

        /* Karta "posta" – proporcje i chrom podpatrzone z feedu Instagrama:
           header z autorem, kanwa 4:5 (albo 9:16 dla story), pasek akcji, podpis. */
        .card {
            width: {{ ($canvas['width'] ?? 1080) * ($scale ?? 0.37) }}px;
            background: var(--panel); border: 1px solid var(--border); border-radius: 14px;
            overflow: hidden;
        }
        .card-head { display: flex; align-items: center; gap: 10px; padding: 12px 14px; }
        .avatar {
            width: 32px; height: 32px; border-radius: 50%; flex: none;
            background: linear-gradient(135deg, #f43f5e, #f59e0b);
            display: flex; align-items: center; justify-content: center;
            font-size: 13px; font-weight: 800; color: #0b1220;
        }
        .who { font-size: 13px; font-weight: 600; line-height: 1.3; }
        .who span { display: block; font-weight: 400; font-size: 11px; color: var(--muted); }

        .viewport { position: relative; background: #000; overflow: hidden; touch-action: pan-y; }
        .frame { position: absolute; inset: 0; opacity: 0; pointer-events: none; transition: opacity .12s; }
        .frame.on { opacity: 1; }
        .frame iframe { border: 0; transform-origin: top left; }

        /* Strefy przewijania na CAŁĄ wysokość kanwy – jak tapnięcie w obrazek na
           Instagramie. Wcześniej klikalne było tylko 30-pikselowe kółko wciśnięte
           w nagłówek, a sama kanwa ma pointer-events:none (żeby iframe nie łapał
           zdarzeń), więc kliknięcie w obrazek nie robiło NIC. */
        .nav {
            position: absolute; top: 0; bottom: 0; width: 42%; border: 0; cursor: pointer;
            background: transparent; padding: 0;
            display: flex; align-items: center;
            color: #fff; font-size: 20px; line-height: 1;
            transition: background .15s;
        }
        .nav.prev { left: 0; justify-content: flex-start; }
        .nav.next { right: 0; justify-content: flex-end; }
        /* Chevron jest zawsze widoczny (ciemne kółko + obwódka), bo na ciemnym
           slajdzie sam biały znak potrafił zniknąć w tle. */
        .nav span {
            width: 34px; height: 34px; margin: 0 10px; border-radius: 50%;
            background: rgba(2,6,23,.72); border: 1px solid rgba(226,232,240,.35);
            display: flex; align-items: center; justify-content: center;
            transition: transform .15s, background .15s;
        }
        .nav:hover span { background: rgba(2,6,23,.95); transform: scale(1.12); }
        .nav.prev:hover { background: linear-gradient(to right, rgba(2,6,23,.35), transparent); }
        .nav.next:hover { background: linear-gradient(to left, rgba(2,6,23,.35), transparent); }
        /* Na krańcach strefa jest WYGASZONA, ale nadal włączona. Atrybut `disabled`
           byłby tu pułapką: Chromium nie wysyła ŻADNYCH zdarzeń wskaźnika na
           wyłączonym przycisku, a ta strefa przykrywa 42% szerokości kanwy – więc
           na pierwszym slajdzie martwa robiła się cała lewa połowa obrazka (klik
           nic, a swipe zaczęty tam nie dostawał nawet pointerdown). */
        .nav.off { cursor: default; }
        .nav.off span { opacity: .2; }
        .nav.off:hover { background: transparent; }
        .nav.off:hover span { transform: none; }

        /* Przechodzenie między POSTAMI – bez wydawania werdyktu. */
        .posts { display: flex; align-items: center; gap: 12px; font-size: 13px; color: var(--muted); }
        .posts a, .posts span.off {
            border: 1px solid var(--border); border-radius: 8px; padding: 7px 14px;
            text-decoration: none; color: #cbd5e1;
        }
        .posts a:hover { border-color: #475569; color: #fff; }
        .posts span.off { opacity: .35; }
        .counter {
            position: absolute; top: 10px; right: 10px; padding: 3px 9px; border-radius: 999px;
            background: rgba(15,23,42,.75); font-size: 11px; color: #e2e8f0;
        }
        .dots { display: flex; justify-content: center; gap: 5px; padding: 10px 0 4px; }
        .dot { width: 6px; height: 6px; border-radius: 50%; background: #334155; border: 0; padding: 0; cursor: pointer; }
        .dot.on { background: #38bdf8; }

        .caption { padding: 4px 14px 16px; font-size: 13px; line-height: 1.5; }
        .caption .hook { color: var(--text); }
        .caption .rest { color: #cbd5e1; white-space: pre-wrap; }
        .caption .tags { color: #38bdf8; margin-top: 8px; word-break: break-word; }
        .caption .over { color: var(--red); }

        {{-- Notatka MUSI wyglądać inaczej niż podpis i stać POZA atrapą telefonu.
             Wcześniej notatki produkcyjne siedziały w `caption` i panel rysował je
             dokładnie tam, gdzie Instagram rysuje podpis – czyli mówił „to idzie
             w świat" o instrukcji dla autora. Amber, ikona i ramka, bo jedyne
             zadanie tego bloku to NIE dać się pomylić z treścią posta. --}}
        .notes {
            margin: 12px auto 0; max-width: 400px; display: flex; gap: 8px;
            padding: 10px 12px; border: 1px dashed #a16207; border-radius: 8px;
            background: #78350f1a; color: #fcd34d; font-size: 12px; line-height: 1.5;
        }
        .notes .what { font-weight: 700; text-transform: uppercase; letter-spacing: .04em; font-size: 10px; color: #f59e0b; }
        .notes .text { white-space: pre-wrap; color: #fde68a; }

        {{-- Weryfikacja merytoryczna: stan MUSI być czytelny zanim klikniesz zielone.
             Lint pilnuje formatu, to pilnuje prawdy — a nieaktualna pieczątka jest
             groźniejsza niż jej brak, bo wygląda jak zielone światło. --}}
        .verify {
            margin: 12px auto 0; max-width: 400px; display: flex; gap: 10px;
            padding: 10px 12px; border-radius: 8px; font-size: 12px; line-height: 1.5;
            border: 1px solid var(--rule); background: #0f172a;
        }
        .verify .mark { font-size: 15px; line-height: 1.2; flex: none; }
        .verify .what { font-weight: 700; font-size: 10px; text-transform: uppercase; letter-spacing: .04em; }
        .verify .detail { color: var(--muted); margin-top: 3px; }
        .verify ul { margin: 4px 0 0; padding-left: 16px; color: #94a3b8; }
        .verify .text { white-space: pre-wrap; color: #cbd5e1; margin-top: 4px; }

        .verify.ok      { border-color: #14532d; background: #052e16; }
        .verify.ok .what { color: var(--green); }
        .verify.issues  { border-color: #78350f; background: #2a1405; }
        .verify.issues .what { color: #fbbf24; }
        .verify.stale   { border-color: #7f1d1d; background: #2a0a0a; }
        .verify.stale .what { color: var(--red); }
        .verify.none    { border-style: dashed; }
        .verify.none .what { color: var(--muted); }

        .meta { font-size: 12px; color: var(--muted); display: flex; flex-wrap: wrap; gap: 10px; justify-content: center; align-items: center; }
        .meta code { color: #94a3b8; }
        .linky {
            background: none; border: 0; padding: 0; font: inherit; color: #94a3b8;
            text-decoration: underline; cursor: pointer;
        }
        .linky:hover { color: #fff; }

        /* Wszystkie slajdy naraz – w modalu, nie w nowej karcie: przy zatwierdzaniu
           postów liczy się to, żeby nie tracić kontekstu i nie wracać zakładkami. */
        dialog.all { width: auto; max-width: 96vw; padding: 16px; }
        dialog.all h2 { margin: 0 0 12px; font-size: 15px; font-weight: 600; }
        .strip { display: flex; gap: 14px; overflow-x: auto; padding-bottom: 6px; }
        .thumb { flex: none; }
        .thumb .no { font-size: 11px; color: var(--muted); margin-bottom: 6px; }
        .thumb .box { overflow: hidden; border-radius: 8px; border: 1px solid var(--border); background: #000; }
        .thumb iframe { border: 0; transform-origin: top left; display: block; }

        .actions { display: flex; gap: 14px; }
        .btn {
            border: 0; border-radius: 10px; padding: 14px 28px; font-size: 15px; font-weight: 700;
            cursor: pointer; color: #06111f; min-width: 190px;
        }
        .btn.green { background: var(--green); }
        .btn.red { background: var(--red); color: #fff; }
        .btn:hover { filter: brightness(1.1); }
        .hint { font-size: 12px; color: var(--muted); }

        dialog {
            border: 1px solid var(--border); border-radius: 14px; background: var(--panel);
            color: var(--text); width: 460px; max-width: 92vw; padding: 20px;
        }
        dialog::backdrop { background: rgba(2,6,23,.7); }
        dialog h2 { margin: 0 0 4px; font-size: 17px; }
        dialog p { margin: 0 0 14px; font-size: 13px; color: var(--muted); }
        textarea {
            width: 100%; min-height: 130px; resize: vertical; padding: 10px;
            background: #020617; color: var(--text); border: 1px solid var(--border);
            border-radius: 8px; font: inherit; font-size: 13px;
        }
        textarea:focus { outline: 2px solid var(--red); outline-offset: 1px; }
        .dialog-actions { display: flex; justify-content: flex-end; gap: 10px; margin-top: 14px; }
        .btn.ghost { background: transparent; border: 1px solid var(--border); color: var(--muted); min-width: 0; padding: 10px 16px; font-weight: 600; }
        .error { color: var(--red); font-size: 12px; margin-top: 8px; }

        .done { text-align: center; max-width: 560px; }
        .done h1 { font-size: 22px; margin: 0 0 8px; }
        .done p { color: var(--muted); font-size: 14px; line-height: 1.6; }
        .tally { display: flex; gap: 10px; justify-content: center; margin: 18px 0 8px; flex-wrap: wrap; }
        .pill { border: 1px solid var(--border); border-radius: 999px; padding: 6px 14px; font-size: 13px; }
        .pill b { color: var(--text); }
        .pill.green b { color: var(--green); }
        .pill.red b { color: var(--red); }
    </style>
</head>
<body>

@if(session('reviewed'))
    <div class="flash {{ session('reviewed')['verdict'] }}">
        Zapisano: <b>{{ session('reviewed')['slug'] }}</b> –
        {{ session('reviewed')['verdict'] === 'approved' ? 'nadaje się do publikacji' : 'do poprawy' }}
    </div>
@endif

@if($broken > 0)
    <div class="warn">{{ $broken }} plik(ów) posta nie da się sparsować – odpal <code>php artisan social:lint</code>.</div>
@endif

@if($item === null)
    {{-- Kolejka pusta: nie ma nic bez aktualnego werdyktu. --}}
    <div class="done">
        <h1>Przejrzane. Kolejka pusta.</h1>
        <div class="tally">
            <span class="pill green">zielone: <b>{{ $summary['approved'] }}</b></span>
            <span class="pill red">do poprawy: <b>{{ $summary['changes'] }}</b></span>
            <span class="pill">postów: <b>{{ $summary['total'] }}</b></span>
        </div>
        <p style="margin-bottom: 14px;">
            <a href="{{ route('social.calendar') }}">Zobacz kalendarz zaakceptowanych &#8250;</a>
        </p>
        <p>
            @if($summary['changes'] > 0)
                Napisz w Claude Code: <b>„przejrzałem zaplanowane posty i teraz je opracuj”</b> –
                skill <code>social-review</code> weźmie {{ $summary['changes'] }} post(ów) z powodami
                i je poprawi. Po poprawce wrócą tu automatycznie do ponownego obejrzenia.
            @else
                Wszystko z zielonym światłem. Nie ma czego poprawiać.
            @endif
        </p>
    </div>
@else
    @php($post = $item->post)
    @php($captionLen = mb_strlen($post->captionWithHashtags()))
    @php($captionMax = (int) config('social.limits.caption_max', 2200))

    <div class="queue">
        Do przejrzenia: <b>{{ $summary['pending'] }}</b> z {{ $summary['total'] }}
        &middot; <a href="{{ route('social.calendar') }}" style="text-transform: none; letter-spacing: 0;">kalendarz</a>
    </div>

    <div class="card">
        <div class="card-head">
            <div class="avatar">O</div>
            <div class="who">
                {{ ltrim(config('social.brand.handle', '@oatllo'), '@') }}
                <span>{{ $post->type->label() }} &middot; {{ $canvas['width'] }}x{{ $canvas['height'] }}</span>
            </div>
        </div>

        <div class="viewport" style="height: {{ $canvas['height'] * $scale }}px;">
            @foreach($post->slides as $slide)
                <div class="frame {{ $loop->first ? 'on' : '' }}" data-slide="{{ $slide->index }}">
                    <iframe src="{{ route('social.slide', ['slug' => $post->slug, 'index' => $slide->index]) }}"
                            title="{{ $slide->number() }}"
                            style="width: {{ $canvas['width'] }}px; height: {{ $canvas['height'] }}px; transform: scale({{ $scale }});"
                            loading="eager"></iframe>
                </div>
            @endforeach

            @if($post->slideCount() > 1)
                <span class="counter" data-counter>1/{{ $post->slideCount() }}</span>
                <button class="nav prev off" data-prev aria-label="Poprzedni slajd" aria-disabled="true"><span>&#8249;</span></button>
                <button class="nav next" data-next aria-label="Następny slajd"><span>&#8250;</span></button>
            @endif
        </div>

        @if($post->slideCount() > 1)
            <div class="dots">
                @foreach($post->slides as $slide)
                    <button class="dot {{ $loop->first ? 'on' : '' }}" data-dot="{{ $slide->index }}"
                            aria-label="Slajd {{ $slide->index }}"></button>
                @endforeach
            </div>
        @endif

        <div class="caption">
            <div class="rest"><span class="hook"><b>{{ ltrim(config('social.brand.handle', '@oatllo'), '@') }}</b> </span>{{ $post->caption }}</div>
            @if($post->hashtags !== [])
                <div class="tags">{{ collect($post->hashtags)->map(fn ($t) => '#' . $t)->implode(' ') }}</div>
            @endif
            <div class="{{ $captionLen > $captionMax ? 'over' : '' }}" style="margin-top:8px; font-size:11px; color:{{ $captionLen > $captionMax ? '#ef4444' : '#475569' }};">
                podpis: {{ $captionLen }}/{{ $captionMax }} znaków &middot; hashtagów: {{ count($post->hashtags) }}
            </div>
        </div>
    </div>

    {{-- Stan weryfikacji liczymy z tego samego odcisku, co werdykt człowieka:
         `SocialReviewItem::$fingerprint` to sha1 treści BEZ bloku `verified:`,
         czyli dokładnie to, czego dotyczy pieczątka. Tożsamość treści jest jedna. --}}
    @php($vState = $post->verified === null
        ? 'none'
        : (! $post->verified->matches($item->fingerprint)
            ? 'stale'
            : ($post->verified->isApproved() ? 'approved' : 'issues')))

    <div class="verify {{ $vState === 'approved' ? 'ok' : ($vState === 'issues' ? 'issues' : ($vState === 'stale' ? 'stale' : 'none')) }}">
        <div class="mark">{{ ['approved' => '✓', 'issues' => '⚠', 'stale' => '✗', 'none' => '·'][$vState] }}</div>
        <div>
            <div class="what">
                @switch($vState)
                    @case('approved') Zweryfikowane merytorycznie @break
                    @case('issues')   Zweryfikowane &mdash; z uwagami @break
                    @case('stale')    Weryfikacja NIEAKTUALNA &mdash; treść zmieniona po sprawdzeniu @break
                    @default          Niezweryfikowane
                @endswitch
            </div>

            @if($vState === 'none')
                <div class="detail">Nikt nie sprawdził faktów w tym poście. Lint pilnuje tylko formatu.</div>
            @elseif($vState === 'stale')
                <div class="detail">Pieczątka z {{ $post->verified?->at?->format('Y-m-d H:i') }} dotyczy innej wersji. Traktuj jak niezweryfikowane.</div>
            @else
                <div class="detail">Claude, {{ $post->verified?->at?->format('Y-m-d H:i') }}</div>

                @if($post->verified?->checks !== [])
                    <ul>
                        @foreach($post->verified?->checks ?? [] as $check)
                            <li>{{ $check }}</li>
                        @endforeach
                    </ul>
                @endif

                @if(trim((string) $post->verified?->notes) !== '')
                    <div class="text">{{ $post->verified?->notes }}</div>
                @endif
            @endif
        </div>
    </div>

    @if($post->hasNotes())
        <div class="notes">
            <div>
                <div class="what">Przy wrzucaniu &middot; nie idzie w świat</div>
                <div class="text">{{ $post->notes }}</div>
            </div>
        </div>
    @endif

    <div class="meta">
        <span><code>{{ $post->slug }}</code></span>
        <span>status: <code>{{ $post->status }}</code></span>
        <span>publish_at: <code>{{ $post->publishAt?->format('Y-m-d H:i') ?? '—' }}</code></span>
        @if($item->isStale())
            <span style="color:#fbbf24;">post zmieniony po ostatnim werdykcie</span>
        @endif
        <button class="linky" data-open-all>wszystkie slajdy obok siebie</button>
        <a href="{{ route('social.styles', ['slug' => $post->slug]) }}" target="_blank">ten post we wszystkich stylach</a>
    </div>

    <div class="actions">
        <button class="btn red" data-open-modal>Do poprawy</button>
        <form method="POST" action="{{ route('social.review.store', ['slug' => $post->slug]) }}">
            @csrf
            <input type="hidden" name="verdict" value="approved">
            <button class="btn green" type="submit">OK, nadaje się</button>
        </form>
    </div>
    {{-- Oglądanie kolejnych postów BEZ werdyktu. Werdykt zdejmuje post z kolejki,
         więc te linki są jedynym sposobem, żeby coś odłożyć na później. --}}
    <div class="posts">
        @if($cursor > 0)
            <a href="{{ route('social.review', ['i' => $cursor - 1]) }}">&#8249; poprzedni post</a>
        @else
            <span class="off">&#8249; poprzedni post</span>
        @endif

        <span>post {{ $cursor + 1 }} z {{ $pending }}</span>

        @if($cursor + 1 < $pending)
            <a href="{{ route('social.review', ['i' => $cursor + 1]) }}">następny post (bez oceny) &#8250;</a>
        @else
            <span class="off">następny post &#8250;</span>
        @endif
    </div>

    <div class="hint">Slajdy: klikaj w obrazek (lewa/prawa strona), strzałki na klawiaturze albo przeciągnij.</div>

    {{-- Miniatury dociągane dopiero przy otwarciu (data-src), żeby wejście na panel
         nie ładowało każdego slajdu DWA razy – raz w karuzeli, raz tutaj. --}}
    @php($thumb = round(420 / $canvas['height'], 4))
    <dialog class="all" data-all-modal>
        <h2>{{ $post->slug }} &middot; {{ $post->slideCount() }} slajdów</h2>
        <div class="strip">
            @foreach($post->slides as $slide)
                <div class="thumb">
                    <div class="no">{{ $slide->number() }} &middot; {{ $slide->role }}</div>
                    <div class="box" style="width: {{ round($canvas['width'] * $thumb) }}px; height: {{ round($canvas['height'] * $thumb) }}px;">
                        <iframe data-src="{{ route('social.slide', ['slug' => $post->slug, 'index' => $slide->index]) }}"
                                title="{{ $slide->number() }}"
                                style="width: {{ $canvas['width'] }}px; height: {{ $canvas['height'] }}px; transform: scale({{ $thumb }});"></iframe>
                    </div>
                </div>
            @endforeach
        </div>
        <div class="dialog-actions">
            <button class="btn ghost" type="button" data-close-all>Zamknij (Esc)</button>
        </div>
    </dialog>

    <dialog data-modal>
        <form method="POST" action="{{ route('social.review.store', ['slug' => $post->slug]) }}">
            @csrf
            <input type="hidden" name="verdict" value="changes">
            <h2>Co jest do poprawy?</h2>
            <p>Powód trafi do <code>resources/social/reviews/{{ $post->slug }}.md</code> i to na jego podstawie post zostanie przerobiony.</p>
            <textarea name="reason" data-reason required
                      placeholder="Np. slajd 3 ma za dużo tekstu, hook nie zatrzymuje, przykład kodu jest nieprawdziwy...">{{ old('reason') }}</textarea>
            @error('reason')<div class="error">{{ $message }}</div>@enderror
            <div class="dialog-actions">
                <button class="btn ghost" type="button" data-close-modal>Anuluj</button>
                <button class="btn red" type="submit">Zapisz powód</button>
            </div>
        </form>
    </dialog>

    <script>
        (function () {
            const frames = [...document.querySelectorAll('[data-slide]')];
            const dots = [...document.querySelectorAll('[data-dot]')];
            const counter = document.querySelector('[data-counter]');
            const prev = document.querySelector('[data-prev]');
            const next = document.querySelector('[data-next]');
            let current = 0;

            function show(i) {
                current = Math.max(0, Math.min(i, frames.length - 1));
                frames.forEach((f, n) => f.classList.toggle('on', n === current));
                dots.forEach((d, n) => d.classList.toggle('on', n === current));
                if (counter) counter.textContent = (current + 1) + '/' + frames.length;
                // Wygaszamy klasą, nie atrybutem `disabled` – patrz komentarz przy .nav.off.
                // show() i tak przycina zakres, więc klik w wygaszoną strefę jest no-opem.
                toggleOff(prev, current === 0);
                toggleOff(next, current === frames.length - 1);
            }

            function toggleOff(btn, off) {
                if (! btn) return;
                btn.classList.toggle('off', off);
                btn.setAttribute('aria-disabled', off ? 'true' : 'false');
            }

            prev?.addEventListener('click', () => show(current - 1));
            next?.addEventListener('click', () => show(current + 1));
            dots.forEach((d, n) => d.addEventListener('click', () => show(n)));

            const modal = document.querySelector('[data-modal]');
            const reason = document.querySelector('[data-reason]');

            document.querySelector('[data-open-modal]')?.addEventListener('click', () => {
                modal.showModal();
                reason.focus();
            });
            document.querySelector('[data-close-modal]')?.addEventListener('click', () => modal.close());

            // Wszystkie slajdy obok siebie – w modalu. Źródła podstawiamy przy
            // pierwszym otwarciu, potem iframe'y zostają załadowane.
            const allModal = document.querySelector('[data-all-modal]');

            document.querySelector('[data-open-all]')?.addEventListener('click', () => {
                allModal.querySelectorAll('iframe[data-src]').forEach((f) => {
                    f.src = f.dataset.src;
                    delete f.dataset.src;
                });
                allModal.showModal();
            });
            document.querySelector('[data-close-all]')?.addEventListener('click', () => allModal.close());

            // Walidacja odbija się serwerem – wtedy modal ma być od razu otwarty,
            // inaczej powód zniknąłby razem z komunikatem błędu.
            @if($errors->has('reason'))
                modal.showModal();
            @endif

            document.addEventListener('keydown', (e) => {
                // Strzałki przewijają karuzelę tylko wtedy, gdy nie stoi nad nią modal.
                if (modal.open || allModal.open) return;
                if (e.key === 'ArrowLeft') show(current - 1);
                if (e.key === 'ArrowRight') show(current + 1);
            });

            // Przeciąganie – tak się przewija karuzelę na telefonie, więc ręka sama
            // to robi także myszą. Pointer events łapią i mysz, i dotyk jednym kodem.
            const viewport = document.querySelector('.viewport');
            let startX = null;
            let dragged = false;

            // `dragged` kasujemy TU, na starcie gestu, a nie licząc na click –
            // przeciągnięcie ze strefy na kanwę kończy się na innym elemencie,
            // więc click W OGÓLE nie leci i flaga zostawałaby true na zawsze,
            // zjadając następne prawdziwe kliknięcie.
            viewport?.addEventListener('pointerdown', (e) => { startX = e.clientX; dragged = false; });

            // pointerup łapiemy na OKNIE, nie na kanwie: kanwa ma tylko ~400px
            // szerokości, więc przeciągnięcie zaczęte przy jej krawędzi kończy się
            // poza nią – przy nasłuchu na kanwie taki gest po prostu ginął.
            window.addEventListener('pointerup', (e) => {
                if (startX === null) return;
                const dx = e.clientX - startX;
                startX = null;
                // Próg 40px: mniejszy ruch to klik w strefę, nie przeciągnięcie –
                // inaczej drgnięcie ręki przy kliknięciu przewijałoby dwa razy.
                if (Math.abs(dx) < 40) return;
                dragged = true;
                show(current + (dx < 0 ? 1 : -1));
            });
            window.addEventListener('pointercancel', () => { startX = null; });

            // Przeciągnięcie zaczęte na strefie klikania kończy się TAKŻE klikiem w nią
            // (pointerdown i pointerup na tym samym elemencie) – bez tego jedno
            // pociągnięcie przeskakiwałoby o dwa slajdy. Faza przechwytywania łapie
            // zdarzenie zanim dojdzie do przycisku.
            viewport?.addEventListener('click', (e) => {
                if (! dragged) return;
                dragged = false;
                e.stopPropagation();
                e.preventDefault();
            }, true);
        })();
    </script>
@endif

</body>
</html>
