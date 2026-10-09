<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LiquiditySnapshotStoreRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'name'                 => 'required|string|max:255',
            'data'                 => 'required|array',
            'data.balance'         => 'nullable|numeric',
            'data.balance_date'    => 'nullable|date',
            'data.items'           => 'array',
            'data.items.*.label'   => 'required|string|max:255',
            'data.items.*.amount'  => 'required|numeric', // signed: positive in, negative out
            'data.items.*.date'    => 'nullable|date',
            'data.invoice_ids'     => 'array',
            'data.invoice_ids.*'   => 'integer',
            'data.project_ids'     => 'array',
            'data.project_ids.*'   => 'integer',
        ];
    }

    public function messages()
    {
        return [
            'name.required'               => 'Enter a name for this state.',
            'data.items.*.label.required' => 'Every item needs a label.',
            'data.items.*.amount.required' => 'Every item needs an amount.',
        ];
    }

    /** The document as stored — only known keys, normalised types. */
    public function snapshotData(): array
    {
        $data = $this->validated()['data'];

        return [
            'balance'      => (float) ($data['balance'] ?? 0),
            'balance_date' => $data['balance_date'] ?? null,
            'items'        => array_values(array_map(fn ($item) => [
                'label'  => $item['label'],
                'amount' => round((float) $item['amount'], 2),
                'date'   => $item['date'] ?? null,
            ], $data['items'] ?? [])),
            'invoice_ids'  => array_values(array_unique(array_map('intval', $data['invoice_ids'] ?? []))),
            'project_ids'  => array_values(array_unique(array_map('intval', $data['project_ids'] ?? []))),
        ];
    }
}
