<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ProjectStoreRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    /**
     * Normalize is_collection to a real boolean so required_if fires correctly
     * regardless of how the SPA serializes it.
     */
    protected function prepareForValidation()
    {
        if ($this->has('is_collection')) {
            $this->merge([
                'is_collection' => filter_var($this->input('is_collection'), FILTER_VALIDATE_BOOLEAN),
            ]);
        }
    }

    public function rules()
    {
        $isFlatRate = $this->input('is_collection') === false;

        return [
            'name' => 'required|string',
            'rate_id' => 'required',
            'client_id' => 'required',
            // Flat-rate projects (is_collection = false) require a budget > 0 (the fixed price).
            // Collection projects may leave the budget empty/0 (uncapped).
            'budget' => $isFlatRate
                ? 'required|numeric|gt:0'
                : 'nullable|numeric|min:0',
        ];
    }

    /**
     * Custom message for validation
     *
     * @return array
     */
    public function messages()
    {
        return [
            'name.required' => 'Name is required!',
            'rate_id.required' => 'Rate is required',
            'client_id.required' => 'Client is required!',
            'budget.required' => 'A budget is required for flat-rate (non-collection) projects.',
            'budget.gt' => 'A budget is required for flat-rate (non-collection) projects.',
        ];
    }
}
