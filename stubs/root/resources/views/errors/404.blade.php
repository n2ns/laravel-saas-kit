@php
    $routeLocale = request()->route('locale');
    $locale = is_string($routeLocale) && \App\Support\LocaleProfile::hasPrefix($routeLocale)
        ? $routeLocale
        : \App\Support\LocaleProfile::prefixFor(app()->getLocale());
@endphp

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', $locale) }}" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, follow">
    <title>{{ __('errors.404.meta_title') }}</title>
    <style>
        :root {
            color-scheme: dark;
            --bg: #06101d;
            --panel: rgba(15, 23, 42, 0.72);
            --line: rgba(148, 163, 184, 0.22);
            --text: #f8fafc;
            --muted: #9fb2cd;
            --accent: #28c9ff;
            --accent-2: #8b5cf6;
        }

        * {
            box-sizing: border-box;
        }

        body {
            min-height: 100vh;
            margin: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 56px 24px;
            background: linear-gradient(135deg, #030712 0%, var(--bg) 58%, #0b1730 100%);
            color: var(--text);
            font-family: ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            position: relative;
        }

        body::before {
            content: "";
            position: fixed;
            inset: 0;
            background-image:
                linear-gradient(rgba(255, 255, 255, 0.035) 1px, transparent 1px),
                linear-gradient(90deg, rgba(255, 255, 255, 0.035) 1px, transparent 1px);
            background-size: 30px 30px;
            mask-image: radial-gradient(circle at 50% 35%, black, transparent 70%);
            pointer-events: none;
        }

        .shell {
            width: min(860px, 100%);
            position: relative;
            z-index: 1;
        }

        .brand {
            display: flex;
            align-items: center;
            gap: 14px;
            margin-bottom: 28px;
        }

        .brand img {
            height: 34px;
            width: auto;
        }

        .panel {
            border: 1px solid var(--line);
            background: var(--panel);
            box-shadow: 0 28px 90px rgba(0, 0, 0, 0.36);
            backdrop-filter: blur(18px);
            padding: clamp(28px, 5vw, 56px);
            border-radius: 8px;
        }

        .eyebrow {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 8px 12px;
            border: 1px solid rgba(40, 201, 255, 0.24);
            border-radius: 999px;
            color: #a5f3fc;
            background: rgba(40, 201, 255, 0.08);
            font-size: 13px;
            font-weight: 700;
            letter-spacing: 0;
            text-transform: uppercase;
        }

        .dot {
            width: 8px;
            height: 8px;
            border-radius: 999px;
            background: var(--accent);
            box-shadow: 0 0 22px rgba(40, 201, 255, 0.9);
        }

        h1 {
            max-width: 680px;
            margin: 24px 0 18px;
            font-size: 2.5rem;
            line-height: 1.02;
            letter-spacing: 0;
        }

        p {
            max-width: 620px;
            margin: 0;
            color: var(--muted);
            font-size: 1rem;
            line-height: 1.7;
        }

        .actions {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            margin-top: 34px;
        }

        a {
            color: inherit;
            text-decoration: none;
        }

        .button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 46px;
            padding: 0 18px;
            border-radius: 8px;
            font-weight: 800;
            border: 1px solid var(--line);
        }

        .button-primary {
            color: #03131f;
            background: linear-gradient(135deg, var(--accent), #67e8f9);
            border-color: transparent;
        }

        .button-secondary {
            color: #dbeafe;
            background: rgba(255, 255, 255, 0.06);
        }

        .meta {
            display: flex;
            flex-wrap: wrap;
            gap: 18px;
            margin-top: 34px;
            padding-top: 22px;
            border-top: 1px solid var(--line);
            color: #cbd5e1;
            font-size: 14px;
        }

        @media (min-width: 768px) {
            h1 {
                font-size: 4.25rem;
            }

            p {
                font-size: 1.125rem;
            }
        }
    </style>
</head>
<body>
    <main class="shell" role="main">
        <div class="brand">
            <img src="{{ asset('badges/site-badge.svg') }}" alt="SaaS Starter">
        </div>

        <section class="panel" aria-labelledby="page-title">
            <div class="eyebrow"><span class="dot"></span>{{ __('errors.404.eyebrow') }}</div>

            <h1 id="page-title">{{ __('errors.404.title') }}</h1>

            <p>{{ __('errors.404.description') }}</p>

            <div class="actions">
                <a class="button button-primary" href="{{ localized_route('home', ['locale' => $locale]) }}">{{ __('errors.404.home') }}</a>
                <a class="button button-secondary" href="{{ localized_route('products.index', ['locale' => $locale]) }}">{{ __('errors.404.products') }}</a>
                <a class="button button-secondary" href="{{ localized_route('support', ['locale' => $locale]) }}">{{ __('errors.404.support') }}</a>
            </div>

            <div class="meta" aria-label="Error details">
                <span>HTTP 404</span>
                <span>{{ __('errors.404.meta_note') }}</span>
            </div>
        </section>
    </main>
</body>
</html>
