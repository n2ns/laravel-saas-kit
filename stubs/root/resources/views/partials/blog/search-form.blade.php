@php
    $searchQuery = $searchQuery ?? request()->string('q')->toString();
    $preservedQuery = $preservedQuery ?? collect(request()->query())
        ->only(['sort', 'type', 'topic'])
        ->filter(fn ($value): bool => filled($value))
        ->all();
@endphp

<form action="{{ localized_route('blog.index') }}" method="GET" class="w-full sm:w-[24rem]">
    @foreach($preservedQuery as $name => $value)
        @if(is_scalar($value))
            <input type="hidden" name="{{ $name }}" value="{{ $value }}">
        @endif
    @endforeach
    <label for="blog-search-{{ $id ?? 'default' }}" class="sr-only">{{ __('messages.blog.search_label') }}</label>
    <div class="relative flex items-center">
        <i data-lucide="search" class="pointer-events-none absolute left-4 h-4 w-4 text-slate-400"></i>
        <input id="blog-search-{{ $id ?? 'default' }}"
               type="search"
               name="q"
               value="{{ $searchQuery }}"
               maxlength="120"
               placeholder="{{ __('messages.blog.search_placeholder') }}"
               class="h-11 w-full rounded-full border border-black/[0.08] bg-white pl-11 pr-24 text-sm text-slate-900 shadow-sm outline-none transition focus:border-primary-400 focus:ring-2 focus:ring-primary-400/20">
        @if(filled($searchQuery))
            <a href="{{ localized_route('blog.index', $preservedQuery) }}"
               aria-label="{{ __('messages.blog.search_clear') }}"
               class="absolute right-14 inline-flex h-8 w-8 items-center justify-center rounded-full text-slate-400 transition hover:bg-slate-100 hover:text-slate-700">
                <i data-lucide="x" class="h-4 w-4"></i>
            </a>
        @endif
        <button type="submit"
                aria-label="{{ __('messages.blog.search_submit') }}"
                class="absolute right-1.5 inline-flex h-8 w-10 items-center justify-center rounded-full bg-primary-500 text-slate-950 transition hover:bg-primary-400 focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-400">
            <i data-lucide="arrow-right" class="h-4 w-4"></i>
        </button>
    </div>
</form>
