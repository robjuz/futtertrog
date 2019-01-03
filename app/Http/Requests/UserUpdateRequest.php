<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UserUpdateRequest extends FormRequest
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
        $rules =  [
            'name' => ['sometimes', 'string', 'max:255'],
            'email' => ['sometimes', 'string', 'email', Rule::unique('users')->ignoreModel($this->user)],
            'is_admin' => ['sometimes', 'boolean'],

        ];

        if (!is_null($this->password)) {
           $rules['password'] = ['sometimes', 'min:6', 'confirmed'];
        }

        return $rules;
    }
}
