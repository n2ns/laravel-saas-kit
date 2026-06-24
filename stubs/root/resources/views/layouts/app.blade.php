<!DOCTYPE html>
@php
    $pageTitle = View::getSection('title', __('messages.meta.title'));
    $pageDescription = View::getSection('meta_description', __('messages.meta.description'));
    $pageKeywords = View::getSection('meta_keywords', __('messages.meta.keywords'));
    $pageImage = View::getSection('og_image', asset('images/og-image.png'));
    $pageImageAlt = View::getSection('og_image_alt', config('app.name'));
    $pageType = View::getSection('og_type', 'website');
    $pageRobots = View::getSection('robots', 'index, follow');
    $pageCanonicalUrl = $seoCanonicalUrl ?? View::getSection('canonical_url', url()->current());
    $pageAlternates = $seoAlternates ?? null;
    $pageLocale = \App\Support\LocaleProfile::normalize(request()->attributes->get('seo_content_locale', $seoContentLocale ?? app()->getLocale()));
    $pageOgLocale = match ($pageLocale) {
        'zh_CN' => 'zh_CN',
        'en' => 'en_US',
        default => $pageLocale.'_'.strtoupper($pageLocale),
    };
@endphp
<html lang="{{ str_replace('_', '-', $pageLocale) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $pageTitle }}</title>
    <meta name="description" content="{{ $pageDescription }}">
    <meta name="keywords" content="{{ $pageKeywords }}">
    <meta name="author" content="{{ config('app.company_name', config('app.name')) }}">
    <meta name="robots" content="{{ $pageRobots }}">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <!-- Canonical URL -->
    <link rel="canonical" href="{{ $pageCanonicalUrl }}">

    <!-- Hreflang for Multi-language SEO -->
    @php
        $localeProfile = \App\Support\LocaleProfile::class;
        $supportedLocales = $localeProfile::map();
    @endphp
    @if(is_array($pageAlternates))
        @foreach ($pageAlternates as $hreflang => $alternateUrl)
            <link rel="alternate" hreflang="{{ $hreflang }}" href="{{ $alternateUrl }}">
        @endforeach
    @else
        @php
            $currentPath = request()->path();
            $basePath = $localeProfile::stripPrefixFromPath($currentPath);
            $xDefaultPrefix = $localeProfile::defaultPrefix();
        @endphp
        @foreach ($localeProfile::alternates() as $hreflang => $prefix)
            @php
                $prefixPath = $prefix ? '/' . $prefix : '';
                $urlPath = $prefixPath . ($basePath === '/' ? '' : '/' . ltrim($basePath, '/'));
            @endphp
            <link rel="alternate" hreflang="{{ $hreflang }}" href="{{ url($urlPath) }}">
        @endforeach
        @php
            $xDefaultUrlPath = ($xDefaultPrefix ? '/' . $xDefaultPrefix : '') . ($basePath === '/' ? '' : '/' . ltrim($basePath, '/'));
        @endphp
        <link rel="alternate" hreflang="x-default" href="{{ url($xDefaultUrlPath) }}">
    @endif

    <!-- Open Graph -->
    <meta property="og:title" content="{{ $pageTitle }}">
    <meta property="og:description" content="{{ $pageDescription }}">
    <meta property="og:type" content="{{ $pageType }}">
    <meta property="og:site_name" content="{{ __('messages.meta.site_name') }}">
    <meta property="og:url" content="{{ $pageCanonicalUrl }}">
    <meta property="og:locale" content="{{ $pageOgLocale }}">
        @foreach ($supportedLocales as $supportedLocale)
            @php
                $alternateLocale = match ($supportedLocale) {
                    'zh_CN' => 'zh_CN',
                    'en' => 'en_US',
                    default => $supportedLocale.'_'.strtoupper($supportedLocale),
                };
            @endphp
            <meta property="og:locale:alternate" content="{{ $alternateLocale }}">
    @endforeach
    <meta property="og:image" content="{{ $pageImage }}">
    <meta property="og:image:alt" content="{{ $pageImageAlt }}">

    <!-- Twitter Card -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $pageTitle }}">
    <meta name="twitter:description" content="{{ $pageDescription }}">
    <meta name="twitter:image" content="{{ $pageImage }}">

    @include('partials.structured-data.json-ld', ['data' => \App\Support\StructuredData::organization()])
    @include('partials.structured-data.json-ld', ['data' => \App\Support\StructuredData::website()])

    <!-- Google Fonts: Space Grotesk (headings) + DM Sans (body) — UIpro Tech Startup recommendation -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,300;0,9..40,400;0,9..40,500;0,9..40,700;1,9..40,400&family=Space+Grotesk:wght@400;500;600;700&display=swap" rel="stylesheet">

    <!-- Devicon -->
    <link rel="stylesheet" type='text/css' href="https://cdn.jsdelivr.net/npm/devicon@latest/devicon.min.css" />

    <!-- Flag Icons (Required for Windows which doesn't render flag emojis) -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/flag-icon-css/7.1.0/css/flag-icons.min.css" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    @yield('extra_meta')
    @stack('structured_data')
</head>
<body class="antialiased bg-bg-body text-text-primary font-sans selection:bg-[#a34f1f] selection:text-white overflow-x-hidden">
    <!-- Navigation -->
    @include('partials.header')

    <!-- Main Content -->
    <main class="pt-20">
        @yield('content')
    </main>

    <!-- Footer -->
    @include('partials.footer')


    <!-- Global Google One Tap (Only for Guests) -->

    @guest
        <div id="g_id_onload"
             data-client_id="{{ config('services.google.client_id') }}"
             data-login_uri="{{ route('auth.google.one-tap') }}"
             data-auto_prompt="true"
             data-use_fedcm_for_prompt="{{ app()->isProduction() ? 'true' : 'false' }}"
             data-hl="{{ app()->getLocale() }}">
        </div>
        
        
        <script src="https://accounts.google.com/gsi/client?hl={{ app()->getLocale() }}" async defer></script>
    @endguest

    {{-- Cookie Consent Banner --}}
    @include('partials.cookie-consent')

</body>
</html>
