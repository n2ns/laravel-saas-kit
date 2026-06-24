<?php

namespace App\Services;

use App\Models\BlogPost;
use App\Models\CatalogItem;
use App\Models\SiteEventDailyStat;
use App\Models\SiteEventUnique;
use App\Models\SiteVisitDailyStat;
use App\Models\SiteVisitUnique;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class SiteAnalyticsTracker
{
    public function shouldTrackVisit(Request $request, int $statusCode): bool
    {
        if (! config('site_analytics.enabled', true) || $this->isBot($request)) {
            return false;
        }

        if (! $request->isMethod('GET') || $statusCode < 200 || $statusCode >= 400) {
            return false;
        }

        if ($request->expectsJson() || $this->isIgnoredPath($request)) {
            return false;
        }

        $routeName = $request->route()?->getName();

        return $routeName !== null && ! Str::contains($routeName, ['preview']);
    }

    public function trackVisit(Request $request): void
    {
        $locale = app()->getLocale();
        $visitDate = now()->toDateString();
        $source = $this->sourceFromRequest($request);
        $countryCode = $this->countryCodeFromRequest($request);
        $visitorHash = $this->visitorHash($request, $visitDate);
        $context = $this->pageContext($request);
        $path = '/'.ltrim($request->path(), '/');
        $pathHash = hash('sha256', $path);

        try {
            DB::transaction(function () use ($context, $countryCode, $locale, $path, $pathHash, $source, $visitDate, $visitorHash): void {
                $daily = SiteVisitDailyStat::query()->firstOrCreate([
                    'visit_date' => $visitDate,
                    'locale' => $locale,
                    'path_hash' => $pathHash,
                    'source_key' => $source['source_key'],
                ], [
                    'path' => $path,
                    'route_name' => $context['route_name'],
                    'page_type' => $context['page_type'],
                    'catalog_item_id' => $context['catalog_item_id'],
                    'blog_post_id' => $context['blog_post_id'],
                    'source_type' => $source['source_type'],
                    'country_code' => $countryCode,
                    'referrer_host' => $source['referrer_host'],
                    'utm_source' => $source['utm_source'],
                    'utm_medium' => $source['utm_medium'],
                    'utm_campaign' => $source['utm_campaign'],
                ]);

                $daily->increment('views');

                $inserted = SiteVisitUnique::query()->insertOrIgnore([
                    'visit_date' => $visitDate,
                    'locale' => $locale,
                    'path_hash' => $pathHash,
                    'source_key' => $source['source_key'],
                    'visitor_hash' => $visitorHash,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                if ($inserted > 0) {
                    $daily->increment('unique_visitors');
                }
            });
        } catch (Throwable $exception) {
            Log::warning('Site visit tracking failed.', [
                'path' => $path,
                'message' => $exception->getMessage(),
            ]);
        }
    }

    public function trackEvent(Request $request, array $payload): void
    {
        if (! config('site_analytics.enabled', true) || $this->isBot($request)) {
            return;
        }

        $locale = app()->getLocale();
        $eventDate = now()->toDateString();
        $path = $this->cleanPath((string) ($payload['path'] ?? $request->headers->get('referer', '')));
        $targetUrl = $this->cleanUrl($payload['target_url'] ?? null);
        $pathHash = hash('sha256', $path ?: 'unknown');
        $targetHash = hash('sha256', $targetUrl ?: 'none');
        $eventName = $this->cleanKey((string) ($payload['event_name'] ?? 'site_event'), 80);
        $eventType = $this->cleanKey((string) ($payload['event_type'] ?? 'interaction'), 40);
        $eventKey = hash('sha256', implode('|', [$eventName, $eventType, $pathHash, $targetHash]));
        $visitorHash = $this->visitorHash($request, $eventDate);
        $context = $this->eventContext($payload);

        try {
            DB::transaction(function () use ($context, $eventDate, $eventKey, $eventName, $eventType, $locale, $path, $pathHash, $targetHash, $targetUrl, $visitorHash): void {
                $daily = SiteEventDailyStat::query()->firstOrCreate([
                    'event_date' => $eventDate,
                    'locale' => $locale,
                    'event_name' => $eventName,
                    'event_type' => $eventType,
                    'path_hash' => $pathHash,
                    'target_hash' => $targetHash,
                ], [
                    'path' => $path ?: null,
                    'target_url' => $targetUrl,
                    'catalog_item_id' => $context['catalog_item_id'],
                    'blog_post_id' => $context['blog_post_id'],
                ]);

                $daily->increment('events');

                $inserted = SiteEventUnique::query()->insertOrIgnore([
                    'event_date' => $eventDate,
                    'locale' => $locale,
                    'event_key' => $eventKey,
                    'visitor_hash' => $visitorHash,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                if ($inserted > 0) {
                    $daily->increment('unique_visitors');
                }
            });
        } catch (Throwable $exception) {
            Log::warning('Site event tracking failed.', [
                'event_name' => $eventName,
                'event_type' => $eventType,
                'message' => $exception->getMessage(),
            ]);
        }
    }

    /**
     * @return array{
     *     route_name: string|null,
     *     page_type: string,
     *     catalog_item_id: int|null,
     *     blog_post_id: int|null
     * }
     */
    private function pageContext(Request $request): array
    {
        $route = $request->route();
        $routeName = $route?->getName();
        $baseRouteName = $this->baseRouteName($routeName);
        $parameters = $route?->parameters() ?? [];

        $blogPostId = null;
        $catalogItemId = null;

        if ($baseRouteName === 'blog.show' && isset($parameters['slug'])) {
            $requestBlogPostId = $request->attributes->get('site_analytics_blog_post_id');
            $blogPostId = is_numeric($requestBlogPostId)
                ? (int) $requestBlogPostId
                : BlogPost::query()->where('slug', $parameters['slug'])->value('id');
        }

        if ($baseRouteName === 'catalog.guides.show' && isset($parameters['productCode'], $parameters['slug'])) {
            $blogPostId = BlogPost::query()
                ->where('slug', $parameters['slug'])
                ->where('content_scope', BlogPost::productContentScope((string) $parameters['productCode']))
                ->value('id');
        }

        $catalogCode = match ($baseRouteName) {
            'catalog.show', 'catalog.pricing', 'checkout.create' => $parameters['slug'] ?? $parameters['product'] ?? null,
            'catalog.guides.index', 'catalog.guides.show' => $parameters['productCode'] ?? null,
            default => null,
        };

        if (is_string($catalogCode)) {
            $catalogItemId = CatalogItem::query()->where('code', $catalogCode)->value('id');
        }

        return [
            'route_name' => $routeName,
            'page_type' => $this->pageType($baseRouteName),
            'catalog_item_id' => $catalogItemId,
            'blog_post_id' => $blogPostId,
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array{catalog_item_id: int|null, blog_post_id: int|null}
     */
    private function eventContext(array $payload): array
    {
        $catalogItemId = null;
        $blogPostId = null;

        $catalogCode = $payload['catalog_item_code'] ?? null;
        if (is_string($catalogCode) && $catalogCode !== '') {
            $catalogItemId = CatalogItem::query()->where('code', $catalogCode)->value('id');
        }

        $payloadBlogPostId = $payload['blog_post_id'] ?? null;
        if (is_numeric($payloadBlogPostId)) {
            $blogPostId = BlogPost::query()->whereKey((int) $payloadBlogPostId)->value('id');
        }

        return [
            'catalog_item_id' => $catalogItemId,
            'blog_post_id' => $blogPostId,
        ];
    }

    private function pageType(?string $routeName): string
    {
        return match ($routeName) {
            'home' => 'home',
            'products.index' => 'product_index',
            'catalog.show', 'catalog.pricing' => 'catalog',
            'blog.index' => 'blog_index',
            'blog.show' => 'blog_post',
            'catalog.guides.index' => 'guide_index',
            'catalog.guides.show' => 'guide',
            'checkout.create', 'checkout.success', 'checkout.cancel', 'checkout.portal' => 'checkout',
            'login', 'auth.google', 'auth.google.callback' => 'auth',
            default => 'page',
        };
    }

    private function baseRouteName(?string $routeName): ?string
    {
        return is_string($routeName) && str_starts_with($routeName, 'localized.')
            ? substr($routeName, strlen('localized.'))
            : $routeName;
    }

    /**
     * @return array{
     *     source_key: string,
     *     source_type: string,
     *     referrer_host: string|null,
     *     utm_source: string|null,
     *     utm_medium: string|null,
     *     utm_campaign: string|null
     * }
     */
    private function sourceFromRequest(Request $request): array
    {
        $utmSource = $this->queryString($request, 'utm_source', 100);
        $utmMedium = $this->queryString($request, 'utm_medium', 100);
        $utmCampaign = $this->queryString($request, 'utm_campaign', 150);
        $referrerHost = $this->referrerHost($request);

        $sourceType = match (true) {
            $utmSource !== null => 'utm',
            $referrerHost === null => 'direct',
            $this->isInternalHost($request, $referrerHost) => 'internal',
            $this->isSearchHost($referrerHost) => 'search',
            $this->isSocialHost($referrerHost) => 'social',
            default => 'referral',
        };

        $sourceKey = hash('sha256', json_encode([
            'source_type' => $sourceType,
            'referrer_host' => $referrerHost,
            'utm_source' => $utmSource,
            'utm_medium' => $utmMedium,
            'utm_campaign' => $utmCampaign,
        ], JSON_THROW_ON_ERROR));

        return [
            'source_key' => $sourceKey,
            'source_type' => $sourceType,
            'referrer_host' => $referrerHost,
            'utm_source' => $utmSource,
            'utm_medium' => $utmMedium,
            'utm_campaign' => $utmCampaign,
        ];
    }

    private function queryString(Request $request, string $key, int $limit): ?string
    {
        $value = trim((string) $request->query($key, ''));

        return $value === '' ? null : Str::limit(strip_tags($value), $limit, '');
    }

    private function referrerHost(Request $request): ?string
    {
        $referrer = $request->headers->get('referer');
        if (! $referrer) {
            return null;
        }

        $host = parse_url($referrer, PHP_URL_HOST);

        return is_string($host) ? $this->normalizeHost($host) : null;
    }

    private function countryCodeFromRequest(Request $request): ?string
    {
        $countryCode = Str::upper(trim((string) $request->headers->get('CF-IPCountry', '')));

        if ($countryCode === '' || $countryCode === 'XX' || ! preg_match('/^[A-Z]{2}$/', $countryCode)) {
            return null;
        }

        return $countryCode;
    }

    private function normalizeHost(?string $host): ?string
    {
        if (! $host) {
            return null;
        }

        $host = Str::lower(trim($host, " \t\n\r\0\x0B."));

        return Str::startsWith($host, 'www.') ? substr($host, 4) : $host;
    }

    private function isInternalHost(Request $request, string $host): bool
    {
        $currentHost = $this->normalizeHost($request->getHost());
        $configuredHost = parse_url((string) config('app.url'), PHP_URL_HOST);
        $appHost = is_string($configuredHost) ? $this->normalizeHost($configuredHost) : null;

        return in_array($host, array_filter([$currentHost, $appHost]), true);
    }

    private function isSearchHost(string $host): bool
    {
        return Str::contains($host, [
            'google.',
            'bing.com',
            'duckduckgo.com',
            'yahoo.',
            'yandex.',
            'baidu.',
            'naver.',
            'sogou.',
        ]);
    }

    private function isSocialHost(string $host): bool
    {
        return Str::contains($host, [
            'facebook.com',
            'instagram.com',
            'linkedin.com',
            'lnkd.in',
            'reddit.com',
            't.co',
            'twitter.com',
            'x.com',
            'youtube.com',
            'tiktok.com',
        ]);
    }

    private function isBot(Request $request): bool
    {
        $userAgent = Str::lower((string) $request->userAgent());

        if ($userAgent === '') {
            return true;
        }

        return Str::contains($userAgent, config('site_analytics.bot_user_agent_keywords', []));
    }

    private function isIgnoredPath(Request $request): bool
    {
        $path = $request->path();

        foreach (config('site_analytics.ignored_path_prefixes', []) as $prefix) {
            if ($request->is(trim($prefix, '/').'*')) {
                return true;
            }
        }

        return (bool) preg_match('/\.(?:css|js|map|png|jpe?g|gif|svg|webp|ico|woff2?|ttf|eot|json|xml|txt)$/i', $path);
    }

    private function visitorHash(Request $request, string $date): string
    {
        $sessionId = $request->hasSession() ? $request->session()->getId() : '';
        $basis = $sessionId ?: (($request->ip() ?? '').'|'.((string) $request->userAgent()));

        return hash_hmac('sha256', $date.'|'.$basis, (string) config('app.key'));
    }

    private function cleanKey(string $value, int $limit): string
    {
        $clean = Str::of($value)
            ->lower()
            ->replaceMatches('/[^a-z0-9_\-:.]+/', '_')
            ->trim('_')
            ->limit($limit, '')
            ->toString();

        return $clean !== '' ? $clean : 'unknown';
    }

    private function cleanPath(string $value): ?string
    {
        if ($value === '') {
            return null;
        }

        $path = parse_url($value, PHP_URL_PATH);
        if (! is_string($path) || $path === '') {
            $path = $value;
        }

        return Str::limit('/'.ltrim(strip_tags($path), '/'), 2048, '');
    }

    private function cleanUrl(mixed $value): ?string
    {
        if (! is_string($value) || trim($value) === '') {
            return null;
        }

        return Str::limit(strip_tags(trim($value)), 2048, '');
    }
}
