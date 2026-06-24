<?php

namespace App\Services;

use App\Models\Product;
use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ProductStatsSyncService
{
    /**
     * Sync stats for all active products.
     */
    public function syncAll(): array
    {
        $products = Product::active()
            ->with('catalogItem.profile')
            ->get();
        $results = [];

        foreach ($products as $product) {
            $results[$product->code] = $this->syncProduct($product);
        }

        return $results;
    }

    /**
     * Sync stats for a specific product.
     */
    public function syncProduct(Product $product): array
    {
        $metadata = $product->metadata ?? [];
        $sources = $metadata['sources'] ?? [];
        $stats = $metadata['stats'] ?? [];
        $hasChanges = false;

        // 1. GitHub Stars & Version
        if (isset($sources['github'])) {
            $ghData = $this->fetchGitHubData($sources['github']);
            if ($ghData) {
                $metadata['stars'] = $ghData['stars'];
                $stats['github_stars'] = $ghData['stars'];
                if ($ghData['version']) {
                    $product->catalogItem?->profile?->update(['version' => $ghData['version']]);
                }
                $hasChanges = true;
            }
        }

        // 2. NPM Downloads
        if (isset($sources['npm'])) {
            $npmDownloads = $this->fetchNpmDownloads($sources['npm']);
            if ($npmDownloads !== null) {
                $stats['npm_downloads'] = $npmDownloads;
                $hasChanges = true;
            }
        }

        // 3. VS Code Marketplace Downloads
        if (isset($sources['vscode'])) {
            $vsCodeDownloads = $this->fetchVsCodeDownloads($sources['vscode']);
            if ($vsCodeDownloads !== null) {
                $stats['vscode_downloads'] = $vsCodeDownloads;
                $hasChanges = true;
            }
        }

        // 4. Open VSX Downloads
        if (isset($sources['open_vsx'])) {
            $openVsxDownloads = $this->fetchOpenVsxDownloads($sources['open_vsx']);
            if ($openVsxDownloads !== null) {
                $stats['open_vsx_downloads'] = $openVsxDownloads;
                $hasChanges = true;
            }
        }

        if ($hasChanges) {
            $metadata['stats'] = $stats;
            $product->metadata = $metadata;
            $product->save();
        }

        return $stats;
    }

    protected function fetchGitHubData(string $repo): ?array
    {
        try {
            $token = config('services.github.token');
            $request = Http::timeout(15);
            if ($token) {
                $request = $request->withToken($token);
            }

            Log::info("Syncing GitHub stats for repository: {$repo}");
            $response = $request->get("https://api.github.com/repos/{$repo}");

            if ($response->successful()) {
                $data = $response->json();
                $stars = $data['stargazers_count'] ?? 0;

                // Try latest release
                $version = null;
                $releaseResponse = $request->get("https://api.github.com/repos/{$repo}/releases/latest");
                if ($releaseResponse->successful()) {
                    $version = $releaseResponse->json()['tag_name'] ?? null;
                }

                // Fallback to tags
                if (! $version) {
                    $tagsResponse = $request->get("https://api.github.com/repos/{$repo}/tags");
                    if ($tagsResponse->successful()) {
                        $tags = $tagsResponse->json();
                        if (! empty($tags)) {
                            $version = $tags[0]['name'] ?? null;
                        }
                    }
                }

                Log::info("GitHub stats for {$repo}: Stars: {$stars}, Version: {$version}");

                return [
                    'stars' => $stars,
                    'version' => $version,
                ];
            } else {
                Log::error("GitHub API error for {$repo}: ".$response->status().' '.$response->body());
            }
        } catch (Exception $e) {
            Log::error("Exception syncing GitHub data for {$repo}: ".$e->getMessage());
        }

        return null;
    }

    protected function fetchNpmDownloads(string $package): ?int
    {
        try {
            // we use point download for last month as a stable metric
            $response = Http::get("https://api.npmjs.org/downloads/point/last-month/{$package}");
            if ($response->successful()) {
                return $response->json()['downloads'] ?? 0;
            }
        } catch (Exception $e) {
            Log::error("Failed to fetch NPM downloads for {$package}: ".$e->getMessage());
        }

        return null;
    }

    protected function fetchVsCodeDownloads(string $extId): ?int
    {
        try {
            $response = Http::post('https://marketplace.visualstudio.com/_apis/public/gallery/extensionquery', [
                'filters' => [[
                    'criteria' => [
                        ['filterType' => 7, 'value' => $extId],
                    ],
                ]],
                'flags' => 914,
            ]);

            if ($response->successful()) {
                $results = $response->json()['results'][0]['extensions'] ?? [];
                if (! empty($results)) {
                    $statistics = $results[0]['statistics'] ?? [];
                    foreach ($statistics as $stat) {
                        if ($stat['statisticName'] === 'install') {
                            return (int) $stat['value'];
                        }
                    }
                }
            }
        } catch (Exception $e) {
            Log::error("Failed to fetch VS Code downloads for {$extId}: ".$e->getMessage());
        }

        return null;
    }

    protected function fetchOpenVsxDownloads(string $extId): ?int
    {
        try {
            $response = Http::get("https://open-vsx.org/api/{$extId}");
            if ($response->successful()) {
                return $response->json()['downloadCount'] ?? 0;
            }
        } catch (Exception $e) {
            Log::error("Failed to fetch Open VSX downloads for {$extId}: ".$e->getMessage());
        }

        return null;
    }
}
