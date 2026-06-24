{{--
    Code Block Component (Laravel 12 Anonymous Component)

    Usage: <x-products.code-block :section="$section" />

    @props array $section Configuration array with:
        - title_key: Translation key for the title
        - title: Direct title text
        - language: Code language (json, javascript, bash, etc.)
        - code: The code content to display
--}}

@props(['section'])

@php
    // Zero-Style Refactoring: Pure content-driven title
    $title = $section['title'] ?? (isset($section['title_key']) ? __($section['title_key']) : null);
    $code = $section['code'] ?? '';

    // Language-generic, escape-safe syntax highlighting.
    // Runs over the HTML-escaped code, so quote/string patterns match entities
    // (&quot; / &#039;) rather than raw quotes. Order matters: first match wins
    // per position (comment → key → string → url → number → boolean). A bare
    // url is kept as one token so its host/port digits are not split; urls
    // inside quoted strings are already covered by the string rule above it.
    $highlightPattern = '~(?P<comment>^[ \t]*(?:\/\/|\#)[^\n]*)'
        . '|(?P<key>&quot;(?:(?!&quot;)[^\n])*&quot;(?=\s*:))'
        . '|(?P<str>&quot;(?:(?!&quot;)[^\n])*&quot;|&\#039;(?:(?!&\#039;)[^\n])*&\#039;)'
        . '|(?P<url>https?:\/\/\S+)'
        . '|(?P<num>(?<![\w./:])\d+(?:\.\d+)?(?![\w./:]))'
        . '|(?P<bool>\b(?:true|false|null)\b)~m';

    $highlighted = preg_replace_callback($highlightPattern, function ($m) {
        if (($m['comment'] ?? '') !== '') return '<span class="text-slate-500">' . $m['comment'] . '</span>';
        if (($m['key'] ?? '') !== '')     return '<span class="text-sky-300">' . $m['key'] . '</span>';
        if (($m['str'] ?? '') !== '')     return '<span class="text-emerald-300">' . $m['str'] . '</span>';
        if (($m['url'] ?? '') !== '')     return '<span class="text-emerald-300">' . $m['url'] . '</span>';
        if (($m['num'] ?? '') !== '')     return '<span class="text-amber-300">' . $m['num'] . '</span>';
        if (($m['bool'] ?? '') !== '')    return '<span class="text-violet-300">' . $m['bool'] . '</span>';
        return $m[0];
    }, e($code));
@endphp

<div {{ $attributes->merge(['class' => 'bg-white rounded-3xl border border-black/10 p-8 md:p-12']) }}>
    @if($title)
        <h2 class="text-2xl md:text-2xl font-bold text-slate-950 mb-6">{{ $title }}</h2>
    @endif

    <div class="bg-white rounded-xl p-6 font-mono text-sm overflow-x-auto">
        <pre class="text-slate-700">{!! $highlighted !!}</pre>
    </div>
</div>
