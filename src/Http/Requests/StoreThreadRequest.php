<?php

declare(strict_types=1);

namespace Kurt\Modules\Forum\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class StoreThreadRequest extends FormRequest
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
            'board_id' => ['required', 'integer', 'exists:forum_boards,id'],
            'title' => ['required', 'string', 'max:'.(int) config('forum.thread_max_title_length', 200)],
            'body' => ['required', 'string', 'max:'.(int) config('forum.post_max_body_length', 30000)],
        ];
    }
}
