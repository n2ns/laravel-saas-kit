<?php

namespace App\Http\Controllers\Api\Mcp;

use App\Http\Controllers\Controller;
use App\Models\BlogPost;
use App\Models\CatalogItem;
use App\Models\Product;
use App\Support\LocaleProfile;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class BlogPostController extends Controller
{
    /**
     * Describe the content publishing contract supported by this backend.
     */
    public function capabilities(): JsonResponse
    {
        $products = Product::query()
            ->withLocalizedTranslations($this->defaultLocale())
            ->whereIn('code', Product::SELLABLE_CODES)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get(['id', 'catalog_item_id', 'code']);

        return response()->json([
            'contract' => 'Content Publishing API Contract',
            'contract_version' => '1.0',
            'base_path' => '/api/v1/mcp',
            'auth' => [
                'required_headers' => ['X-API-KEY'],
            ],
            'endpoints' => [
                'capabilities' => 'GET /capabilities',
                'product_context' => 'GET /products/{content_scope}',
                'list_posts' => 'GET /posts',
                'create_post' => 'POST /posts',
                'get_post' => 'GET /posts/{id_or_slug}',
                'update_post' => 'PATCH /posts/{id_or_slug}',
                'publish_post' => 'POST /posts/{id_or_slug}/publish',
            ],
            'content' => [
                'input_model' => 'single_locale',
                'locale_field' => 'locale',
                'types' => ['technical', 'announcement', 'changelog', 'guide'],
                'statuses' => ['draft', 'published'],
                'locales' => $this->supportedLocales(),
                'default_locale' => $this->defaultLocale(),
                'recommended_locales' => $this->supportedLocales(),
                'localized_fields' => [
                    'title' => 'plain_text',
                    'excerpt' => 'plain_text',
                    'content' => 'markdown',
                ],
                'create_update_prohibited_fields' => ['status', 'published_at', 'user_id', 'author'],
                'content_scope' => [
                    'field' => 'content_scope',
                    'required_when' => ['type' => 'guide'],
                    'prohibited_unless' => ['type' => 'guide'],
                    'format' => 'kind:key',
                    'empty_filter' => 'company_blog',
                    'kinds' => ['product'],
                    'examples' => ['product:starter'],
                ],
            ],
            'translation' => [
                'backend_auto_translate' => false,
                'client_should_complete_missing_locales' => true,
                'missing_locales_returned' => true,
            ],
            'products' => $products
                ->map(fn (Product $product): array => [
                    'code' => $product->code,
                    'name' => $product->name,
                    'content_scope' => BlogPost::productContentScope($product->code),
                ])
                ->values()
                ->all(),
            'limits' => [
                'per_page_max' => 100,
                'default_status_on_create' => 'draft',
            ],
            'safety' => [
                'delete_exposed' => false,
                'database_access_exposed' => false,
                'shell_access_exposed' => false,
                'server_operations_exposed' => false,
            ],
        ]);
    }

    /**
     * Return a controlled product fact sheet for AI-assisted guide writing.
     */
    public function productContext(string $contentScope): JsonResponse
    {
        if (! str_starts_with($contentScope, 'product:')) {
            return response()->json([
                'message' => 'The content_scope must use product:key format.',
                'errors' => ['content_scope' => ['The content_scope must use product:key format.']],
            ], 422);
        }

        [, $productCode] = explode(':', $contentScope, 2);

        $product = Product::query()
            ->withLocalizedTranslations($this->defaultLocale())
            ->whereIn('code', Product::SELLABLE_CODES)
            ->where('code', $productCode)
            ->where('is_active', true)
            ->first();

        if (! $product) {
            return response()->json([
                'message' => 'The selected content_scope product does not exist.',
                'errors' => ['content_scope' => ['The selected content_scope product does not exist.']],
            ], 404);
        }

        $catalogItem = CatalogItem::query()
            ->withLocalizedTranslations($this->defaultLocale())
            ->where('code', $product->code)
            ->first();
        $defaultLocale = $this->defaultLocale();

        return response()->json([
            'content_scope' => BlogPost::productContentScope($product->code),
            'name' => $product->getLocalized('name', $defaultLocale) ?? $product->name,
            'canonical_url' => $this->publicWebUrl(localized_route('catalog.show', ['slug' => $product->code, 'locale' => $defaultLocale], false)),
            'docs_url' => $this->publicWebUrl(localized_route('catalog.guides.index', ['productCode' => $product->code, 'locale' => $defaultLocale], false)),
            'summary' => $this->productSummary($product, $catalogItem),
            'key_points' => $this->productKeyPoints($product, $catalogItem),
            'do_not_claim' => $this->productDoNotClaim($product),
        ]);
    }

    /**
     * List blog posts with optional filtering and search.
     */
    public function index(Request $request): JsonResponse
    {
        $locale = $this->normalizeLocale($request->string('locale')->toString());
        $query = BlogPost::with('author')->withLocalizedTranslations($locale)->latest();

        if ($request->has('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->has('type')) {
            $query->where('type', $request->input('type'));
        }

        if ($request->has('content_scope')) {
            $contentScope = $request->input('content_scope');
            $contentScope === null || $contentScope === ''
                ? $query->whereNull('content_scope')
                : $query->where('content_scope', $contentScope);
        }

        if ($request->has('q')) {
            $query->search($request->input('q'));
        }

        return response()->json(
            $this->transformPaginator($query->paginate($request->integer('per_page', 20)))
        );
    }

    /**
     * Create a new blog post.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'type' => ['nullable', 'string', Rule::in(['technical', 'announcement', 'changelog', 'guide'])],
            'content_scope' => ['nullable', 'string', 'required_if:type,guide', 'prohibited_unless:type,guide', $this->validContentScopeRule()],
            'status' => ['prohibited'],
            'slug' => ['required', 'string', 'unique:blog_posts,slug'],
            'locale' => ['nullable', 'string', Rule::in($this->supportedLocales())],
            'title' => ['required', 'string'],
            'content' => ['required', 'string'],
            'excerpt' => ['nullable', 'string'],
            'thumbnail' => ['nullable', 'string'],
            'published_at' => ['prohibited'],
        ]);

        $normalized = $this->normalizeInput($validated, requireTitle: true, requireContent: true);
        $requestedLocale = $this->normalizeLocale($normalized['locale'] ?? $this->defaultLocale());

        return DB::transaction(function () use ($normalized, $requestedLocale) {
            $blogPostData = [
                'user_id' => Auth::id(),
                'type' => $normalized['type'] ?? 'technical',
                'content_scope' => $normalized['content_scope'] ?? null,
                'status' => 'draft',
                'slug' => $normalized['slug'],
                'thumbnail' => $normalized['thumbnail'] ?? null,
                'title' => $this->primaryLocaleValue($normalized['title']),
                'content' => $this->primaryLocaleValue($normalized['content']),
                'excerpt' => $this->primaryLocaleValue($normalized['excerpt'] ?? []),
            ];

            $blogPost = BlogPost::create($blogPostData);

            $this->syncTranslations($blogPost, $normalized);
            $this->ensureLocalizedTranslationsLoaded($blogPost, $requestedLocale);

            return response()->json([
                'message' => 'Blog post created successfully',
                'blog_post' => $this->transformPost($blogPost),
                'available_locales' => $this->availableLocales($blogPost),
                'missing_locales' => $this->missingLocales($blogPost),
                'next_actions' => $this->nextActions($blogPost),
            ], 201);
        });
    }

    /**
     * Get blog post details.
     */
    public function show(Request $request, string $id): JsonResponse
    {
        // Support finding by ID or Slug
        $locale = $this->normalizeLocale($request->string('locale')->toString());
        $blogPost = $this->findPost($id, $locale);

        return response()->json($this->transformPost($blogPost));
    }

    /**
     * Update a blog post.
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $blogPost = $this->findPost($id, $this->defaultLocale());

        $validated = $request->validate([
            'type' => ['nullable', 'string', Rule::in(['technical', 'announcement', 'changelog', 'guide'])],
            'content_scope' => ['nullable', 'string', $this->validContentScopeRule()],
            'status' => ['prohibited'],
            'slug' => ['nullable', 'string', Rule::unique('blog_posts')->ignore($blogPost->id)],
            'locale' => ['nullable', 'string', Rule::in($this->supportedLocales())],
            'title' => ['nullable', 'string'],
            'content' => ['nullable', 'string'],
            'excerpt' => ['nullable', 'string'],
            'thumbnail' => ['nullable', 'string'],
            'published_at' => ['prohibited'],
        ]);

        $normalized = $this->normalizeInput($validated, requireTitle: false, requireContent: false);
        $requestedLocale = $this->normalizeLocale($normalized['locale'] ?? $this->defaultLocale());

        $validationError = $this->validateNextTypeAndScope($blogPost, $normalized);
        if ($validationError) {
            return $validationError;
        }

        $defaultLocale = $this->defaultLocale();

        return DB::transaction(function () use ($blogPost, $normalized, $requestedLocale, $defaultLocale) {
            $updateData = [];

            // Update simple fields
            foreach (['type', 'content_scope', 'slug', 'thumbnail'] as $field) {
                if (array_key_exists($field, $normalized)) {
                    $updateData[$field] = $normalized[$field];
                }
            }

            if (isset($normalized['title'][$defaultLocale])) {
                $updateData['title'] = $normalized['title'][$defaultLocale];
            }
            if (isset($normalized['content'][$defaultLocale])) {
                $updateData['content'] = $normalized['content'][$defaultLocale];
            }
            if (isset($normalized['excerpt'][$defaultLocale])) {
                $updateData['excerpt'] = $normalized['excerpt'][$defaultLocale];
            }

            if (! empty($updateData)) {
                $blogPost->update($updateData);
            }

            $this->syncTranslations($blogPost, $normalized);
            $this->ensureLocalizedTranslationsLoaded($blogPost, $requestedLocale);

            return response()->json([
                'message' => 'Blog post updated successfully',
                'blog_post' => $this->transformPost($blogPost),
                'available_locales' => $this->availableLocales($blogPost),
                'missing_locales' => $this->missingLocales($blogPost),
                'next_actions' => $this->nextActions($blogPost),
            ]);
        });
    }

    /**
     * Publish a blog post.
     */
    public function publish(string $id): JsonResponse
    {
        $blogPost = $this->findPost($id, $this->defaultLocale());

        $blogPost->update([
            'status' => 'published',
            'published_at' => $blogPost->published_at ?? now(),
        ]);

        $this->ensureLocalizedTranslationsLoaded($blogPost, $this->defaultLocale());

        return response()->json([
            'message' => 'Blog post published successfully',
            'blog_post' => $this->transformPost($blogPost),
            'available_locales' => $this->availableLocales($blogPost),
            'missing_locales' => $this->missingLocales($blogPost),
            'next_actions' => $this->nextActions($blogPost),
        ]);
    }

    /**
     * Helper to sync translations.
     */
    protected function syncTranslations(BlogPost $blogPost, array $data)
    {
        $locales = array_unique(array_merge(
            array_keys($data['title'] ?? []),
            array_keys($data['content'] ?? []),
            array_keys($data['excerpt'] ?? [])
        ));

        foreach ($locales as $locale) {
            $transData = [
                'title' => $data['title'][$locale] ?? $blogPost->getTranslation('title', $locale),
                'content' => $data['content'][$locale] ?? $blogPost->getTranslation('content', $locale),
                'excerpt' => $data['excerpt'][$locale] ?? $blogPost->getTranslation('excerpt', $locale),
            ];

            $blogPost->translations()->updateOrCreate(
                ['locale' => $locale],
                array_filter($transData, fn ($val) => ! is_null($val))
            );
        }
    }

    private function validContentScopeRule(): callable
    {
        return function (string $attribute, mixed $value, callable $fail): void {
            if ($value === null || $value === '') {
                return;
            }

            if (! is_string($value) || ! preg_match('/^[a-z][a-z0-9_-]*:[a-z0-9][a-z0-9_-]*$/', $value)) {
                $fail('The '.$attribute.' field must use the kind:key format.');

                return;
            }

            [$kind, $key] = explode(':', $value, 2);
            if ($kind === 'product' && ! Product::query()->whereIn('code', Product::SELLABLE_CODES)->where('code', $key)->exists()) {
                $fail('The selected '.$attribute.' product does not exist.');
            }
        };
    }

    /**
     * @param  array<string, mixed>  $validated
     */
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

    /**
     * @return array<string, mixed>
     */
    private function transformPost(BlogPost $blogPost): array
    {
        return array_merge($blogPost->toArray(), [
            'link' => $blogPost->publicUrl($this->defaultLocale()),
        ]);
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    private function normalizeInput(array $validated, bool $requireTitle, bool $requireContent): array
    {
        $locale = $validated['locale'] ?? $this->defaultLocale();
        $normalized = $validated;

        foreach (['title', 'excerpt', 'content'] as $field) {
            if (! array_key_exists($field, $validated)) {
                continue;
            }

            $value = $validated[$field];
            if (is_string($value)) {
                $normalized[$field] = [$locale => $value];
            } elseif ($value === null && $field === 'excerpt') {
                $normalized[$field] = [];
            } else {
                throw ValidationException::withMessages([
                    $field => ["The {$field} field must be a string."],
                ]);
            }
        }

        if ($requireTitle && empty($normalized['title'])) {
            abort(response()->json([
                'message' => 'The title field is required.',
                'errors' => ['title' => ['The title field is required.']],
            ], 422));
        }

        if ($requireContent && empty($normalized['content'])) {
            abort(response()->json([
                'message' => 'The content field is required.',
                'errors' => ['content' => ['The content field is required.']],
            ], 422));
        }

        return $normalized;
    }

    private function normalizeLocale(?string $locale): string
    {
        return LocaleProfile::normalize($locale);
    }

    private function publicWebUrl(string $path): string
    {
        return rtrim((string) config('app.url'), '/').'/'.ltrim($path, '/');
    }

    private function findPost(string $id, string $locale): BlogPost
    {
        $query = BlogPost::query()
            ->with('author')
            ->withLocalizedTranslations($locale);

        return is_numeric($id)
            ? $query->findOrFail((int) $id)
            : $query->where('slug', $id)->firstOrFail();
    }

    private function ensureLocalizedTranslationsLoaded(BlogPost $blogPost, string $locale): void
    {
        $locale = $this->normalizeLocale($locale);
        $defaultLocale = $this->defaultLocale();
        $loadLocales = array_values(array_unique([$locale, $defaultLocale]));

        $blogPost->load([
            'author',
            'translations' => fn ($query) => $query->whereIn('locale', $loadLocales),
        ]);
    }

    /**
     * @param  array<string, string>  $values
     */
    private function primaryLocaleValue(array $values): ?string
    {
        $defaultLocale = $this->defaultLocale();
        if (isset($values[$defaultLocale])) {
            return $values[$defaultLocale];
        }

        $firstKey = array_key_first($values);

        return $firstKey !== null ? $values[$firstKey] : null;
    }

    /**
     * @return array<int, string>
     */
    private function availableLocales(BlogPost $blogPost): array
    {
        $supportedLocales = $this->supportedLocales();
        $defaultLocale = $this->defaultLocale();
        $available = $blogPost->translations()->whereIn('locale', $supportedLocales)->pluck('locale');
        if ($this->hasMainLocaleContent($blogPost)) {
            $available = $available->concat([$defaultLocale])->unique();
        }

        return collect($supportedLocales)
            ->filter(fn (string $locale): bool => $available->contains($locale))
            ->values()
            ->all();
    }

    /**
     * @return array<int, string>
     */
    private function missingLocales(BlogPost $blogPost): array
    {
        return array_values(array_diff($this->supportedLocales(), $this->availableLocales($blogPost)));
    }

    private function defaultLocale(): string
    {
        return LocaleProfile::default();
    }

    /**
     * @return array<int, string>
     */
    private function supportedLocales(): array
    {
        return LocaleProfile::supported();
    }

    private function hasMainLocaleContent(BlogPost $blogPost): bool
    {
        return ! empty($blogPost->title) || ! empty($blogPost->content) || ! empty($blogPost->excerpt);
    }

    /**
     * @return array<int, string>
     */
    private function nextActions(BlogPost $blogPost): array
    {
        $missingLocales = $this->missingLocales($blogPost);
        if ($missingLocales === []) {
            return ['All recommended locales are present. Review the draft, then publish when ready.'];
        }

        return [
            'Update the draft to add missing locale versions: '.implode(', ', $missingLocales).'.',
            'Submit one locale per tool call using locale, title, excerpt, and Markdown content.',
            'Publishing is separate; publish only after reviewing the article.',
        ];
    }

    private function productSummary(Product $product, ?CatalogItem $catalogItem): string
    {
        return $catalogItem?->getLocalized('short_description', $this->defaultLocale())
            ?? $catalogItem?->getLocalized('long_description', $this->defaultLocale())
            ?? $product->getLocalized('subtitle', $this->defaultLocale())
            ?? $product->subtitle
            ?? $product->getLocalized('name', $this->defaultLocale())
            ?? $product->name;
    }

    /**
     * @return array<int, string>
     */
    private function productKeyPoints(Product $product, ?CatalogItem $catalogItem): array
    {
        $catalogPoints = $this->normalizeKeyPoints($catalogItem?->getLocalized('key_points', $this->defaultLocale()));
        if ($catalogPoints !== []) {
            return $catalogPoints;
        }

        $productMetadata = $this->arrayValue($product->getAttribute('metadata'));
        $catalogFacts = $this->arrayValue($catalogItem?->facts);
        $metadataPoints = $productMetadata['key_points'] ?? data_get($catalogFacts, 'metadata.key_points');
        if (is_array($metadataPoints) && ! empty($metadataPoints)) {
            return array_values(array_filter($metadataPoints, fn ($point) => is_string($point) && $point !== ''));
        }

        $sectionsContent = $product->getSectionsContent($this->defaultLocale());
        $features = $sectionsContent['features'] ?? [];

        $points = collect(is_array($features) ? $features : [])
            ->map(function (mixed $feature): ?string {
                if (is_string($feature)) {
                    return $feature;
                }

                if (! is_array($feature)) {
                    return null;
                }

                $title = $feature['title'] ?? $feature['name'] ?? null;
                $description = $feature['description'] ?? $feature['text'] ?? null;

                if (is_string($title) && is_string($description) && $description !== '') {
                    return $title.': '.$description;
                }

                return is_string($title) ? $title : null;
            })
            ->filter()
            ->take(6)
            ->values()
            ->all();

        return $points === [] ? [$this->productSummary($product, $catalogItem)] : $points;
    }

    /**
     * @return array<int, string>
     */
    private function normalizeKeyPoints(mixed $points): array
    {
        if (! is_array($points)) {
            return [];
        }

        return collect($points)
            ->map(function (mixed $point): ?string {
                if (is_string($point)) {
                    return $point;
                }

                if (! is_array($point)) {
                    return null;
                }

                $title = $point['title'] ?? null;
                $description = $point['description'] ?? $point['desc'] ?? null;

                if (is_string($title) && is_string($description) && $description !== '') {
                    return $title.': '.$description;
                }

                return is_string($title) ? $title : null;
            })
            ->filter(fn (?string $point): bool => $point !== null && $point !== '')
            ->values()
            ->all();
    }

    /**
     * @return array<int, string>
     */
    private function productDoNotClaim(Product $product): array
    {
        $metadata = $this->arrayValue($product->getAttribute('metadata'));
        $configured = $metadata['do_not_claim'] ?? null;
        if (is_array($configured) && ! empty($configured)) {
            return array_values(array_filter($configured, fn ($claim) => is_string($claim) && $claim !== ''));
        }

        return [
            'Do not claim government, platform, vendor, or browser-store affiliation unless the product page explicitly states it.',
            'Do not claim guaranteed approval, guaranteed outcomes, or fully automated completion.',
            'Do not claim support for countries, platforms, browsers, IDEs, or workflows not listed in the product context or product page.',
            'Do not invent pricing, availability, compliance, security, or data-retention guarantees.',
        ];
    }

    /**
     * @param  LengthAwarePaginator<int, BlogPost>  $paginator
     * @return array<string, mixed>
     */
    private function transformPaginator(LengthAwarePaginator $paginator): array
    {
        return array_merge($paginator->toArray(), [
            'data' => collect($paginator->items())
                ->map(fn (BlogPost $blogPost): array => $this->transformPost($blogPost))
                ->values()
                ->all(),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function arrayValue(mixed $value): array
    {
        return is_array($value) ? $value : [];
    }
}
