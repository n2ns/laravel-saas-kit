@extends('layouts.app')

@php
    $contentLocale = $contentLocale ?? app()->getLocale();
@endphp

@section('title', $blogPost->getTranslation('title', $contentLocale) . ' - SaaS Starter Blog')
@section('meta_description', $blogPost->getTranslation('excerpt', $contentLocale))
@section('og_type', 'article')

@php($blogOgImage = $blogPost->thumbnail ? url(Storage::disk('public')->url($blogPost->thumbnail)) : null)
@if($blogOgImage)
    @section('og_image', $blogOgImage)
    @section('og_image_alt', $blogPost->getTranslation('title', $contentLocale))
@endif

@section('extra_meta')
    @unless(request()->routeIs('admin.*'))
        @push('structured_data')
            @include('partials.structured-data.json-ld', ['data' => \App\Support\StructuredData::blogPosting($blogPost)])
        @endpush
    @endunless
    <style>
        .blog-content pre {
            display: block !important;
            margin: 1.75rem 0 !important;
            padding: 1.25rem !important;
            overflow-x: auto !important;
            border: 1px solid rgba(96, 165, 250, 0.28) !important;
            border-radius: 1rem !important;
            background:
                linear-gradient(180deg, rgba(15, 23, 42, 0.96), rgba(2, 6, 23, 0.96)) !important;
            box-shadow: 0 22px 60px rgba(0, 0, 0, 0.34) !important;
        }

        .blog-content pre code {
            display: block !important;
            padding: 0 !important;
            border-radius: 0 !important;
            background: transparent !important;
            color: #cffafe !important;
            font-size: 0.92rem !important;
            line-height: 1.75 !important;
            white-space: pre !important;
        }

        .blog-content > code[class^="language-"],
        .blog-content > code[class*=" language-"] {
            display: block !important;
            margin: 1.75rem 0 !important;
            padding: 1.25rem !important;
            overflow-x: auto !important;
            border: 1px solid rgba(96, 165, 250, 0.28) !important;
            border-radius: 1rem !important;
            background:
                linear-gradient(180deg, rgba(15, 23, 42, 0.96), rgba(2, 6, 23, 0.96)) !important;
            box-shadow: 0 22px 60px rgba(0, 0, 0, 0.34) !important;
            color: #cffafe !important;
            font-size: 0.92rem !important;
            line-height: 1.75 !important;
            white-space: pre !important;
        }
    </style>
@endsection

@section('content')
<div class="relative min-h-screen pt-10">
    <!-- Background Gradient -->
    <div class="absolute top-0 inset-x-0 h-[500px] bg-gradient-to-b from-primary-600/10 to-transparent pointer-events-none"></div>

    <article class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16 pt-4 relative z-10">
        <!-- Header -->
        <header class="text-left mb-16 animate-fade-in border-l-2 border-primary-500 pl-8">
            <div class="flex items-center justify-start gap-4 mb-6">
                <span class="px-3 py-1 bg-primary-500/10 text-primary-400 text-xs font-normal rounded-full border border-primary-500/20 uppercase tracking-widest leading-6">
                    {{ $blogPost->type }}
                </span>
                <span class="text-text-tertiary h-1 w-1 rounded-full bg-slate-700"></span>
                @if($displayDate = $blogPost->published_at ?? $blogPost->updated_at ?? $blogPost->created_at)
                    <time class="text-text-tertiary font-normal text-xs">
                        {{ $displayDate->format('M d, Y') }}
                    </time>
                @endif
                <span class="text-slate-400 h-1 w-1 rounded-full bg-slate-700"></span>
                <span class="text-slate-400 font-normal text-xs">{{ $blogPost->getReadingTime($contentLocale) }} min read</span>
            </div>

            <h1 class="text-2xl md:text-5xl font-bold mb-10 text-white tracking-tight leading-tight font-bold">
                {{ $blogPost->getTranslation('title', $contentLocale) }}
            </h1>

            @if($blogPost->getTranslation('excerpt', $contentLocale))
                <p class="text-base md:text-base text-slate-400 mb-8 leading-relaxed font-normal italic">
                    {{ $blogPost->getTranslation('excerpt', $contentLocale) }}
                </p>
            @endif

            @if($blogPost->thumbnail)
                <div class="rounded-3xl overflow-hidden glass-card aspect-video mb-16">
                    <img src="{{ Storage::disk('public')->url($blogPost->thumbnail) }}" alt="{{ $blogPost->getTranslation('title', $contentLocale) }}" class="w-full h-full object-cover">
                </div>
            @endif
        </header>

        <!-- Content -->
        <div class="blog-content prose prose-invert max-w-none 
            prose-headings:text-white prose-headings:font-bold prose-headings:tracking-tight
            prose-p:text-text-secondary prose-p:leading-relaxed
            prose-a:text-primary-400 prose-a:no-underline hover:prose-a:text-neon-cyan prose-a:transition-colors
            prose-code:text-primary-300 prose-code:bg-slate-900/50 prose-code:px-1.5 prose-code:py-0.5 prose-code:rounded prose-code:before:content-none prose-code:after:content-none
            prose-code:font-['Fira_Code','JetBrains_Mono',monospace] prose-code:[font-variant-ligatures:none]
            [&_pre]:my-7 [&_pre]:overflow-x-auto [&_pre]:rounded-2xl [&_pre]:border [&_pre]:border-primary-400/20 [&_pre]:bg-[#081120] [&_pre]:p-5 [&_pre]:shadow-2xl [&_pre]:shadow-black/30
            [&_pre_code]:block [&_pre_code]:bg-transparent [&_pre_code]:p-0 [&_pre_code]:text-sm [&_pre_code]:leading-7 [&_pre_code]:text-cyan-100 [&_pre_code]:whitespace-pre
            prose-blockquote:border-l-primary-500 prose-blockquote:bg-primary-500/5 prose-blockquote:py-2 prose-blockquote:px-6 prose-blockquote:rounded-r-xl prose-blockquote:italic prose-blockquote:text-text-secondary
            prose-img:rounded-2xl prose-img:shadow-2xl
            prose-strong:text-white prose-strong:font-semibold
            animate-slide-up">
            {!! $htmlContent !!}
        </div>

        <!-- Footer / Navigation -->
        <footer class="mt-20 pt-10 border-t border-white/5 flex flex-col md:flex-row items-center justify-between gap-8">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 rounded-full bg-gradient-to-br from-primary-500 to-neon-cyan flex items-center justify-center p-0.5">
                    <div class="w-full h-full rounded-full bg-slate-900 flex items-center justify-center overflow-hidden">
                        <img src="{{ asset('favicon-512.png') }}" alt="SaaS Starter" class="w-8 h-8 opacity-90">
                    </div>
                </div>
                <div>
                    <p class="text-white font-semibold">{{ $blogPost->author->name ?? 'SaaS Starter' }}</p>
                    <p class="text-slate-400 text-sm">{{ __('messages.blog.author_tagline') }}</p>
                </div>
            </div>

            <div class="flex items-center gap-4">
                <a href="{{ localized_route('blog.index') }}" class="px-6 py-3 bg-slate-900/50 hover:bg-slate-800/80 text-white rounded-full border border-white/10 transition-all flex items-center gap-2">
                    <i class="lucide-list w-4 h-4"></i>
                    {{ __('messages.blog.back_to_list') ?? 'Back to Blog' }}
                </a>
            </div>
        </footer>
    </article>
</div>

@endsection
