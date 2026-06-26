<?php

namespace Database\Seeders;

use App\Models\BlogPost;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\File;

class BlogPostSeeder extends Seeder
{
    public function run(): void
    {
        $postsPath = database_path('seeders/data/posts');

        if (! File::isDirectory($postsPath)) {
            $this->command?->warn("Posts directory not found: {$postsPath}");

            return;
        }

        $author = $this->contentAuthor();
        $relatedBySlug = [];

        foreach ($this->loadPostSources($postsPath) as $source => $data) {
            if (! isset($data['slug'])) {
                $this->command?->warn("Invalid blog post source skipped: {$source}");
                continue;
            }

            $post = BlogPost::firstOrNew(['slug' => $data['slug']]);
            $post->fill([
                'user_id' => $author->id,
                'type' => $data['type'] ?? BlogPost::defaultType(),
                'geo_tags' => $this->normalizedList($data['geo_tags'] ?? null, uppercase: true),
                'topics' => $this->validTopics($data['topics'] ?? null),
                'seo_keywords' => $this->normalizedList($data['seo_keywords'] ?? null),
                'related_slugs' => null,
                'thumbnail' => $data['thumbnail'] ?? null,
                'status' => $data['status'] ?? 'draft',
                'is_pinned' => (bool) ($data['is_pinned'] ?? false),
                'pin_order' => (int) ($data['pin_order'] ?? 0),
                'title' => $data['title'][\App\Support\LocaleProfile::default()] ?? '',
                'excerpt' => $data['excerpt'][\App\Support\LocaleProfile::default()] ?? null,
                'content' => $data['content'][\App\Support\LocaleProfile::default()] ?? '',
            ]);

            $post->pinned_until = ! empty($data['pinned_until'])
                ? Carbon::parse($data['pinned_until'])->utc()->toDateTimeString()
                : null;

            if (! empty($data['published_at'])) {
                $post->published_at = Carbon::parse($data['published_at'])->utc()->toDateTimeString();
            } elseif (! $post->exists && $post->status === 'published') {
                $post->published_at = now()->toDateTimeString();
            }

            $post->save();

            $this->syncTranslations($post, $data);

            if (! empty($data['related_slugs']) && is_array($data['related_slugs'])) {
                $relatedBySlug[$data['slug']] = $data['related_slugs'];
            }

            $this->command?->line("Seeded blog post: {$data['slug']}");
        }

        $this->syncRelatedSlugs($relatedBySlug);
    }

    private function contentAuthor(): User
    {
        return User::firstOrCreate(
            ['email' => 'content@example.com'],
            [
                'name' => 'Content Team',
                'is_admin' => false,
                'email_verified_at' => now(),
                'password' => null,
            ]
        );
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function loadPostSources(string $postsPath): array
    {
        $sources = [];

        foreach (File::directories($postsPath) as $directory) {
            $metaFile = $directory.'/meta.json';
            if (! File::exists($metaFile)) {
                continue;
            }

            $source = basename($directory).'/meta.json';
            $data = $this->decodeJsonFile($metaFile, $source);
            if ($data === null) {
                continue;
            }

            $data = $this->mergeMarkdownTranslations($directory, $data);
            if (($data['slug'] ?? null) !== basename($directory)) {
                $this->command?->warn("Post slug does not match directory name: {$directory}");
            }
            $this->reportFieldIssues($source, $data);

            $sources[$source] = $data;
        }

        ksort($sources);

        return $sources;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function decodeJsonFile(string $path, string $source): ?array
    {
        $data = json_decode(File::get($path), true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->command?->warn("Malformed JSON skipped: {$source} (".json_last_error_msg().')');

            return null;
        }

        if (! is_array($data)) {
            $this->command?->warn("Invalid JSON object skipped: {$source}");

            return null;
        }

        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function reportFieldIssues(string $source, array $data): void
    {
        if (isset($data['type']) && ! in_array($data['type'], BlogPost::typeCodes(), true)) {
            $this->command?->warn("Invalid blog post type in {$source}: ".$this->diagnosticValue($data['type']));
        }

        if (isset($data['status']) && ! in_array($data['status'], ['draft', 'published'], true)) {
            $this->command?->warn("Invalid blog post status in {$source}: ".$this->diagnosticValue($data['status']));
        }

        foreach ($this->invalidValues($data['topics'] ?? null, BlogPost::topicCodes()) as $topic) {
            $this->command?->warn("Unknown blog topic in {$source}: {$topic}");
        }

        foreach ($this->invalidGeoTags($data['geo_tags'] ?? null) as $geoTag) {
            $this->command?->warn("Invalid geo tag in {$source}: {$geoTag}");
        }
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function mergeMarkdownTranslations(string $directory, array $data): array
    {
        foreach (File::glob($directory.'/*.md') as $markdownFile) {
            $locale = basename($markdownFile, '.md');
            $parsed = $this->parseMarkdownWithFrontMatter(File::get($markdownFile));

            $data['title'][$locale] = $parsed['front_matter']['title'] ?? $data['title'][$locale] ?? '';
            $data['excerpt'][$locale] = $parsed['front_matter']['excerpt'] ?? $data['excerpt'][$locale] ?? null;
            $data['content'][$locale] = $parsed['content'];
        }

        return $data;
    }

    /**
     * @return array{front_matter: array<string, string>, content: string}
     */
    private function parseMarkdownWithFrontMatter(string $markdown): array
    {
        $markdown = str_replace(["\r\n", "\r"], "\n", $markdown);
        if (! str_starts_with($markdown, "---\n")) {
            return ['front_matter' => [], 'content' => trim($markdown)];
        }

        $end = strpos($markdown, "\n---\n", 4);
        if ($end === false) {
            return ['front_matter' => [], 'content' => trim($markdown)];
        }

        return [
            'front_matter' => $this->parseSimpleFrontMatter(substr($markdown, 4, $end - 4)),
            'content' => trim(substr($markdown, $end + 5)),
        ];
    }

    /**
     * @return array<string, string>
     */
    private function parseSimpleFrontMatter(string $frontMatter): array
    {
        $result = [];

        foreach (explode("\n", $frontMatter) as $line) {
            if (! str_contains($line, ':')) {
                continue;
            }

            [$key, $value] = explode(':', $line, 2);
            $result[trim($key)] = trim(trim($value), '"\'');
        }

        return $result;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function syncTranslations(BlogPost $post, array $data): void
    {
        $locales = array_unique(array_merge(
            array_keys($data['title'] ?? []),
            array_keys($data['content'] ?? []),
            array_keys($data['excerpt'] ?? [])
        ));

        foreach ($locales as $locale) {
            if ($locale === \App\Support\LocaleProfile::default()) {
                continue;
            }

            $post->translations()->updateOrCreate(
                ['locale' => $locale],
                [
                    'title' => $data['title'][$locale] ?? null,
                    'excerpt' => $data['excerpt'][$locale] ?? null,
                    'content' => $data['content'][$locale] ?? null,
                ]
            );
        }
    }

    /**
     * @param  array<string, array<int, string>>  $relatedBySlug
     */
    private function syncRelatedSlugs(array $relatedBySlug): void
    {
        if ($relatedBySlug === []) {
            return;
        }

        $existingSlugs = BlogPost::query()->pluck('slug')->flip();

        foreach ($relatedBySlug as $slug => $relatedSlugs) {
            foreach ($relatedSlugs as $relatedSlug) {
                if (! is_string($relatedSlug) || $relatedSlug === $slug || ! $existingSlugs->has($relatedSlug)) {
                    $this->command?->warn("Ignored related slug for {$slug}: ".(is_scalar($relatedSlug) ? (string) $relatedSlug : gettype($relatedSlug)));
                }
            }

            $resolved = collect($relatedSlugs)
                ->filter(fn ($relatedSlug): bool => is_string($relatedSlug) && $relatedSlug !== $slug && $existingSlugs->has($relatedSlug))
                ->values()
                ->all();

            $post = BlogPost::query()->where('slug', $slug)->first();
            if (! $post) {
                continue;
            }

            $post->related_slugs = $resolved === [] ? null : $resolved;
            $post->save();
        }
    }

    /**
     * @param  array<int, mixed>|null  $values
     * @return array<int, string>|null
     */
    private function normalizedList(?array $values, bool $uppercase = false): ?array
    {
        if ($values === null) {
            return null;
        }

        $normalized = collect($values)
            ->filter(fn ($value): bool => is_string($value) || is_numeric($value))
            ->map(fn ($value): string => trim((string) $value))
            ->filter()
            ->map(fn (string $value): string => $uppercase ? strtoupper($value) : $value)
            ->unique()
            ->values()
            ->all();

        return $normalized === [] ? null : $normalized;
    }

    /**
     * @param  array<int, mixed>|null  $topics
     * @return array<int, string>|null
     */
    private function validTopics(?array $topics): ?array
    {
        $normalized = $this->normalizedList($topics);

        if ($normalized === null) {
            return null;
        }

        $valid = array_values(array_intersect($normalized, BlogPost::topicCodes()));

        return $valid === [] ? null : $valid;
    }

    /**
     * @param  mixed  $values
     * @param  array<int, string>  $allowed
     * @return array<int, string>
     */
    private function invalidValues(mixed $values, array $allowed): array
    {
        if (! is_array($values)) {
            return [];
        }

        return collect($values)
            ->filter(fn ($value): bool => is_string($value) && ! in_array($value, $allowed, true))
            ->values()
            ->all();
    }

    /**
     * @return array<int, string>
     */
    private function invalidGeoTags(mixed $values): array
    {
        if (! is_array($values)) {
            return [];
        }

        return collect($values)
            ->filter(fn ($value): bool => is_string($value) && ! preg_match('/^[A-Z]{2}$/', $value))
            ->values()
            ->all();
    }

    private function diagnosticValue(mixed $value): string
    {
        return is_scalar($value) ? (string) $value : gettype($value);
    }
}
