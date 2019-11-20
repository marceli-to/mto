<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TimeStoreRequest extends FormRequest
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
  public function rules()
  {
    return [
      'task' => 'required|string',
      'project_id' => 'required',
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
      'task.required' => 'Name is required!',
      'project_id.required' => 'Project Id is required',
    ];
  }
}
