<?php

namespace App\Http\Requests;

use App\Actions\Auth\LocalPassword;
use Illuminate\Foundation\Http\FormRequest;

class ProfilePasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->local_credential_status === 'enabled';
    }

    public function rules(): array
    {
        return [
            'current_password' => ['required', 'string', 'current_password:web'],
            'password' => LocalPassword::rules(),
            'id' => ['missing'], 'user_id' => ['missing'], 'is_admin' => ['missing'], 'roles' => ['missing'],
        ];
    }
}
