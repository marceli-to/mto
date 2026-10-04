<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Validator;

class InvoiceSendRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'to' => ['required', 'string', $this->emailList()],
            'cc' => ['nullable', 'string', $this->emailList()],
            'subject' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string'],
        ];
    }

    public function messages()
    {
        return [
            'to.required' => 'A recipient is required!',
            'subject.required' => 'A subject is required!',
            'body.required' => 'A message is required!',
        ];
    }

    /**
     * Recipient fields take one or more comma separated addresses, so the
     * form stays a single input per field.
     */
    public function recipients(string $field): array
    {
        return collect(explode(',', (string) $this->input($field)))
            ->map(fn ($email) => trim($email))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    protected function emailList(): callable
    {
        return function (string $attribute, $value, callable $fail) {
            foreach ($this->recipients($attribute) as $email) {
                if (Validator::make(['email' => $email], ['email' => 'email'])->fails()) {
                    $fail("'{$email}' is not a valid email address!");
                }
            }
        };
    }
}
