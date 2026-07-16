# Redis course — plan & resume doc

Roboczy plan kursu **Redis** dla Oatllo + status, żeby wznowić po crashu/wyczerpaniu tokenów.
Format kursu: pliki `.md` w `resources/courses/{slug}/` (patrz skill `course-writer`), render z dysku,
zero bazy. Workflow: napisz pliki -> commit -> deploy = `git pull`.

## Status (aktualizuj w trakcie)

- [x] Motyw okładki `redis` dodany do `config/course-covers.php` (czerwień `#DC382D`, `accent_color: red`
      — `red` już w safelist, WIĘC BEZ rebuildu CSS; logo = warstwowy „stack"). Wpis stoi PRZED `database`.
- [x] `resources/courses/redis-basics/course.md`
- [x] Dział 01 — Getting started (5 lekcji)
- [x] Dział 02 — Keys, values and expiration (5)
- [x] Dział 03 — Core data types (5)
- [x] Dział 04 — Managing Redis from the console (5)
- [x] Dział 05 — Redis + Laravel (6)
- [x] Dział 06 — Caching patterns and invalidation (7)
- [x] Dział 07 — Beyond cache & production (8)
- [x] Sanity-check: 41 lekcji, 0 em/en dashes, 0 strzałek, wszystkie mają `## FAQ`, frontmatter OK,
      77 linków wewn. / 0 broken, okładka = `[Redis]` `#DC382D`. TREŚĆ KOMPLETNA.
- [x] Pass SEO + anti-AI (lesson-seo + blog-anti-ai) na WSZYSTKICH 41 lekcjach. Po passie: 82 linki wewn./0 broken.
- [ ] Commit + push na `main` (deploy = git pull); po deployu `php artisan indexnow:submit-sitemap --regenerate`

## Metadane

- Slug kursu: `redis-basics` · katalog `resources/courses/redis-basics/`
- `image: auto` -> motyw `redis` (czerwień). Akcent strony: `red`.
- Audytorium: developerzy PHP/Laravel, zero wiedzy o Redisie. Profil Oatllo -> mocny wątek Laravel + Docker.
- 4 lekcje WYMAGANE przez użytkownika (oznaczone ⭐).

### UWAGA — dobór motywu okładki (nauczka z nginx)

Motyw dobiera się z „haystacka" = `name + slug + title_list + description_list + title_seo`
(`CourseCoverImageService::buildHaystack`), pierwszy trafiony motyw wygrywa. `laravel`/`docker`/`php`
stoją w configu przed `redis`. **W `course.md` te pola MUSZĄ być czyste z `laravel`/`docker`/`php`**,
inaczej okładka złapie obcy motyw. Laravel/Docker/PHP wolno wspomnieć tylko w `description_full`,
`description_seo` i treści lekcji (NIE wchodzą do haystacka). Weryfikacja: `php artisan course:cover redis-basics`
-> ma zwrócić `[Redis]` i `#DC382D`.

## Pełny konspekt (7 działów, ~41 lekcji)

### 01 — Getting started (`01-getting-started`)
- `01-what-is-redis.md` — in-memory key-value store, czemu szybki
- `02-what-redis-is-used-for.md` — cache, sesje, kolejki, rate limiting, pub/sub
- `03-redis-vs-a-database.md` — RAM vs dysk, uzupełnienie a nie zamiennik
- ⭐ `04-run-redis-with-docker.md` — Redis na Dockerze (`docker run`/`compose`) + wejście `docker exec -it redis redis-cli`
- `05-first-commands.md` — `PING`, `SET`/`GET`, `DEL`

### 02 — Keys, values and expiration (`02-keys-values-and-expiration`)
- `01-keys-and-values.md` — model klucz->wartość, SET/GET, nadpisywanie
- `02-key-naming-conventions.md` — namespacing przez `:` (`user:42:profile`)
- `03-expiration-and-ttl.md` — `EXPIRE`, `TTL`, `SETEX`, `PERSIST` (serce cache)
- `04-deleting-and-checking-keys.md` — `DEL`, `EXISTS`, `SCAN` zamiast `KEYS`
- `05-atomic-counters.md` — `INCR`/`DECR` (podkładka pod rate limiting)

### 03 — Core data types (`03-core-data-types`)
- `01-strings.md` · `02-hashes.md` · `03-lists.md` · `04-sets.md` · `05-sorted-sets.md`

### 04 — Managing Redis from the console (`04-managing-redis-from-the-console`)
Dział o `redis-cli` — codzienna obsługa i diagnostyka. Umieszczony PO typach danych,
żeby „odczyt wg typu" nie wyprzedzał materiału.
- `01-the-redis-cli-console.md` — połączenie: lokalnie, na Dockerze (`docker exec -it`), zdalnie (`-h/-p/-a`),
  tryb interaktywny vs one-shot (`redis-cli GET foo`), `SELECT`, `DBSIZE`
- `02-finding-keys-scan-vs-keys.md` — `KEYS wzorzec` (czemu groźne na prodzie) vs `SCAN` z `MATCH/COUNT`,
  glob (`user:*`), `redis-cli --scan --pattern`
- `03-inspecting-keys-and-values.md` — `EXISTS`, `TYPE`, `TTL`, `OBJECT ENCODING` + odczyt wg typu
  (`GET`, `HGETALL`, `LRANGE`, `SMEMBERS`, `ZRANGE`)
- `04-deleting-keys-and-flushing-cache.md` — `DEL`/`UNLINK`, kasowanie po wzorcu
  (`--scan --pattern ... | xargs redis-cli DEL`), `FLUSHDB` vs `FLUSHALL` (cały cache), `ASYNC`, pułapka złej bazy
- `05-server-info-and-monitoring.md` — `INFO` (pamięć/keyspace/stats), `MONITOR`, `SLOWLOG`,
  `--stat`, `--bigkeys`, `--latency`

### 05 — Redis + Laravel (`05-redis-and-laravel`)
- ⭐ `01-connecting-redis-to-laravel.md` — `config/database.php`, `.env`, phpredis vs predis
- `02-using-the-redis-facade.md` — `Redis::set/get`, surowe komendy z Laravela
- `03-redis-as-cache-driver.md` — `CACHE_STORE=redis`, różnica wobec `file`
- ⭐ `04-caching-in-laravel.md` — `Cache::remember`, `put`, `get`, `forget`, `remember` vs `rememberForever`
- `05-cache-tags.md` — `Cache::tags(...)->flush()`
- `06-sessions-and-queues-on-redis.md` — `SESSION_DRIVER`, `QUEUE_CONNECTION=redis` (skrótowo)

### 06 — Caching patterns and invalidation (`06-caching-patterns-and-invalidation`)
- `01-why-we-cache.md` — po co cache, koszt (świeżość vs szybkość)
- `02-the-cache-aside-pattern.md` — read-through / lazy loading (`Cache::remember` to on)
- `03-ttl-vs-explicit-invalidation.md` — wygasanie czasem vs ręczne unieważnianie
- ⭐ `04-why-cache-invalidation-is-hard.md` — czemu inwalidacja to jedna z najważniejszych i najtrudniejszych
  rzeczy w programowaniu (Phil Karlton: „two hard things... cache invalidation and naming things"),
  stale data, korektność vs świeżość, problem rozproszony
- `05-invalidation-strategies.md` — delete-on-write, write-through, klucze wersjonowane, tagi
- `06-cache-stampede.md` — thundering herd, `Cache::lock` / atomiczne odświeżanie
- `07-common-caching-mistakes.md` — cache bez TTL, dane per-user pod globalnym kluczem, cache'owanie wszystkiego

### 07 — Beyond cache & production (`07-beyond-cache-and-production`)
- `01-rate-limiting-with-redis.md` — licznik + TTL (bazuje na `INCR`/`EXPIRE`)
- `02-queues-and-background-jobs.md` — Redis jako kolejka zadań Laravela
- `03-pub-sub.md` — publish/subscribe w skrócie
- `04-persistence-rdb-aof.md` — RDB vs AOF (przeżycie restartu)
- `05-eviction-policies.md` — `maxmemory` + `maxmemory-policy` (LRU/LFU) — kluczowe, gdy Redis to cache
- `06-securing-redis.md` — `requirepass`, `bind`, „nigdy nie wystawiaj Redisa do internetu"
- `07-a-laravel-redis-docker-stack.md` — capstone: pełny `docker-compose` Laravel + Redis + kolejka
- `08-troubleshooting.md` — connection refused, OOM, wolne komendy

## Kolejność / zależności (never get ahead)

Docker (01/04) na starcie, żeby dało się ćwiczyć. TTL (02/03) i typy (03) PRZED konsolą (04) i cache w Laravel (05).
Dział konsoli (04) po typach, bo odczyt wg typu wymaga znajomości typów. Marquee „czemu inwalidacja trudna"
(06/04) po tym, jak czytelnik już cache'uje i zna TTL — inaczej abstrakcyjne. Rate limiting (07/01) po `INCR`+`EXPIRE`.

## Jak dokończyć (przepis)

1. Utwórz `course.md` (haystack czysty — patrz uwaga wyżej; Laravel/Docker tylko w `description_full`/`description_seo`/About).
2. Rozdaj 7 agentów (po jednym na dział, subagent `general-purpose`, równolegle) — użyj SZABLONU PROMPTU z kursu nginx:
   podaj każdemu: base dir, pełny konspekt (do ordering), listę plików działu, format frontmattera `_chapter.md`
   i lekcji (`title/slug/seo_title/seo_description`), styl Laravel-docs (problem->pokaż->wyjaśnij, plain hyphens,
   NIGDY em/en dashes, brak strzałek, sekcje od `##`, „common mistake", `## FAQ`), zakaz wyprzedzania materiału,
   crosslink tylko wstecz w formie `/course/redis-basics/{chapter-slug}/{lesson-slug}`.
3. Sanity-check bash: liczba plików, `grep` na em/en dashes i strzałki `→ ←`, `## FAQ` w każdej lekcji,
   frontmatter (linia 1 = `---`), broken linki (porównaj użyte `/course/redis-basics/.../...` ze zbiorem realnych slugów).
4. Pass SEO + anti-AI: 7 agentów (po jednym na dział), każdy per plik robi PASS 1 (lesson-seo: primary keyword,
   `seo_title`/`seo_description`, wyszukiwalne nagłówki, FAQ) + PASS 2 (blog-anti-ai: rytm zdań, brak wypełniacza,
   1-2 PRAWDZIWE notki praktyka, żadnych zmyślonych liczb). Twarde zakazy jak w pkt 2. Nie ruszać slugów.
5. Ponowny sanity-check. Commit tylko plików tej roboty:
   `git add resources/courses/redis-basics config/course-covers.php` (CSS/tailwind NIE — `red` już w safelist).
6. `git push` (uwaga: w headless push może paść na brak kredek — użytkownik robi `! git push`).
   Po deployu: `php artisan indexnow:submit-sitemap --regenerate`.

## Kontekst SEO (czemu bez obaw dosypujemy kurs)

Kursy dodajemy w CAŁOŚCI (spójna hierarchia, Google oczekuje kompletu) — to inne niż artykuły (tam limit ~3/tydz).
Dowód: kurs PHP (86 podstron naraz) = 88% ruchu domeny. Redis to kolejny klaster (Docker+nginx+PHP+Redis).
Google nie karze za tempo/wolumen, tylko za jakość.
