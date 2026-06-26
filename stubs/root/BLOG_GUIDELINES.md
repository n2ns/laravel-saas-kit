# Blog Content Workflow

The starter blog is a site-level publishing workflow. Blog posts serve readers, SEO, and internal linking. They are not product-owned content records.

## Source Files

Seeded posts live in:

```text
database/seeders/data/posts/{slug}/meta.json
database/seeders/data/posts/{slug}/{locale}.md
```

`meta.json` stores routing and taxonomy fields:

- `slug`: public URL slug and directory name.
- `type`: one code from `_meta_schema.json`.
- `status`: `draft` or `published`.
- `published_at`: ISO 8601 date-time for published posts.
- `topics`: editorial topic codes from `_meta_schema.json`.
- `geo_tags`: optional ISO country codes for regional relevance.
- `seo_keywords`: optional meta/schema keywords.
- `related_slugs`: optional curated related article slugs.
- `is_pinned`, `pin_order`, `pinned_until`: homepage featured ordering only.

Markdown files store localized `title`, `excerpt`, and body content through front matter:

```markdown
---
title: "Article title"
excerpt: "Short search and listing summary."
---

Article body.
```

## Taxonomy

The content vocabulary is configured in `database/seeders/data/posts/_meta_schema.json`.

Update the schema when a product site needs different article types or topics. Do not hardcode new taxonomy codes in controllers, views, or seeders.

## Listing Order

`/blog` uses normal listing order:

- newest first by default;
- oldest first when `sort=oldest`;
- optional search, type, and topic filters.

Pinned posts do not get priority on `/blog`. This keeps the blog archive predictable.

## Homepage Featured Posts

Pinned posts are a marketing-site homepage feature. The homepage query applies pinned ordering before date ordering:

1. active pinned posts first;
2. lower `pin_order` first;
3. newest published posts next;
4. expired pins fall back to normal date order.

Use `is_pinned` only when an article should be promoted on the homepage.

## Related Posts

Article pages use `BlogPost::relatedPosts()`:

1. explicit `related_slugs`, preserving author order;
2. matching `geo_tags`;
3. matching `topics`;
4. same `type`, newest first.

This creates useful internal links without adding product-to-article ownership.

## Seeder Diagnostics

`BlogPostSeeder` warns about:

- malformed `meta.json`;
- non-object JSON;
- slug and directory mismatch;
- unknown type, status, topic, or geo tag;
- ignored or missing `related_slugs`.

Warnings should be fixed before using the seeded content as launch content.
