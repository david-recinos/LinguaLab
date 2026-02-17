<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTranslationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'target_language_id' => 'required|exists:languages,id',
            'type' => 'required|in:word,text,expression',
            'word_type_id' => 'nullable|required_if:type,word|exists:word_types,id',
            'source_text' => 'required|string',
            'target_text' => 'required|string',
            'example_sentence' => 'nullable|string',
            'notes' => 'nullable|string',
            'pronunciation' => 'nullable|string|max:500',
        ];
    }
}
