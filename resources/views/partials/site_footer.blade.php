@php
    // Wspólna stopka z hubami (kategorie + tagi) do linkowania wewnętrznego na
    // każdej podstronie. Dane pobierane raz i cache'owane, więc nie obciążają
    // renderu. $accent pozwala dopasować kolor (domyślnie rose; emerald w kursach).
    $accent = $accent ?? 'rose';

    // Stopka jest dekoracyjna — ewentualny błąd bazy/cache nie może wywalić
    // renderu całej strony (500). W razie problemu pokazujemy pustą stopkę-hub.
    try {
        $footerHubs = \Illuminate\Support\Facades\Cache::remember(
            'footer_hubs:' . env('APP_LOCALE', 'en'),
            3600,
            function () {
                $catIds = \App\Models\Article::whereNotNull('category_id')
                    ->where('is_published', true)->distinct()->pluck('category_id');
                $categories = \App\Models\Category::whereIn('id', $catIds)
                    ->orderBy('name')->take(8)->get(['id', 'name', 'slug']);

                $tagIds = \App\Models\TagArticle::query()->distinct()->pluck('tag_id');
                $tags = \App\Models\Tag::whereIn('id', $tagIds)
                    ->where('language', env('APP_LOCALE'))
                    ->orderByDesc('id')->take(14)->get(['name', 'slug']);

                return ['categories' => $categories, 'tags' => $tags];
            }
        );
    } catch (\Throwable $e) {
        report($e);
        $footerHubs = ['categories' => collect(), 'tags' => collect()];
    }
@endphp
<footer class="mt-20 border-t border-white/5 bg-neutral-950">
    <div class="mx-auto max-w-7xl px-6 py-14 lg:px-8">
        <div class="grid grid-cols-2 gap-10 md:grid-cols-4">
            <div class="col-span-2 md:col-span-1">
                <div class="logo_oatllo">oatllo</div>
                <p class="mt-4 text-sm text-neutral-400">{{ __('basic.meta_description') }}</p>
            </div>

            <div>
                <h2 class="text-sm font-semibold text-white">Explore</h2>
                <ul class="mt-4 space-y-2 text-sm text-neutral-400">
                    <li><a href="{{ route('index') }}" class="hover:text-{{ $accent }}-400">{{ __('basic.home') }}</a></li>
                    <li><a href="{{ route('blog') }}" class="hover:text-{{ $accent }}-400">Blog</a></li>
                    <li><a href="{{ \App\Services\HomeService::getRouteCourses() }}" class="hover:text-{{ $accent }}-400">{{ __('basic.courses') }}</a></li>
                    @if(\Illuminate\Support\Facades\Route::has('about.us'))
                        <li><a href="{{ route('about.us') }}" class="hover:text-{{ $accent }}-400">{{ __('basic.about') }}</a></li>
                    @endif
                    @if(\Illuminate\Support\Facades\Route::has('site.map'))
                        <li><a href="{{ route('site.map') }}" class="hover:text-{{ $accent }}-400">{{ __('basic.sitemap') }}</a></li>
                    @endif
                </ul>
            </div>

            @if($footerHubs['categories']->count() > 0)
                <div>
                    <h2 class="text-sm font-semibold text-white">{{ __('basic.categories') }}</h2>
                    <ul class="mt-4 space-y-2 text-sm text-neutral-400">
                        @foreach($footerHubs['categories'] as $cat)
                            <li><a href="{{ route('blog.list.category', ['slug' => $cat->slug]) }}" class="hover:text-{{ $accent }}-400">{{ $cat->name }}</a></li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div>
                <h2 class="text-sm font-semibold text-white">Connect</h2>
                <ul class="mt-4 space-y-2 text-sm text-neutral-400">
                    <li><a href="https://www.linkedin.com/in/jakub-owsianka-446bb5213/" target="_blank" rel="noopener" class="hover:text-{{ $accent }}-400">LinkedIn</a></li>
                    <li><a href="{{ route('feed') }}" class="hover:text-{{ $accent }}-400">RSS</a></li>
                </ul>
            </div>
        </div>

        @if($footerHubs['tags']->count() > 0)
            <div class="mt-12 border-t border-white/5 pt-8">
                <h2 class="text-sm font-semibold text-white">{{ __('basic.tags') }}</h2>
                <div class="mt-4 flex flex-wrap gap-2">
                    @foreach($footerHubs['tags'] as $tag)
                        <a href="{{ route('blogTag', ['tag' => $tag->slug ?: \Illuminate\Support\Str::slug($tag->name)]) }}"
                           class="rounded-full border border-white/10 bg-white/5 px-3 py-1 text-xs text-neutral-300 hover:border-{{ $accent }}-400/40 hover:text-white transition-colors duration-200">#{{ $tag->name }}</a>
                    @endforeach
                </div>
            </div>
        @endif

        <div class="mt-12 flex flex-col items-center justify-between gap-4 border-t border-white/5 pt-8 sm:flex-row">
            <p class="text-sm text-neutral-500">&copy; {{ date('Y') }} Oatllo · Jakub Owsianka</p>
            <div class="flex gap-5">
                <a href="https://www.linkedin.com/in/jakub-owsianka-446bb5213/" target="_blank" rel="noopener" class="text-neutral-500 hover:text-{{ $accent }}-400" aria-label="LinkedIn">{!! \App\Support\Icons::svg('linkedin', '') !!}</a>
                <a href="{{ route('feed') }}" class="text-neutral-500 hover:text-{{ $accent }}-400" aria-label="RSS">{!! \App\Support\Icons::svg('rss', '') !!}</a>
            </div>
        </div>
    </div>
</footer>
