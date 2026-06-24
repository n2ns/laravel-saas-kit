@props(['section', 'content' => []])

@php
    $dataSource = $section['data_source'] ?? 'article';
    $body = is_string($content[$dataSource] ?? null) ? $content[$dataSource] : '';
    $title = $content["{$dataSource}_title"] ?? $section['title'] ?? null;
    $html = $body !== '' ? app(\Spatie\LaravelMarkdown\MarkdownRenderer::class)->toHtml($body) : '';
@endphp

@if($body !== '')
<section {{ $attributes->merge(['class' => 'mx-auto mb-12 max-w-4xl']) }}>
    @if($title)
        <h2 class="text-[19px] md:text-[22px] font-semibold text-white mb-4 tracking-tight">{{ $title }}</h2>
    @endif

    <div class="prose prose-invert max-w-none prose-p:text-slate-300 prose-p:leading-7 prose-li:text-slate-300 prose-strong:text-white prose-a:text-[var(--brand-primary)]">
        {!! $html !!}
    </div>
</section>
@endif
