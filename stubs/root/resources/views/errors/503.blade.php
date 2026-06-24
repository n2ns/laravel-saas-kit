<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>SaaS Starter is temporarily unavailable</title>
    <style>
        :root {
            color-scheme: dark;
            --bg: #07111f;
            --line: rgba(148, 163, 184, 0.22);
            --text: #f8fafc;
            --muted: #9fb2cd;
            --accent: #28c9ff;
            --accent-2: #3b82f6;
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
            padding: 64px 32px;
            background:
                radial-gradient(circle at 15% 20%, rgba(40, 201, 255, 0.16), transparent 30%),
                radial-gradient(circle at 85% 75%, rgba(59, 130, 246, 0.14), transparent 32%),
                linear-gradient(135deg, #06101d 0%, var(--bg) 48%, #0b1730 100%);
            color: var(--text);
            font-family: ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            position: relative;
        }

        body::before {
            content: "";
            position: fixed;
            inset: 0;
            background-image:
                linear-gradient(rgba(255, 255, 255, 0.03) 1px, transparent 1px),
                linear-gradient(90deg, rgba(255, 255, 255, 0.03) 1px, transparent 1px);
            background-size: 28px 28px;
            mask-image: radial-gradient(circle at 50% 35%, black, transparent 70%);
            pointer-events: none;
        }

        .content {
            position: relative;
            z-index: 1;
            width: min(760px, 100%);
        }

        .brand {
            display: flex;
            align-items: center;
            gap: 14px;
            padding-bottom: 28px;
            border-bottom: 1px solid var(--line);
            margin-bottom: 40px;
        }

        .brand img {
            height: 32px;
            width: auto;
        }

        .pill {
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
            letter-spacing: 0.08em;
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
            max-width: 620px;
            margin: 24px 0 18px;
            font-size: clamp(34px, 6vw, 56px);
            line-height: 1.05;
            letter-spacing: -0.045em;
        }

        p {
            max-width: 600px;
            margin: 0;
            color: var(--muted);
            font-size: clamp(16px, 2vw, 18px);
            line-height: 1.7;
        }

        .meta {
            display: flex;
            flex-wrap: wrap;
            gap: 24px;
            margin-top: 40px;
            padding-top: 24px;
            border-top: 1px solid var(--line);
            color: #cbd5e1;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <main class="content" role="main">
        <div class="brand">
            <img src="{{ asset('badges/site-badge.svg') }}" alt="SaaS Starter">
        </div>

        <div class="pill"><span class="dot"></span> Maintenance in progress</div>

        <h1>We are updating SaaS Starter.</h1>

        <p>
            The site is temporarily unavailable while a deployment is being completed.
            Please refresh this page in a few minutes.
        </p>

        <div class="meta" aria-label="Maintenance details">
            <span>HTTP 503</span>
            <span>Temporary maintenance window</span>
        </div>
    </main>
</body>
</html>
