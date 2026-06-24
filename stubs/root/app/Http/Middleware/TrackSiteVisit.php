<?php

namespace App\Http\Middleware;

use App\Services\SiteAnalyticsTracker;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TrackSiteVisit
{
    public function __construct(
        private readonly SiteAnalyticsTracker $tracker,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if ($this->tracker->shouldTrackVisit($request, $response->getStatusCode())) {
            $this->tracker->trackVisit($request);
        }

        return $response;
    }
}
