<?php

/*
|--------------------------------------------------------------------------
| Motywy okładek KURSÓW (SVG generowany dynamicznie)
|--------------------------------------------------------------------------
|
| Analogicznie do config/covers.php (okładki artykułów), ale dla kursów okładka
| jest "lekko inna": zamiast okna kodu ma DUŻE LOGO technologii + pigułkę
| "Free course" + kropki rozdziałów. Motyw jest dobierany do tematu kursu na
| podstawie nazwy, slug, symbolu i opisu (pierwszy pasujący keyword wygrywa).
| Jeśli nic nie pasuje -> motyw 'default' (akcent emerald, spójny z motywem kursów).
|
| Każdy motyw:
|  - accent   : kolor akcentu (poświata, pigułka, podkreślenie, kropki),
|  - label    : etykieta technologii w pigułce (np. "Docker"),
|  - logo     : wewnętrzny markup SVG logo na kanwie 0 0 100 100. Używaj
|               fill="currentColor" / stroke="currentColor" – kolor (biały)
|               ustawia widok covers/course-cover.blade.php. Dzięki temu logo
|               jest spójne wizualnie, a identyfikuje je akcent + etykieta.
|
| Jak DODAĆ nową technologię: dopisz wpis w 'themes' (keywords + accent + label +
| logo). Kolejność ma znaczenie – bardziej szczegółowe motywy przed ogólnymi
| (docker przed linux/devops, laravel przed php, kubernetes przed docker).
|
| Nie zależy od żadnych rozszerzeń graficznych PHP – to czysty SVG, działa
| identycznie lokalnie i na produkcji.
*/

return [

    'default' => [
        'accent'       => '#34d399', // emerald-400 – akcent kursów (hex dla okładki SVG)
        'accent_color' => 'emerald', // nazwa palety Tailwind – akcent CAŁEJ strony kursu
        'label'        => 'Free course',
        // Czapka absolwenta – uniwersalny symbol kursu.
        'logo'   => '<g fill="currentColor">'
            . '<path d="M50 18 L94 37 L50 56 L6 37 Z"/>'
            . '<path d="M22 47 v17 c0 8 13 14 28 14 s28 -6 28 -14 v-17 l-28 12 z"/>'
            . '<path d="M92 37 v24" stroke="currentColor" stroke-width="4" stroke-linecap="round"/>'
            . '<circle cx="92" cy="66" r="5"/>'
            . '</g>',
    ],

    'themes' => [

        // MUSI być przed 'docker': Kubernetes orkiestruje kontenery, więc jego
        // nazwa i opis prawie zawsze zawierają słowo "container" (keyword Dockera).
        // Przy odwrotnej kolejności kurs o Kubernetesie dostawał wieloryba Dockera.
        'kubernetes' => [
            'keywords' => ['kubernetes', 'k8s', 'helm', 'kubectl', 'orchestration', 'orkiestracja'],
            'accent'       => '#326ce5',
            'accent_color' => 'blue',
            'label'        => 'Kubernetes',
            // Ster (helm) – siedmioramienne koło.
            'logo'     => '<g fill="none" stroke="currentColor" stroke-width="5" stroke-linejoin="round">'
                . '<path d="M50 6 L80 20 L88 52 L68 78 L32 78 L12 52 L20 20 Z"/>'
                . '<circle cx="50" cy="48" r="12"/>'
                . '<g stroke-width="4">'
                . '<path d="M50 16 V34"/><path d="M25 30 L40 44"/><path d="M75 30 L60 44"/>'
                . '<path d="M33 72 L44 57"/><path d="M67 72 L56 57"/>'
                . '</g></g>',
        ],

        'docker' => [
            // NIE dopisuj tu gołego 'compose' – dopasowanie jest podciągowe, więc
            // 'compose' trafia w 'composer', czyli w narzędzie PHP. Efekt: każdy
            // tekst o Composerze dostawał wieloryba Dockera. 'docker compose' jest
            // jednoznaczne, a samo 'docker' i tak łapie resztę przypadków.
            'keywords' => ['docker', 'dockerfile', 'docker compose', 'container', 'kontener', 'konteneryzacja'],
            'accent'       => '#2496ed',
            'accent_color' => 'sky', // niebieski Dockera
            'label'        => 'Docker',
            // Wieloryb Moby ze stertą kontenerów.
            'logo'     => '<g fill="currentColor">'
                . '<rect x="30" y="30" width="12" height="11" rx="1"/>'
                . '<rect x="44" y="30" width="12" height="11" rx="1"/>'
                . '<rect x="58" y="30" width="12" height="11" rx="1"/>'
                . '<rect x="44" y="17" width="12" height="11" rx="1"/>'
                . '<rect x="58" y="17" width="12" height="11" rx="1"/>'
                . '<rect x="58" y="4"  width="12" height="11" rx="1"/>'
                . '<path d="M12 45 h78 c-1 8 -5 14 -13 18 c-6 3 -14 4 -24 4 c-18 0 -31 -7 -36 -16 c-1 -2 -2 -4 -5 -6 z"/>'
                . '<path d="M90 41 c5 -4 12 -3 14 1 c-2 2 -5 2 -8 4 c4 1 7 4 7 8 c-6 2 -12 -2 -14 -7"/>'
                . '</g>',
        ],

        'laravel' => [
            'keywords' => ['laravel', 'eloquent', 'blade', 'artisan', 'livewire'],
            'accent'       => '#ff2d20',
            'accent_color' => 'red',
            'label'        => 'Laravel',
            // Blokowe "L".
            'logo'     => '<g fill="currentColor">'
                . '<path d="M30 14 h13 v50 h30 v13 h-43 z"/>'
                . '</g>',
        ],

        'php' => [
            'keywords' => ['php', 'composer', 'symfony', ' oop', 'php 8'],
            'accent'       => '#777BB4', // PHP "elephant purple" – firmowy fiolet PHP
            'accent_color' => 'violet',
            'label'        => 'PHP',
            // Owal z italic "php" – klasyczny znak PHP (styl spójny z Node).
            'logo'     => '<g fill="none" stroke="currentColor" stroke-width="5">'
                . '<ellipse cx="50" cy="50" rx="45" ry="27"/>'
                . '<text x="50" y="60" text-anchor="middle" font-family="Arial, sans-serif" font-style="italic" font-weight="700" font-size="30" fill="currentColor" stroke="none">php</text>'
                . '</g>',
        ],

        'node' => [
            'keywords' => ['node', 'nodejs', 'express', 'nestjs', 'npm'],
            'accent'       => '#5fa04e',
            'accent_color' => 'green',
            'label'        => 'Node.js',
            'logo'     => '<g fill="none" stroke="currentColor" stroke-width="5" stroke-linejoin="round">'
                . '<path d="M50 6 L86 27 V73 L50 94 L14 73 V27 Z"/>'
                . '<text x="50" y="59" text-anchor="middle" font-family="Arial, sans-serif" font-weight="700" font-size="22" fill="currentColor" stroke="none">node</text>'
                . '</g>',
        ],

        'javascript' => [
            'keywords' => ['javascript', ' js', 'typescript', ' ts', 'react', 'vue', 'frontend', 'front-end'],
            'accent'       => '#f7df1e',
            'accent_color' => 'amber',
            'label'        => 'JavaScript',
            'logo'     => '<g fill="none" stroke="currentColor" stroke-width="5" stroke-linejoin="round">'
                . '<rect x="6" y="6" width="88" height="88" rx="12"/>'
                . '<text x="86" y="82" text-anchor="end" font-family="Arial, sans-serif" font-weight="700" font-size="46" fill="currentColor" stroke="none">JS</text>'
                . '</g>',
        ],

        'python' => [
            'keywords' => ['python', 'django', 'flask', 'pandas', 'fastapi'],
            'accent'       => '#3776ab',
            'accent_color' => 'blue',
            'label'        => 'Python',
            'logo'     => '<text x="50" y="68" text-anchor="middle" font-family="Arial, sans-serif" font-weight="700" font-size="52" fill="currentColor">Py</text>',
        ],

        // MUSI stac przed 'database' – Redis to tez baza, ale ma wlasna marke
        // (firmowa czerwien + warstwowy "stack" zamiast generycznego walca).
        'redis' => [
            'keywords' => ['redis', 'cache', 'caching', 'in-memory', 'key-value', 'key value'],
            'accent'       => '#DC382D', // firmowa czerwien Redis
            'accent_color' => 'red',
            'label'        => 'Redis',
            // Warstwowy "stack" – skojarzenie z magazynem in-memory.
            'logo'     => '<g fill="none" stroke="currentColor" stroke-width="5" stroke-linejoin="round">'
                . '<path d="M50 14 L84 30 L50 46 L16 30 Z"/>'
                . '<path d="M16 44 L50 60 L84 44"/>'
                . '<path d="M16 58 L50 74 L84 58"/>'
                . '<path d="M16 30 V58"/><path d="M84 30 V58"/>'
                . '</g>',
        ],

        'rabbitmq' => [
            'keywords' => ['rabbitmq', 'amqp', 'message broker', 'message queue', 'messaging', 'brokers'],
            'accent'       => '#FF6600', // firmowy pomarancz RabbitMQ
            'accent_color' => 'orange',
            'label'        => 'RabbitMQ',
            // Krolik - znak rozpoznawczy RabbitMQ (dwa ucha + glowa).
            'logo'     => '<g fill="none" stroke="currentColor" stroke-width="5" stroke-linejoin="round" stroke-linecap="round">'
                . '<path d="M40 46 C34 30 33 16 40 14 C47 16 46 32 44 46"/>'
                . '<path d="M60 46 C66 30 67 16 60 14 C53 16 54 32 56 46"/>'
                . '<circle cx="50" cy="64" r="24"/>'
                . '<circle cx="42" cy="60" r="2.5" fill="currentColor"/>'
                . '<circle cx="58" cy="60" r="2.5" fill="currentColor"/>'
                . '</g>',
        ],

        'database' => [
            'keywords' => ['sql', 'mysql', 'postgres', 'postgresql', 'database', 'baza danych', 'sqlite', 'mariadb'],
            'accent'       => '#00b4d8',
            'accent_color' => 'cyan',
            'label'        => 'Database',
            // Klasyczny walec bazy danych.
            'logo'     => '<g fill="none" stroke="currentColor" stroke-width="5" stroke-linejoin="round">'
                . '<ellipse cx="50" cy="24" rx="30" ry="12"/>'
                . '<path d="M20 24 V76 a30 12 0 0 0 60 0 V24"/>'
                . '<path d="M20 50 a30 12 0 0 0 60 0"/>'
                . '</g>',
        ],

        'git' => [
            'keywords' => ['git', 'github', 'gitlab', 'version control', 'kontrola wersji'],
            'accent'       => '#f05032',
            'accent_color' => 'orange',
            'label'        => 'Git',
            // Graf gałęzi.
            'logo'     => '<g stroke="currentColor" stroke-width="6" fill="currentColor" stroke-linecap="round">'
                . '<circle cx="28" cy="76" r="8"/>'
                . '<circle cx="28" cy="30" r="8"/>'
                . '<circle cx="70" cy="46" r="8"/>'
                . '<path d="M28 68 V38" fill="none"/>'
                . '<path d="M28 40 q0 8 34 8" fill="none" stroke-width="6"/>'
                . '</g>',
        ],

        // MUSI stać przed 'devops': tam 'nginx' jest jednym ze słów kluczowych, a
        // wygrywa PIERWSZY trafiony motyw – po zamianie kolejności nginx wróciłby
        // do generycznego okna terminala. Własny wpis daje mu markę: firmową zieleń
        // i heksagon z "N" zamiast ogólnego "DevOps".
        'nginx' => [
            'keywords' => ['nginx', 'reverse proxy', 'load balancer', 'load balancing'],
            'accent'       => '#009639', // firmowa zieleń nginx
            'accent_color' => 'green',
            'label'        => 'nginx',
            // Heksagon z "N" – znak nginxa.
            'logo'     => '<g fill="none" stroke="currentColor" stroke-width="5" stroke-linejoin="round" stroke-linecap="round">'
                . '<path d="M50 8 L86 29 v42 L50 92 L14 71 V29 Z"/>'
                . '<path d="M38 66 V38 l24 24 V34" stroke-width="6"/>'
                . '</g>',
        ],

        'devops' => [
            // 'nginx' zostaje tu CELOWO jako siatka bezpieczeństwa dla starych
            // treści – motyw 'nginx' wyżej i tak go przechwyci wcześniej.
            'keywords' => ['devops', 'linux', 'bash', 'shell', 'cli', 'terminal', 'nginx', 'server', 'serwer', 'ci/cd', 'deploy'],
            'accent'       => '#22c55e',
            'accent_color' => 'green',
            'label'        => 'DevOps',
            // Okno terminala z ">_".
            'logo'     => '<g fill="none" stroke="currentColor" stroke-width="5" stroke-linejoin="round" stroke-linecap="round">'
                . '<rect x="10" y="16" width="80" height="68" rx="9"/>'
                . '<path d="M26 44 l14 10 l-14 10"/>'
                . '<path d="M48 66 h20"/>'
                . '</g>',
        ],

        'ai' => [
            'keywords' => ['ai', 'llm', 'gpt', 'openai', 'claude', 'machine learning', 'sztuczna inteligencja', 'agent', 'prompt'],
            'accent'       => '#10b981',
            'accent_color' => 'emerald',
            'label'        => 'AI',
            // Iskra – współczesny glif "AI".
            'logo'     => '<path fill="currentColor" d="M50 8 C55 34 66 45 92 50 C66 55 55 66 50 92 C45 66 34 55 8 50 C34 45 45 34 50 8 Z"/>',
        ],

    ],

];
