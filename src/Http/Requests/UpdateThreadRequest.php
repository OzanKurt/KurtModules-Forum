<?php

declare(strict_types=1);

namespace Kurt\Modules\Forum\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class UpdateThreadRequest extends FormRequest
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
            'title' => ['required', 'string', 'max:'.(int) config('forum.thread_max_title_length', 200)],
        ];
    }
}
