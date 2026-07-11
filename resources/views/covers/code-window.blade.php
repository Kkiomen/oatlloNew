@php
    $mono = "'JetBrains Mono','Fira Code',ui-monospace,SFMono-Regular,Menlo,Consolas,'Liberation Mono',monospace";
    $sans = "'Montserrat',ui-sans-serif,system-ui,-apple-system,'Segoe UI',Roboto,sans-serif";
    $labelX = $width - 90 - $labelWidth;
@endphp
<svg xmlns="http://www.w3.org/2000/svg" width="{{ $width }}" height="{{ $height }}" viewBox="0 0 {{ $width }} {{ $height }}" role="img" aria-label="{{ $title }}">
    <defs>
        <linearGradient id="bg" x1="0" y1="0" x2="1" y2="1">
            <stop offset="0" stop-color="#0b1120"/>
            <stop offset="1" stop-color="#111827"/>
        </linearGradient>
    </defs>

    <rect width="{{ $width }}" height="{{ $height }}" fill="url(#bg)"/>

    {{-- Poświata w kolorze akcentu --}}
    <circle cx="{{ $width - 140 }}" cy="120" r="300" fill="{{ $accent }}" opacity="0.10"/>
    <circle cx="140" cy="{{ $height - 40 }}" r="220" fill="{{ $accent }}" opacity="0.06"/>

    {{-- Etykieta kategorii / technologii (pigułka) --}}
    <g>
        <rect x="{{ $labelX }}" y="54" width="{{ $labelWidth }}" height="44" rx="22" fill="{{ $accent }}" opacity="0.16"/>
        <text x="{{ $labelX + $labelWidth / 2 }}" y="82" font-family="{{ $sans }}" font-size="22" font-weight="600" fill="{{ $accent }}" text-anchor="middle">{{ $label }}</text>
    </g>

    {{-- Okno edytora --}}
    <rect x="90" y="118" width="1020" height="400" rx="18" fill="#0f172a" stroke="#1f2937" stroke-width="1.5"/>
    {{-- Pasek tytułu okna --}}
    <path d="M90 136 a18 18 0 0 1 18 -18 h984 a18 18 0 0 1 18 18 v36 h-1020 z" fill="#111827"/>
    <circle cx="126" cy="145" r="8" fill="#ef4444"/>
    <circle cx="154" cy="145" r="8" fill="#f59e0b"/>
    <circle cx="182" cy="145" r="8" fill="#22c55e"/>
    <text x="232" y="152" font-family="{{ $mono }}" font-size="20" fill="#94a3b8">{{ $filename }}</text>
    <rect x="232" y="164" width="{{ min(240, max(90, strlen($filename) * 11)) }}" height="3" fill="{{ $accent }}"/>

    {{-- "Kod": nagłówek + tytuł jako komentarz + podpis --}}
    <text x="{{ $textX }}" y="230" font-family="{{ $mono }}" font-size="22" fill="#64748b">{{ $header }}</text>

    @foreach($titleLines as $line)
        <text x="{{ $textX }}" y="{{ $line['y'] }}" font-family="{{ $mono }}" font-size="{{ $fontSize }}" font-weight="700" fill="#e2e8f0"><tspan fill="{{ $accent }}">{{ $comment }} </tspan>{{ $line['text'] }}</text>
    @endforeach

    <text x="{{ $textX }}" y="492" font-family="{{ $mono }}" font-size="24" fill="#475569">{{ $footer }}</text>

    {{-- Marka --}}
    <text x="90" y="590" font-family="{{ $sans }}" font-size="30" font-weight="700" fill="#e2e8f0">oatllo<tspan fill="{{ $accent }}">.com</tspan></text>
</svg>
