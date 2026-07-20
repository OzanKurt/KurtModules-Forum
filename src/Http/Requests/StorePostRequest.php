<?php

declare(strict_types=1);

namespace Kurt\Modules\Forum\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class StorePostRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'body' => ['required', 'string', 'max:'.(int) config('forum.post_max_body_length', 30000)],
            'parent_id' => ['nullable', 'integer', 'exists:forum_posts,id'],
        ];
    }
}
