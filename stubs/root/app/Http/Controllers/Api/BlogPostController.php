<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreBlogPostRequest;
use App\Http\Requests\Api\UpdateBlogPostRequest;
use App\Models\BlogPost;
use App\Support\LocaleProfile;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class BlogPostController extends Controller
{
    /**
     * Display a listing of blog posts.
     */
    public function index(Request $request): JsonResponse
    {
        $locale = $this->normalizeLocale($request->string('locale')->toString());
        $blogPosts = BlogPost::with('author')
            ->withLocalizedTranslations($locale)
            ->latest()
            ->paginate(20);

        return response()->json($blogPosts);
    }

    /**
     * Store a newly created blog post.
     */
    public function store(StoreBlogPostRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $defaultLocale = LocaleProfile::default();

        return DB::transaction(function () use ($validated, $defaultLocale) {
            // Prepare main table data with fallback locale.
            $blogPostData = [
                'user_id' => Auth::id(),
                'type' => $validated['type'] ?? 'technical',
                'content_scope' => $validated['content_scope'] ?? null,
                'status' => $validated['status'] ?? 'draft',
                'slug' => $validated['slug'],
                'thumbnail' => $validated['thumbnail'] ?? null,
                'published_at' => $validated['published_at'] ?? null,
                // Main table stores fallback locale content
                'title' => $this->primaryLocaleValue($validated['title'] ?? [], $defaultLocale),
                'content' => $this->primaryLocaleValue($validated['content'] ?? [], $defaultLocale),
                'excerpt' => $this->primaryLocaleValue($validated['excerpt'] ?? [], $defaultLocale, true),
            ];

            if (! $blogPostData['published_at'] && $blogPostData['status'] === 'published') {
                $blogPostData['published_at'] = now();
            }

            $blogPost = BlogPost::create($blogPostData);

            // Handle translations
            $this->syncTranslations($blogPost, $validated);
            $this->ensureLocalizedTranslationsLoaded($blogPost, LocaleProfile::default());

            return response()->json([
                'message' => 'Blog post created successfully',
                'blog_post' => $blogPost,
            ], 201);
        });
    }

    /**
     * Display the specified blog post.
     */
    public function show(Request $request, BlogPost $blogPost): JsonResponse
    {
        $locale = $this->normalizeLocale($request->string('locale')->toString());
        $blogPost = $this->ensureLocalizedTranslationsLoaded($blogPost, $locale);

        return response()->json($this->localizedPayload($blogPost, $locale));
    }

    /**
     * Update the specified blog post.
     */
    public function update(UpdateBlogPostRequest $request, BlogPost $blogPost): JsonResponse
    {
        $validated = $request->validated();
        $validationError = $this->validateNextTypeAndScope($blogPost, $validated);
        if ($validationError) {
            return $validationError;
        }

        return DB::transaction(function () use ($blogPost, $validated) {
            $updateData = [];

            // Update simple fields
            foreach (['type', 'content_scope', 'status', 'slug', 'thumbnail', 'published_at'] as $field) {
                if (array_key_exists($field, $validated)) {
                    $updateData[$field] = $validated[$field];
                }
            }

            // Update English fields on main table if provided
            $defaultLocale = LocaleProfile::default();
            if (isset($validated['title'][$defaultLocale])) {
                $updateData['title'] = $validated['title'][$defaultLocale];
            }
            if (isset($validated['content'][$defaultLocale])) {
                $updateData['content'] = $validated['content'][$defaultLocale];
            }
            if (isset($validated['excerpt'][$defaultLocale])) {
                $updateData['excerpt'] = $validated['excerpt'][$defaultLocale];
            }

            if (isset($updateData['status']) && $updateData['status'] === 'published' && ! $blogPost->published_at && ! isset($updateData['published_at'])) {
                $updateData['published_at'] = now();
            }

            if (! empty($updateData)) {
                $blogPost->update($updateData);
            }

            // Handle translations
            $this->syncTranslations($blogPost, $validated);
            $this->ensureLocalizedTranslationsLoaded($blogPost, LocaleProfile::default());

            return response()->json([
                'message' => 'Blog post updated successfully',
                'blog_post' => $blogPost,
            ]);
        });
    }

    /**
     * Remove the specified blog post from storage.
     */
    public function destroy(BlogPost $blogPost): JsonResponse
    {
        $blogPost->delete();

        return response()->json([
            'message' => 'Blog post deleted successfully',
        ]);
    }

    /**
     * Helper to sync translations.
     */
    protected function syncTranslations(BlogPost $blogPost, array $data): void
    {
        $locales = array_unique(array_merge(
            array_keys($data['title'] ?? []),
            array_keys($data['content'] ?? []),
            array_keys($data['excerpt'] ?? [])
        ));

        foreach ($locales as $locale) {
            if ($locale === LocaleProfile::default()) {
                continue;
            } // English is in main table

            $transData = array_filter([
                'title' => $data['title'][$locale] ?? null,
                'content' => $data['content'][$locale] ?? null,
                'excerpt' => $data['excerpt'][$locale] ?? null,
            ], fn ($val) => ! is_null($val));

            if (! empty($transData)) {
                $blogPost->translations()->updateOrCreate(
                    ['locale' => $locale],
                    $transData
                );
            }
        }
    }

    private function normalizeLocale(string $locale): string
    {
        return LocaleProfile::normalize($locale);
    }

    private function primaryLocaleValue(array $values, string $defaultLocale, bool $nullable = false): mixed
    {
        $candidate = $values[$defaultLocale] ?? null;
        if ($candidate !== null && $candidate !== '') {
            return $candidate;
        }

        $fallback = array_key_first($values);
        if ($fallback === null) {
            return $nullable ? null : '';
        }

        return $values[$fallback] ?? ($nullable ? null : '');
    }

    private function ensureLocalizedTranslationsLoaded(BlogPost $blogPost, string $locale): BlogPost
    {
        $targetLocale = $this->normalizeLocale($locale);
        $defaultLocale = LocaleProfile::default();
        $loadLocales = array_values(array_unique([$targetLocale, $defaultLocale]));

        return $blogPost->load([
            'author',
            'translations' => fn ($query) => $query->whereIn('locale', $loadLocales),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function localizedPayload(BlogPost $blogPost, string $locale): array
    {
        return array_merge($blogPost->toArray(), [
            'title' => $blogPost->getTranslation('title', $locale),
            'content' => $blogPost->getTranslation('content', $locale),
            'excerpt' => $blogPost->getTranslation('excerpt', $locale),
        ]);
    }

    private function validateNextTypeAndScope(BlogPost $blogPost, array $validated): ?JsonResponse
    {
        $nextType = $validated['type'] ?? $blogPost->type;
        $nextScope = array_key_exists('content_scope', $validated) ? $validated['content_scope'] : $blogPost->content_scope;

        if ($nextType === 'guide' && empty($nextScope)) {
            return response()->json([
                'message' => 'The content_scope field is required when type is guide.',
                'errors' => ['content_scope' => ['The content_scope field is required when type is guide.']],
            ], 422);
        }

        if ($nextType !== 'guide' && ! empty($nextScope)) {
            return response()->json([
                'message' => 'The content_scope field is only allowed when type is guide.',
                'errors' => ['content_scope' => ['The content_scope field is only allowed when type is guide.']],
            ], 422);
        }

        return null;
    }
}
