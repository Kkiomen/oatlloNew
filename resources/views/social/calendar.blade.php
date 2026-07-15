{{--
    Kalendarz zaakceptowanych treści (tylko DEV).

    Jednostką jest para (post × format): karuzela z `formats: [post, reel]` daje tego
    samego dnia DWIE pozycje, bo to dwie osobne publikacje. Dzień z samymi
    nieocenionymi postami dostaje osobny znacznik – inaczej wyglądałby jak wolny.

    Widoki social nie używają Tailwinda (patrz CLAUDE.md), więc ten plik też nie.
--}}
<!doctype html>
<html lang="pl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>kalendarz social: {{ $month->format("m.Y") }}</title>
    <style>
        :root { --bg: #020617; --panel: #0b1220; --border: #1e293b; --text: #e2e8f0; --muted: #64748b; --green: #10b981; }
        * { box-sizing: border-box; }
        body {
            margin: 0; padding: 28px 24px 64px; background: var(--bg); color: var(--text);
            font-family: ui-sans-serif, system-ui, 'Segoe UI', sans-serif;
        }
        a { color: #94a3b8; }
        .top { display: flex; align-items: center; gap: 16px; flex-wrap: wrap; margin-bottom: 6px; }
        h1 { font-size: 20px; margin: 0; text-transform: capitalize; }
        .nav a, .today {
            border: 1px solid var(--border); border-radius: 8px; padding: 6px 12px;
            text-decoration: none; font-size: 13px; color: #cbd5e1;
        }
        .nav a:hover, .today:hover { border-color: #475569; color: #fff; }
        .nav { display: flex; gap: 8px; }
        .spacer { flex: 1; }
        .legend { display: flex; gap: 12px; font-size: 12px; color: var(--muted); flex-wrap: wrap; }
        .legend i { width: 9px; height: 9px; border-radius: 3px; display: inline-block; margin-right: 5px; }
        .sub { font-size: 13px; color: var(--muted); margin: 10px 0 20px; }
        .sub b { color: var(--text); }

        /* minmax(0, 1fr), nie 1fr: domyślne `min-width: auto` nie pozwala kolumnie
           zejść poniżej szerokości treści, a slugi są długie i mają nowrap – siatka
           rozpychała się wtedy poza ekran i ucinała niedzielę. */
        .grid { display: grid; grid-template-columns: repeat(7, minmax(0, 1fr)); gap: 8px; }
        .dow { font-size: 11px; color: var(--muted); text-transform: uppercase; letter-spacing: .08em; padding-bottom: 4px; }
        .cell {
            min-height: 122px; border: 1px solid var(--border); border-radius: 10px;
            background: var(--panel); padding: 8px; display: flex; flex-direction: column; gap: 5px;
        }
        .cell.out { opacity: .38; }
        .cell.today { border-color: #0e7490; }
        .cell .num { font-size: 12px; color: var(--muted); display: flex; justify-content: space-between; align-items: center; }
        .cell.today .num b { color: #38bdf8; }
        .cell .num a { text-decoration: none; }

        /* Pozycja = format + slug. Kropka niesie kolor formatu, bo w kafelku dnia
           nie ma miejsca na pełną etykietę przy każdej pozycji. */
        .entry {
            display: flex; align-items: center; gap: 6px; font-size: 11px; line-height: 1.3;
            background: #111c30; border: 1px solid #1e293b; border-radius: 6px; padding: 4px 6px;
            text-decoration: none; color: #cbd5e1; overflow: hidden;
        }
        .entry:hover { border-color: #475569; color: #fff; }
        .entry i { width: 8px; height: 8px; border-radius: 2px; flex: none; }
        .entry .t { color: var(--muted); flex: none; font-variant-numeric: tabular-nums; }
        /* min-width: 0 – bez tego element flex nie zejdzie poniżej treści i ellipsis
           nigdy się nie włączy (długi slug rozpycha kafelek zamiast się skrócić). */
        .entry .s { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; min-width: 0; }
        .entry.motion { border-style: dashed; }

        .pending { font-size: 10px; color: #fbbf24; }

        .day-panel {
            margin-top: 26px; border: 1px solid var(--border); border-radius: 12px;
            background: var(--panel); padding: 18px;
        }
        .day-panel h2 { margin: 0 0 4px; font-size: 16px; }
        .day-panel .hint { font-size: 12px; color: var(--muted); margin-bottom: 14px; }
        .row {
            display: flex; align-items: center; gap: 12px; padding: 10px 0;
            border-top: 1px solid #16233a; font-size: 13px;
        }
        .row:first-of-type { border-top: 0; }
        .tag {
            font-size: 11px; font-weight: 700; border-radius: 999px; padding: 3px 10px;
            flex: none; color: #06111f;
        }
        .row .time { color: var(--muted); font-variant-numeric: tabular-nums; flex: none; }
        .row .slug { font-weight: 600; }
        .row .type { color: var(--muted); font-size: 12px; }
        .row .links { margin-left: auto; display: flex; gap: 10px; font-size: 12px; }
        .row .slug { background: none; border: 0; padding: 0; font: inherit; font-weight: 600;
                     color: var(--text); cursor: pointer; text-decoration: underline; text-decoration-color: #334155; }
        .row .slug:hover { text-decoration-color: #94a3b8; }
        .empty { color: var(--muted); font-size: 13px; }

        /* Podgląd w modalu, nie w nowej karcie: przy przeglądaniu planu liczy się
           zerknięcie bez gubienia miesiąca, do którego trzeba potem wracać. */
        dialog {
            border: 1px solid var(--border); border-radius: 14px; background: var(--panel);
            color: var(--text); width: auto; max-width: 96vw; padding: 18px;
        }
        dialog::backdrop { background: rgba(2,6,23,.72); }
        .pv-head { display: flex; align-items: center; gap: 10px; margin-bottom: 14px; }
        .pv-head .slug { font-size: 15px; font-weight: 700; }
        .pv-head .meta { font-size: 12px; color: var(--muted); }
        .pv-head .links { margin-left: auto; display: flex; gap: 12px; font-size: 12px; }
        .pv-strip { display: flex; gap: 14px; overflow-x: auto; padding-bottom: 6px; }
        .pv-slide { flex: none; }
        .pv-slide .no { font-size: 11px; color: var(--muted); margin-bottom: 6px; }
        .pv-slide .box { overflow: hidden; border-radius: 8px; border: 1px solid var(--border); background: #000; }
        .pv-slide iframe { border: 0; transform-origin: top left; display: block; }
        .pv-close {
            margin-top: 14px; float: right; border: 1px solid var(--border); background: transparent;
            color: var(--muted); border-radius: 8px; padding: 8px 14px; font: inherit; font-size: 12px; cursor: pointer;
        }
        .pv-close:hover { color: #fff; border-color: #475569; }
    </style>
</head>
<body>

    <div class="top">
        <h1>{{ $month->locale("pl")->translatedFormat("F Y") }}</h1>
        <div class="nav">
            <a href="{{ route('social.calendar', ['m' => $month->subMonth()->format('Y-m')]) }}">&#8249; poprzedni</a>
            <a href="{{ route('social.calendar', ['m' => $month->addMonth()->format('Y-m')]) }}">następny &#8250;</a>
        </div>
        <a class="today" href="{{ route('social.calendar') }}">dziś</a>
        <div class="spacer"></div>
        <div class="legend">
            @foreach($formats as $key => $format)
                <span><i style="background: {{ $format['color'] }}"></i>{{ $format['label'] }}</span>
            @endforeach
        </div>
    </div>

    <div class="sub">
        Tylko <b>zaakceptowane</b> ({{ $summary['approved'] }}) &middot;
        do przejrzenia: {{ $summary['pending'] }} &middot; do poprawy: {{ $summary['changes'] }} &middot;
        <a href="{{ route('social.review') }}">wróć do oceniania</a>
    </div>

    <div class="grid">
        @foreach(['pon', 'wt', 'śr', 'czw', 'pt', 'sob', 'ndz'] as $dow)
            <div class="dow">{{ $dow }}</div>
        @endforeach

        @foreach($days as $cell)
            @php($isToday = $cell['date']->isToday())
            <div class="cell {{ $cell['inMonth'] ? '' : 'out' }} {{ $isToday ? 'today' : '' }}">
                <div class="num">
                    <b>{{ $cell['date']->day }}</b>
                    @if($cell['entries']->isNotEmpty())
                        <a href="{{ route('social.calendar', ['m' => $month->format('Y-m'), 'day' => $cell['date']->format('Y-m-d')]) }}">{{ $cell['entries']->count() }}</a>
                    @endif
                </div>

                {{-- Zostaje <a href>, mimo że klik otwiera modal: dzięki temu ctrl/środkowy
                     przycisk nadal otwiera podgląd w nowej karcie, gdy ktoś tego chce. --}}
                @foreach($cell['entries'] as $entry)
                    <a class="entry {{ $entry->isMotion() ? 'motion' : '' }}"
                       href="{{ route('social.preview', ['slug' => $entry->post()->slug]) }}"
                       data-preview
                       data-slug="{{ $entry->post()->slug }}"
                       data-slides="{{ $entry->post()->slideCount() }}"
                       data-w="{{ $entry->post()->type->width() }}"
                       data-h="{{ $entry->post()->type->height() }}"
                       data-label="{{ $entry->label() }}"
                       data-color="{{ $entry->color() }}"
                       data-time="{{ $entry->time() }}"
                       data-type="{{ $entry->post()->type->label() }}"
                       title="{{ $entry->label() }} &middot; {{ $entry->post()->slug }} &middot; {{ $entry->time() }}">
                        <i style="background: {{ $entry->color() }}"></i>
                        <span class="t">{{ $entry->time() }}</span>
                        <span class="s">{{ $entry->post()->slug }}</span>
                    </a>
                @endforeach

                {{-- Dzień z samymi nieocenionymi postami wyglądałby jak wolny. --}}
                @if($cell['unsettled'] > 0)
                    <span class="pending">+{{ $cell['unsettled'] }} nieocenione</span>
                @endif
            </div>
        @endforeach
    </div>

    @if($day !== null)
        <div class="day-panel">
            <h2>{{ $day->locale("pl")->translatedFormat("l, j F Y") }}</h2>
            <div class="hint">{{ $dayEntries->count() }} zaakceptowanych publikacji tego dnia</div>

            @forelse($dayEntries as $entry)
                <div class="row">
                    <span class="tag" style="background: {{ $entry->color() }}">{{ $entry->label() }}</span>
                    <span class="time">{{ $entry->time() ?? '—' }}</span>
                    <button class="slug" data-preview
                            data-slug="{{ $entry->post()->slug }}"
                            data-slides="{{ $entry->post()->slideCount() }}"
                            data-w="{{ $entry->post()->type->width() }}"
                            data-h="{{ $entry->post()->type->height() }}"
                            data-label="{{ $entry->label() }}"
                            data-color="{{ $entry->color() }}"
                            data-time="{{ $entry->time() }}"
                            data-type="{{ $entry->post()->type->label() }}">{{ $entry->post()->slug }}</button>
                    <span class="type">{{ $entry->post()->type->label() }} &middot; {{ $entry->post()->slideCount() }} slajd(ów)</span>
                    <span class="links">
                        <a href="{{ route('social.styles', ['slug' => $entry->post()->slug]) }}" target="_blank">style</a>
                    </span>
                </div>
            @empty
                <div class="empty">Nic zaakceptowanego na ten dzień.</div>
            @endforelse
        </div>
    @endif

    {{-- Modal podglądu. Pusty w HTML-u i wypełniany dopiero po kliknięciu: w siatce
         bywa kilkadziesiąt pozycji, a każdy slajd to osobny dokument w <iframe> –
         wstępne załadowanie wszystkich zabiłoby stronę. --}}
    <dialog data-preview-modal>
        <div class="pv-head">
            <span class="tag" data-pv-tag></span>
            <span class="slug" data-pv-slug></span>
            <span class="meta" data-pv-meta></span>
            <span class="links">
                <a data-pv-styles target="_blank">wszystkie style</a>
                <a data-pv-page target="_blank">otwórz osobno</a>
            </span>
        </div>
        <div class="pv-strip" data-pv-strip></div>
        <button class="pv-close" type="button" data-pv-close>Zamknij (Esc)</button>
    </dialog>


    @if($undated->isNotEmpty())
        <div class="day-panel">
            <h2>Bez terminu</h2>
            <div class="hint">
                Zaakceptowane, ale bez <code>publish_at</code> – nie mają dnia, więc nie ma ich w siatce.
            </div>
            @foreach($undated as $entry)
                <div class="row">
                    <span class="tag" style="background: {{ $entry->color() }}">{{ $entry->label() }}</span>
                    <button class="slug" data-preview
                            data-slug="{{ $entry->post()->slug }}"
                            data-slides="{{ $entry->post()->slideCount() }}"
                            data-w="{{ $entry->post()->type->width() }}"
                            data-h="{{ $entry->post()->type->height() }}"
                            data-label="{{ $entry->label() }}"
                            data-color="{{ $entry->color() }}"
                            data-time=""
                            data-type="{{ $entry->post()->type->label() }}">{{ $entry->post()->slug }}</button>
                    <span class="type">{{ $entry->post()->type->label() }}</span>
                    <span class="links">
                        <a href="{{ route('social.styles', ['slug' => $entry->post()->slug]) }}" target="_blank">style</a>
                    </span>
                </div>
            @endforeach
        </div>
    @endif

    <script>
        (function () {
            const modal = document.querySelector('[data-preview-modal]');
            const strip = document.querySelector('[data-pv-strip]');
            const tag = document.querySelector('[data-pv-tag]');
            const slugEl = document.querySelector('[data-pv-slug]');
            const meta = document.querySelector('[data-pv-meta]');
            const stylesLink = document.querySelector('[data-pv-styles]');
            const pageLink = document.querySelector('[data-pv-page]');

            // Szablony URL-i z Blade'a – JS nie zna tras Laravela, a sklejanie ścieżek
            // z palca rozjechałoby się przy pierwszej zmianie w routes/web.php.
            const SLIDE_TPL = @json(route('social.slide', ['slug' => '__SLUG__', 'index' => '__INDEX__']));
            const PREVIEW_TPL = @json(route('social.preview', ['slug' => '__SLUG__']));
            const STYLES_TPL = @json(route('social.styles', ['slug' => '__SLUG__']));

            const MAX_H = 460;

            function open(el) {
                const { slug, slides, w, h, label, color, time, type } = el.dataset;
                const count = parseInt(slides, 10) || 1;
                const scale = Math.min(MAX_H / parseInt(h, 10), 0.34);

                tag.textContent = label;
                tag.style.background = color;
                slugEl.textContent = slug;
                meta.textContent = [time, type, count + ' slajd(ów)', w + 'x' + h].filter(Boolean).join(' · ');
                stylesLink.href = STYLES_TPL.replace('__SLUG__', slug);
                pageLink.href = PREVIEW_TPL.replace('__SLUG__', slug);

                strip.innerHTML = '';
                for (let i = 1; i <= count; i++) {
                    const item = document.createElement('div');
                    item.className = 'pv-slide';
                    item.innerHTML =
                        '<div class="no">' + String(i).padStart(2, '0') + '/' + String(count).padStart(2, '0') + '</div>' +
                        '<div class="box" style="width:' + Math.round(w * scale) + 'px;height:' + Math.round(h * scale) + 'px">' +
                        '<iframe src="' + SLIDE_TPL.replace('__SLUG__', slug).replace('__INDEX__', i) + '" ' +
                        'style="width:' + w + 'px;height:' + h + 'px;transform:scale(' + scale + ')" title="' + i + '"></iframe>' +
                        '</div>';
                    strip.appendChild(item);
                }

                modal.showModal();
            }

            document.querySelectorAll('[data-preview]').forEach((el) => {
                el.addEventListener('click', (e) => {
                    // Ctrl/cmd/środkowy przycisk zostawiamy przeglądarce – kto chce
                    // podgląd w nowej karcie, nadal go ma (pozycje w siatce to <a href>).
                    if (e.metaKey || e.ctrlKey || e.shiftKey || e.button !== 0) return;
                    e.preventDefault();
                    open(el);
                });
            });

            document.querySelector('[data-pv-close]')?.addEventListener('click', () => modal.close());

            // Kliknięcie w tło zamyka. <dialog> raportuje klik w backdrop jako klik
            // w samego siebie, więc porównujemy target.
            modal.addEventListener('click', (e) => {
                if (e.target === modal) modal.close();
            });

            // Iframe'y zostawione w DOM-ie trzymałyby dokumenty w pamięci.
            modal.addEventListener('close', () => { strip.innerHTML = ''; });
        })();
    </script>

</body>
</html>
