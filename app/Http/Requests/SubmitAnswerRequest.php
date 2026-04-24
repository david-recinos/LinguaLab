<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SubmitAnswerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'answer'     => ['required', 'string', 'max:500'],
            'time_spent' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
