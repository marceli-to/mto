<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class TimeEntryStoreRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    /**
     * Activity entries carry no project, no rate, and are never billable.
     * Normalize before validation so the rules and the stored row stay consistent.
     */
    protected function prepareForValidation()
    {
        if ($this->filled('activity')) {
            $this->merge([
                'project_id'  => null,
                'rate'        => null,
                'is_billable' => false,
            ]);
        }
    }

    public function rules()
    {
        return [
            'project_id'  => 'nullable|exists:projects,id|required_without:activity',
            'activity'    => 'nullable|string|required_without:project_id|in:' . implode(',', config('timetracking.activities')),
            'is_billable' => 'boolean',
            'date'        => 'required|date',
            'hours'       => 'required|numeric|min:0.01',
            'description' => 'nullable|string',
            'rate'        => 'nullable|numeric|min:0',
        ];
    }

    public function withValidator(Validator $validator)
    {
        $validator->after(function (Validator $validator) {
            // Exactly one of project_id / activity.
            if ($this->filled('project_id') && $this->filled('activity')) {
                $validator->errors()->add('activity', 'An entry cannot have both a project and an activity.');
            }
        });
    }

    public function messages()
    {
        return [
            'project_id.required_without' => 'Select a project or an activity.',
            'activity.required_without'   => 'Select a project or an activity.',
            'activity.in'                 => 'Unknown activity.',
        ];
    }
}
