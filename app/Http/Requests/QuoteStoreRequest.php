<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class QuoteStoreRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'label' => 'required|string|max:50',
            'title' => 'required|string',
            'date' => 'required',
            'client_id' => 'required',
        ];
    }

    public function messages()
    {
        return [
            'label.required' => 'Label is required',
            'title.required' => 'Title is required',
            'date.required' => 'Date is required',
            'client_id.required' => 'Client is required',
        ];
    }
}
