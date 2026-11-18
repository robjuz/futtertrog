<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ProfileUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['missing'], 'id' => ['missing'], 'user_id' => ['missing'],
            'is_admin' => ['missing'], 'roles' => ['missing'], 'local_credential_status' => ['missing'],
        ];
    }
}
