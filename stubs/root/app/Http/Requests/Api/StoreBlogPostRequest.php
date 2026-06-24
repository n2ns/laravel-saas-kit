<?php

namespace App\Http\Requests\Api;

use App\Models\Product;
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
            'type' => ['nullable', 'string', Rule::in(['technical', 'announcement', 'changelog', 'guide'])],
            'content_scope' => ['nullable', 'string', 'required_if:type,guide', 'prohibited_unless:type,guide', $this->validContentScopeRule()],
            'status' => ['nullable', 'string', Rule::in(['draft', 'published'])],
            'slug' => ['required', 'string', 'unique:blog_posts,slug'],
            'title' => ['required', 'array'], // Expecting ['en' => '...', 'zh_CN' => '...']
            'content' => ['required', 'array'],
            'excerpt' => ['nullable', 'array'],
            'thumbnail' => ['nullable', 'string'],
            'published_at' => ['nullable', 'date'],
        ];
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
            if ($kind === 'product' && ! Product::query()->where('code', $key)->exists()) {
                $fail('The selected '.$attribute.' product does not exist.');
            }
        };
    }
}
