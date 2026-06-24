<?php

namespace App\Http\Controllers;

use App\Services\SiteAnalyticsTracker;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class SiteEventController extends Controller
{
    public function store(Request $request, SiteAnalyticsTracker $tracker): JsonResponse|Response
    {
        $validated = $request->validate([
            'event_name' => ['required', 'string', 'max:80'],
            'event_type' => ['required', 'string', 'max:40'],
            'path' => ['nullable', 'string', 'max:2048'],
            'target_url' => ['nullable', 'string', 'max:2048'],
            'catalog_item_code' => ['nullable', 'string', 'max:120'],
            'blog_post_id' => ['nullable', 'integer'],
        ]);

        $tracker->trackEvent($request, $validated);

        return response()->noContent();
    }
}
