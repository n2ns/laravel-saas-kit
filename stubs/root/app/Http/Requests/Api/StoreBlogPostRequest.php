<?php

namespace App\Http\Requests\Api;

use App\Models\BlogPost;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreBlogPostRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'type' => ['nullable', 'string', Rule::in(BlogPost::typeCodes())],
            'geo_tags' => ['nullable', 'array'],
            'geo_tags.*' => ['string', 'size:2'],
            'topics' => ['nullable', 'array'],
            'topics.*' => ['string', Rule::in(BlogPost::topicCodes())],
            'seo_keywords' => ['nullable', 'array'],
            'seo_keywords.*' => ['string', 'max:80'],
            'related_slugs' => ['nullable', 'array'],
            'related_slugs.*' => ['string', 'max:180'],
            'status' => ['nullable', 'string', Rule::in(['draft', 'published'])],
            'is_pinned' => ['nullable', 'boolean'],
            'pin_order' => ['nullable', 'integer', 'min:0'],
            'pinned_until' => ['nullable', 'date'],
            'slug' => ['required', 'string', 'unique:blog_posts,slug'],
            'title' => ['required', 'array'], // Expecting ['en' => '...', 'zh_CN' => '...']
            'content' => ['required', 'array'],
            'excerpt' => ['nullable', 'array'],
            'thumbnail' => ['nullable', 'string'],
            'published_at' => ['nullable', 'date'],
        ];
    }
}
