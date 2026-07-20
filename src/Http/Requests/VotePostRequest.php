<?php

declare(strict_types=1);

namespace Kurt\Modules\Forum\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Kurt\Modules\Forum\Enums\VoteValue;

final class VotePostRequest extends FormRequest
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
            'value' => ['required', 'string', 'in:up,down'],
        ];
    }

    /**
     * Resolve the validated `value` to its VoteValue.
     */
    public function voteValue(): VoteValue
    {
        return $this->string('value')->value() === 'up'
            ? VoteValue::Up
            : VoteValue::Down;
    }
}
