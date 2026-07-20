<?php

declare(strict_types=1);

namespace Kurt\Modules\Forum\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Kurt\Modules\Forum\Enums\BoardState;
use Kurt\Modules\Forum\Enums\Visibility;

final class UpdateBoardRequest extends FormRequest
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
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'parent_id' => ['nullable', 'integer', 'exists:forum_boards,id'],
            'position' => ['nullable', 'integer', 'min:0'],
            'state' => ['nullable', Rule::enum(BoardState::class)],
            'visibility' => ['nullable', Rule::enum(Visibility::class)],
        ];
    }
}
