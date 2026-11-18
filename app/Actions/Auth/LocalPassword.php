<?php

namespace App\Actions\Auth;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;

class LocalPassword
{
    /** @return array<int, string|Password> */
    public static function rules(): array
    {
        return ['required', 'string', 'confirmed', Password::min(12)];
    }

    public function replace(User $user, string $password): void
    {
        $user->forceFill([
            'password' => Hash::make($password),
            'local_credential_status' => 'enabled',
            'remember_token' => Str::random(60),
        ])->save();

        if (config('session.driver') === 'database') {
            DB::connection(config('session.connection'))->table(config('session.table', 'sessions'))
                ->where('user_id', $user->id)->delete();
        }
    }

    public function keepCurrentSession(Request $request, User $user): void
    {
        Auth::login($user);
        $request->session()->regenerate();
        $request->session()->put('password_hash_web', Auth::guard('web')->hashPasswordForCookie($user->password));
    }
}
