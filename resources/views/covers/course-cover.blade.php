@php
    $sans = "'Montserrat',ui-sans-serif,system-ui,-apple-system,'Segoe UI',Roboto,sans-serif";
    // Środek panelu z logo po prawej stronie.
    $logoCx = 960;
    $logoCy = 300;
    $labelWidth = max(120, strlen($label) * 13 + 52);
@endphp
<svg xmlns="http://www.w3.org/2000/svg" width="{{ $width }}" height="{{ $height }}" viewBox="0 0 {{ $width }} {{ $height }}" role="img" aria-label="{{ $title }}">
    <defs>
        <linearGradient id="bg" x1="0" y1="0" x2="1" y2="1">
            <stop offset="0" stop-color="#0b1120"/>
            <stop offset="1" stop-color="#0f172a"/>
        </linearGradient>
        <radialGradient id="logoGlow" cx="0.5" cy="0.5" r="0.5">
            <stop offset="0" stop-color="{{ $accent }}" stop-opacity="0.28"/>
            <stop offset="1" stop-color="{{ $accent }}" stop-opacity="0"/>
        </radialGradient>
    </defs>

    <rect width="{{ $width }}" height="{{ $height }}" fill="url(#bg)"/>

    {{-- Poświata w kolorze akcentu --}}
    <circle cx="{{ $width - 160 }}" cy="120" r="300" fill="{{ $accent }}" opacity="0.08"/>
    <circle cx="120" cy="{{ $height - 30 }}" r="220" fill="{{ $accent }}" opacity="0.05"/>

    {{-- Cienki pasek akcentu u góry --}}
    <rect x="0" y="0" width="{{ $width }}" height="6" fill="{{ $accent }}"/>

    {{-- Pigułka "Free course" (technologia) --}}
    <g>
        <rect x="90" y="70" width="{{ $labelWidth }}" height="46" rx="23" fill="{{ $accent }}" opacity="0.16"/>
        <circle cx="120" cy="93" r="6" fill="{{ $accent }}"/>
        <text x="{{ 138 }}" y="100" font-family="{{ $sans }}" font-size="22" font-weight="700" fill="{{ $accent }}">{{ $label }}</text>
    </g>

    {{-- Duże logo technologii (biały mark na poświacie akcentu) --}}
    <circle cx="{{ $logoCx }}" cy="{{ $logoCy }}" r="210" fill="url(#logoGlow)"/>
    <g transform="translate({{ $logoCx - 130 }}, {{ $logoCy - 130 }}) scale(2.6)" fill="#f8fafc" color="#f8fafc" opacity="0.96">
        {!! $logo !!}
    </g>

    {{-- Tytuł kursu --}}
    @foreach($titleLines as $line)
        <text x="{{ $textX }}" y="{{ $line['y'] }}" font-family="{{ $sans }}" font-size="{{ $fontSize }}" font-weight="800" fill="#f1f5f9">{{ $line['text'] }}</text>
    @endforeach

    {{-- Podkreślenie akcentem pod tytułem --}}
    <rect x="{{ $textX }}" y="{{ $underlineY }}" width="88" height="6" rx="3" fill="{{ $accent }}"/>

    {{-- Meta: darmowy kurs + liczba rozdziałów --}}
    <text x="{{ $textX }}" y="{{ $underlineY + 44 }}" font-family="{{ $sans }}" font-size="24" font-weight="600" fill="#94a3b8">{{ $meta }}</text>

    {{-- Kropki rozdziałów (dekoracja odróżniająca kursy) --}}
    <g>
        @for($i = 0; $i < $dots; $i++)
            <circle cx="{{ $textX + 8 + $i * 26 }}" cy="512" r="7" fill="{{ $accent }}" opacity="{{ $i === 0 ? '1' : '0.35' }}"/>
        @endfor
    </g>

    {{-- Marka --}}
    <text x="90" y="590" font-family="{{ $sans }}" font-size="30" font-weight="700" fill="#e2e8f0">oatllo<tspan fill="{{ $accent }}">.com</tspan></text>
</svg>
