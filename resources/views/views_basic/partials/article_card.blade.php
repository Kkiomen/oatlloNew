{{-- Reużywalny kafel artykułu. Wymaga zmiennej $card (model Article). --}}
<article class="card-hover group flex flex-col overflow-hidden rounded-2xl border border-white/10 bg-neutral-900">
    <a href="{{ $card->getRoute() }}" class="relative block overflow-hidden" aria-label="{{ $card->name }}">
        <div class="aspect-[16/9] w-full overflow-hidden bg-neutral-800">
            <img src="{{ $card->image }}" alt="{{ $card->name }}" class="h-full w-full object-cover transition-transform duration-500 group-hover:scale-105" loading="lazy">
        </div>
        @if($card->getCategoryName())
            <span class="absolute left-3 top-3 rounded-full bg-neutral-950/80 px-3 py-1 text-xs font-medium text-rose-300 backdrop-blur">{{ $card->getCategoryName() }}</span>
        @endif
    </a>
    <div class="flex flex-1 flex-col p-5">
        <div class="mb-2 flex items-center gap-2 text-xs text-neutral-500">
            <time datetime="{{ $card->getPublishedDate()->format('Y-m-d') }}">{{ $card->getPublishedDate()->format('M j, Y') }}</time>
            <span>·</span>
            <span>{{ $card->getTimeRead() }} min</span>
        </div>
        <h3 class="font-bold text-white transition-colors duration-200 group-hover:text-rose-300 line-clamp-2">
            <a href="{{ $card->getRoute() }}">{{ $card->name }}</a>
        </h3>
        <p class="mt-2 text-sm text-neutral-400 line-clamp-2">{{ $card->short_description }}</p>
    </div>
</article>
