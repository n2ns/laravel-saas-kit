# Blog Content Workflow

The blog is a site-level publishing workflow. Blog posts serve readers, SEO,
and internal linking; they are not owned by catalog items or products.

Posts are stored in `blog_posts` (default-locale `title`, `excerpt`, `content`
and all metadata) and `blog_post_translations` (`title`, `excerpt`, `content`
per non-default locale). The default locale is `APP_FALLBACK_LOCALE` (`en`).
Post bodies are Markdown.

Posts can be created three ways:

- Seed files in `database/seeders/data/posts`, imported by `BlogPostSeeder`.
- The Filament admin (**Content > Blog** and **Content > Post translations**).
- The API-key-protected API at `/api/v1/blog`.

## Seed Files

```text
database/seeders/data/posts/_meta_schema.json
database/seeders/data/posts/{slug}/meta.json
database/seeders/data/posts/{slug}/{locale}.md
```

Import them with:

```bash
php artisan db:seed                        # DatabaseSeeder: ReferenceDataSeeder + BlogPostSeeder
php artisan db:seed --class=BlogPostSeeder
```

`BlogPostSeeder` upserts posts by `slug`, so re-running it updates existing
posts. Seeded posts are attributed to a `content@example.com` "Content Team"
user, which the seeder creates if needed.

### `meta.json`

`_meta_schema.json` is the JSON Schema for `meta.json`. `slug`, `type`, and
`status` are required and no other properties are allowed.

| Field | Meaning |
|---|---|
| `slug` | Public URL slug (`/blog/{slug}`); lowercase words separated by hyphens. Must match the directory name. |
| `type` | One type code from the content vocabulary. |
| `status` | `draft` or `published`. |
| `published_at` | ISO 8601 date-time. A new published post without it gets the current time. |
| `topics` | Topic codes from the content vocabulary. Unknown topics are dropped. |
| `geo_tags` | Optional two-letter uppercase country or region codes. |
| `seo_keywords` | Optional keywords for meta and structured data (1 to 8 in the schema). |
| `related_slugs` | Optional curated related post slugs, in display order. |
| `is_pinned`, `pin_order`, `pinned_until` | Homepage featured ordering only. |
| `thumbnail` | Optional cover image path on the `public` disk. |

### `{locale}.md`

Each Markdown file holds one locale's title, excerpt, and body. The file name
is the locale ID (`en`, `es`, `de`, or `zh_CN`). The default-locale file fills
`blog_posts`; other locales go to `blog_post_translations`.

```markdown
---
title: "Article title"
excerpt: "Short search and listing summary."
---

Article body in Markdown.
```

The front matter parser reads simple `key: value` lines and strips surrounding
quotes. Only `title` and `excerpt` are used.

## Content Vocabulary

Post types and topics are defined in `_meta_schema.json` and read by
`App\Support\BlogContentVocabulary`:

1. `x-content-vocabulary.types` and `x-content-vocabulary.topics` provide the
   codes, per-locale `labels`, and the default type (`"default": true`).
2. If that block is missing, the codes come from `properties.type.enum` and
   `properties.topics.items.enum`.
3. If both are missing, built-in defaults are used.

Starter types: `article` (default), `guide`, `announcement`, `changelog`.
Starter topics: `getting-started`, `product`, `tutorial`, `release`,
`company`.

Labels fall back to the default locale's label, then to a headline-cased code.
The starter schema has `en` and `zh_CN` labels.

To change the vocabulary, update both `x-content-vocabulary` and the matching
`enum` lists in `_meta_schema.json`. The admin form, public filters, API
validation, and seeder diagnostics all read the same vocabulary. Do not
hardcode taxonomy codes in controllers, views, or seeders.

## Public Pages

### `/blog`

- Lists published posts (`status = published` and `published_at` not in the
  future), 10 per page.
- Newest first by default; oldest first with `sort=oldest`.
- Optional filters: `q` (search, truncated to 120 characters), `type`, and
  `topic`. Unknown `type` or `topic` values are ignored.
- Search matches title, excerpt, and content in the main table and
  translations with `LIKE`, plus fulltext indexes on MySQL/MariaDB.

Pinned posts get no priority on `/blog`, which keeps the archive predictable.

### `/blog/{slug}`

- A locale renders when that locale has both a title and content. If the
  requested locale has none, the default locale is shown; if neither has
  content, the page returns 404.
- The canonical URL points to the locale actually rendered, and `hreflang`
  alternates list only locales with content.
- The page includes `BlogPosting` JSON-LD, reading time, and up to four related
  posts.

Admins can preview any post, including drafts, at
`/{ADMIN_PATH}/blog-posts/{id}/preview?locale={locale}`.

## Homepage Featured Posts

The homepage shows three published posts using pinned ordering:

1. Active pins first (`is_pinned` is true and `pinned_until` is empty or in the
   future).
2. Lower `pin_order` first among active pins.
3. Then newest `published_at`.

Expired pins fall back to normal date order. On a non-default locale, the
homepage only shows posts that have a translation row for that locale.

Use `is_pinned` only when an article should be promoted on the homepage.

## Related Posts

Article pages use `BlogPost::relatedPosts()` (default limit 4), filling the
list from published posts in this order:

1. Explicit `related_slugs`, preserving their order.
2. Posts sharing any `geo_tags`.
3. Posts sharing any `topics`.
4. Posts of the same `type`.

Within steps 2 to 4, newer posts come first. This creates internal links
without product-to-article ownership.

## Sitemap

`php artisan sitemap:generate` adds every published post, with one URL per
locale that has a title and content, `hreflang` alternates for those locales,
and an `x-default` entry.

## Admin

- **Content > Blog**: create and edit default-locale content with a Markdown
  editor, set type, status, publish date, pinning, topics, region codes, SEO
  keywords, related slugs, and a cover image (stored on the `public` disk under
  `blog-post-thumbnails`). The list shows total and 7-day views and offers
  Publish and Preview actions.
- **Content > Post translations**: edit per-locale title, excerpt, and content.

Deleting a post also deletes its cover image from the `public` disk.

## Blog API

Routes under `/api/v1/blog` require an `X-Api-Key` header (see
[API_AUTH.md](API_AUTH.md)):

| Method | Path | Purpose |
|---|---|---|
| `GET` | `/api/v1/blog` | Paginated posts (20 per page, all statuses); optional `locale`. |
| `POST` | `/api/v1/blog` | Create a post. |
| `GET` | `/api/v1/blog/{slug}` | One post localized by `?locale=`. |
| `PUT` | `/api/v1/blog/{slug}` | Update a post. |
| `DELETE` | `/api/v1/blog/{slug}` | Delete a post. |

`title`, `content`, and `excerpt` are objects keyed by locale ID:

```json
{
  "slug": "launch-notes",
  "type": "article",
  "status": "published",
  "topics": ["release"],
  "geo_tags": ["US"],
  "seo_keywords": ["launch"],
  "related_slugs": ["starter-launch-guide"],
  "title": { "en": "Launch notes", "zh_CN": "发布说明" },
  "content": { "en": "Markdown body", "zh_CN": "正文" },
  "excerpt": { "en": "Short summary" }
}
```

On create, `slug`, `title`, and `content` are required. `type` and `topics`
must be vocabulary codes, `geo_tags` items must be two characters, and
`status` defaults to `draft`. Setting `status` to `published` on a post with
no `published_at` sets it to the current time. On update, only the fields sent
are changed. The API key's owner becomes the author of created posts.

## Seeder Diagnostics

`BlogPostSeeder` warns about:

- malformed `meta.json` and JSON that is not an object (the post is skipped);
- a missing `slug` (the post is skipped);
- a slug that does not match its directory name;
- unknown `type`, `status`, or topic values, and geo tags that are not two
  uppercase letters;
- ignored `related_slugs` that are not strings, point to the post itself, or
  do not match an existing post.

Fix warnings before using the seeded content as launch content.

`php artisan translations:check [--locale=]` reports which posts are missing
translations for each supported locale.
