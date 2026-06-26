@extends('layouts.app')

@php
    $title = __('messages.nav.blog') ?? 'Blog';
    $subtitle = __('messages.blog.subtitle') ?? 'Product updates, guides, and company notes.';
    $searchQuery = $searchQuery ?? '';
    $sort = $sort ?? 'latest';
    $type = $type ?? null;
    $topic = $topic ?? null;
    $articleCount = $blogPosts->total();
@endphp

@section('title', $title . ' - ' . config('app.name'))
@section('meta_description', $subtitle)
@section('meta_keywords', __('messages.blog.meta_keywords'))

@section('content')
<div class="bg-bg-body min-h-screen overflow-hidden">

    <!-- Page header -->
    <section class="relative border-b border-black/[0.08] overflow-hidden">
        <div class="absolute inset-0 bg-dot-grid opacity-[0.035] pointer-events-none"></div>
        <div class="absolute -top-32 left-[8%] w-[520px] h-[520px] bg-primary-600/8 rounded-full blur-[130px] pointer-events-none"></div>
        <div class="absolute top-20 right-[12%] w-[360px] h-[360px] bg-neon-cyan/8 rounded-full blur-[110px] pointer-events-none"></div>

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10 md:py-12 relative z-10">
            <div class="flex flex-col gap-5 lg:flex-row lg:items-center lg:justify-between">
                <h1 class="text-2xl md:text-4xl font-bold text-slate-950 tracking-tighter leading-tight shrink-0">
                    {{ $title }}
                </h1>
                <div class="flex w-full flex-col gap-3 sm:flex-row sm:items-center lg:w-auto lg:justify-end">
                    @include('partials.blog.search-form', ['searchQuery' => $searchQuery, 'id' => 'index', 'preservedQuery' => $searchPreservedQuery])
                    <span class="inline-flex items-center justify-center gap-2 rounded-full border border-black/[0.08] bg-white px-4 py-2 text-sm text-slate-700 shrink-0">
                        <i data-lucide="book-open" class="w-4 h-4 text-primary-300"></i>
                        {{ trans_choice('messages.blog.article_count', $articleCount, ['count' => $articleCount]) }}
                    </span>
                </div>
            </div>
            @if($searchQuery !== '')
                <p class="mt-5 text-sm text-slate-600">
                    {{ __('messages.blog.search_results_for', ['query' => $searchQuery]) }}
                </p>
            @endif
            <form action="{{ localized_route('blog.index') }}" method="GET" class="mt-6 flex flex-col gap-3 rounded-2xl border border-black/[0.08] bg-white p-4 shadow-sm xl:flex-row xl:items-center">
                @if($searchQuery !== '')
                    <input type="hidden" name="q" value="{{ $searchQuery }}">
                @endif
                <label class="flex min-w-0 flex-col gap-2 text-xs font-semibold text-slate-600 sm:flex-row sm:items-center xl:flex-1">
                    <span class="shrink-0 sm:w-12">{{ __('messages.blog.sort_label') }}</span>
                    <select name="sort" onchange="this.form.submit()" class="h-10 w-full rounded-xl border border-black/[0.08] bg-white px-3 text-sm font-normal text-slate-900 outline-none focus:border-primary-400 focus:ring-2 focus:ring-primary-400/20">
                        @foreach($sortOptions as $value => $label)
                            <option value="{{ $value }}" @selected($sort === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="flex min-w-0 flex-col gap-2 text-xs font-semibold text-slate-600 sm:flex-row sm:items-center xl:flex-1">
                    <span class="shrink-0 sm:w-12">{{ __('messages.blog.type_label') }}</span>
                    <select name="type" onchange="this.form.submit()" class="h-10 w-full rounded-xl border border-black/[0.08] bg-white px-3 text-sm font-normal text-slate-900 outline-none focus:border-primary-400 focus:ring-2 focus:ring-primary-400/20">
                        <option value="">{{ __('messages.blog.all_types') }}</option>
                        @foreach($typeOptions as $value => $label)
                            <option value="{{ $value }}" @selected($type === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="flex min-w-0 flex-col gap-2 text-xs font-semibold text-slate-600 sm:flex-row sm:items-center xl:flex-1">
                    <span class="shrink-0 sm:w-12">{{ __('messages.blog.topic_label') }}</span>
                    <select name="topic" onchange="this.form.submit()" class="h-10 w-full rounded-xl border border-black/[0.08] bg-white px-3 text-sm font-normal text-slate-900 outline-none focus:border-primary-400 focus:ring-2 focus:ring-primary-400/20">
                        <option value="">{{ __('messages.blog.all_topics') }}</option>
                        @foreach($topicOptions as $value => $label)
                            <option value="{{ $value }}" @selected($topic === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </label>
                @if($sort !== 'latest' || $type || $topic)
                    <a href="{{ localized_route('blog.index', filled($searchQuery) ? ['q' => $searchQuery] : []) }}"
                       class="inline-flex h-10 shrink-0 items-center justify-center rounded-xl border border-black/[0.08] px-4 text-sm font-semibold text-slate-600 transition hover:bg-slate-50 hover:text-slate-950">
                        {{ __('messages.blog.reset_filters') }}
                    </a>
                @endif
            </form>
        </div>
    </section>

    <!-- Articles -->
    <section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12 md:py-16">
        @forelse($blogPosts as $blogPost)
            @if($loop->first)
                <div class="mb-8">
            @elseif($loop->iteration === 2)
                <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6 mb-14">
            @endif

            @php
                $hasThumbnail = filled($blogPost->thumbnail);
                $localizedTitle = $blogPost->getTranslation('title', app()->getLocale());
                $localizedExcerpt = $blogPost->getTranslation('excerpt', app()->getLocale());
                $articleUrl = localized_route('blog.show', ['slug' => $blogPost->slug]);
            @endphp

            @if($loop->first)
                <article class="group relative overflow-hidden rounded-[2rem] border border-black/[0.08] bg-gradient-to-br from-white/[0.075] via-white/[0.035] to-primary-500/[0.035] shadow-2xl shadow-orange-900/10">
                    <div class="absolute inset-0 bg-dot-grid opacity-[0.035] pointer-events-none"></div>
                    <div class="absolute -right-20 -top-20 w-72 h-72 bg-primary-500/10 rounded-full blur-3xl pointer-events-none"></div>

                    <a href="{{ $articleUrl }}" class="relative z-10 grid {{ $hasThumbnail ? 'lg:grid-cols-[1fr_0.42fr]' : 'lg:grid-cols-1' }} gap-0">
                        <div class="p-7 md:p-9 lg:p-10 xl:p-12 flex flex-col justify-between">
                            <div>
                                <div class="flex flex-wrap items-center gap-3 mb-7">
                                    <span class="ui-badge-brand">{{ $blogPost->typeLabel() }}</span>
                                    <span class="text-slate-500 text-sm">{{ $blogPost->published_at->format('M d, Y') }}</span>
                                    <span class="hidden sm:inline-block w-1 h-1 rounded-full bg-slate-700"></span>
                                    <span class="text-slate-500 text-sm">{{ __('messages.common.reading_time', ['minutes' => $blogPost->getReadingTime()]) }}</span>
                                </div>

                                <h2 class="text-2xl md:text-4xl xl:text-5xl font-bold text-slate-950 tracking-tighter leading-[1.08] max-w-5xl group-hover:text-primary-300 transition-colors">
                                    {{ $localizedTitle }}
                                </h2>

                                @if($localizedExcerpt)
                                    <p class="mt-6 text-slate-700/85 text-base md:text-lg leading-relaxed max-w-4xl">
                                        {{ $localizedExcerpt }}
                                    </p>
                                @endif
                            </div>

                            <div class="mt-9 pt-7 border-t border-black/[0.08] flex flex-wrap items-center justify-between gap-4">
                                <span class="inline-flex items-center gap-2 text-primary-300 font-semibold">
                                    {{ __('messages.common.read_more') ?? 'Read article' }}
                                    <i data-lucide="arrow-right" class="w-4 h-4 group-hover:translate-x-1 transition-transform"></i>
                                </span>
                                <span class="text-slate-600 text-sm">{{ __('messages.blog.published_by') }}</span>
                            </div>
                        </div>

                        @if($hasThumbnail)
                            <div class="min-h-[260px] lg:min-h-full overflow-hidden border-t lg:border-t-0 lg:border-l border-black/[0.08]">
                                <img src="{{ Storage::disk('public')->url($blogPost->thumbnail) }}"
                                     alt="{{ $localizedTitle }}"
                                     class="w-full h-full object-cover transition-transform duration-700 group-hover:scale-105">
                            </div>
                        @endif
                    </a>
                </article>
            @else
                <article class="group overflow-hidden rounded-3xl border border-black/[0.08] bg-white hover:bg-orange-50 transition-all duration-300 shadow-xl shadow-orange-900/5">
                    <a href="{{ $articleUrl }}" class="flex flex-col h-full">
                        @if($hasThumbnail)
                            <div class="aspect-[16/9] overflow-hidden border-b border-black/[0.08]">
                                <img src="{{ Storage::disk('public')->url($blogPost->thumbnail) }}"
                                     alt="{{ $localizedTitle }}"
                                     class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-105">
                            </div>
                        @endif

                        <div class="p-6 md:p-7 flex flex-col flex-grow">
                            <div class="flex flex-wrap items-center gap-3 mb-5">
                                <span class="ui-badge-brand">{{ $blogPost->typeLabel() }}</span>
                                <span class="text-slate-500 text-xs">{{ $blogPost->published_at->format('M d, Y') }}</span>
                            </div>

                            <h2 class="text-xl font-bold text-slate-950 mb-4 group-hover:text-primary-300 transition-colors leading-snug tracking-tight">
                                {{ $localizedTitle }}
                            </h2>

                            @if($localizedExcerpt)
                                <p class="text-slate-600 text-sm leading-relaxed line-clamp-3 flex-grow">
                                    {{ $localizedExcerpt }}
                                </p>
                            @endif

                            <div class="flex items-center justify-between pt-6 mt-auto">
                                <span class="text-sm font-semibold text-primary-300 inline-flex items-center gap-2">
                                    {{ __('messages.common.read_more') ?? 'Read article' }}
                                    <i data-lucide="arrow-right" class="w-4 h-4 group-hover:translate-x-1 transition-transform"></i>
                                </span>
                                <span class="text-slate-600 text-xs">{{ __('messages.common.reading_time', ['minutes' => $blogPost->getReadingTime()]) }}</span>
                            </div>
                        </div>
                    </a>
                </article>
            @endif

            @if($loop->first || $loop->last)
                </div>
            @endif
        @empty
            <div class="py-24 text-center rounded-[2rem] border border-black/[0.08] bg-white">
                <div class="w-14 h-14 rounded-2xl bg-orange-50 border border-black/[0.08] flex items-center justify-center mx-auto mb-4">
                    <i data-lucide="file-text" class="w-6 h-6 text-slate-500"></i>
                </div>
                <p class="text-slate-600 text-sm">{{ filled($searchQuery) ? __('messages.blog.no_search_results') : __('messages.blog.no_blog_posts') }}</p>
            </div>
        @endforelse

        <!-- Pagination -->
        <div class="flex justify-center mt-12">
            {{ $blogPosts->links() }}
        </div>
    </section>

</div>
@endsection
