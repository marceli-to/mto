<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class QuoteBlockStoreRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'title' => 'required|string|max:255',
            'content' => 'nullable|string',
            'is_default' => 'boolean',
        ];
    }

    public function messages()
    {
        return [
            'title.required' => 'Title is required',
        ];
    }
}
