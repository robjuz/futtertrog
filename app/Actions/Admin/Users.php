<?php

namespace App\Actions\Admin;

use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class Users
{
    /** @param array{name: string, email: string, is_admin?: bool} $data */
    public function create(User $actor, array $data): User
    {
        return DB::transaction(function () use ($actor, $data): User {
            $actor = ActivityLogger::authorize($actor);
            Gate::forUser($actor)->authorize('create', User::class);
            $data = Validator::make($data, [
                'name' => ['required', 'string', 'max:255'],
                'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users')],
                'is_admin' => ['sometimes', 'boolean'],
                'password' => ['missing'], 'password_confirmation' => ['missing'],
            ])->validate();
            $user = User::create([
                'name' => $data['name'], 'email' => $data['email'], 'is_admin' => $data['is_admin'] ?? false,
                'password' => Hash::make(bin2hex(random_bytes(32))), 'local_credential_status' => 'disabled',
            ]);
            ActivityLogger::record($actor, 'user.created', $user, after: ['is_admin' => $user->is_admin]);

            return $user;
        });
    }

    /** Administrative identity maintenance; roles and credentials use their dedicated actions.
     * @param array{name?: string, email?: string} $data
     */
    public function update(User $actor, User $user, array $data): User
    {
        return DB::transaction(function () use ($actor, $user, $data): User {
            $actor = ActivityLogger::authorize($actor);
            $user = User::withTrashed()->whereKey($user->id)->lockForUpdate()->firstOrFail();
            Gate::forUser($actor)->authorize('update', $user);
            $data = Validator::make($data, [
                'name' => ['sometimes', 'required', 'string', 'max:255'],
                'email' => ['sometimes', 'required', 'string', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
                'is_admin' => ['missing'], 'password' => ['missing'], 'password_confirmation' => ['missing'],
            ])->validate();
            $before = $user->only(['name', 'email']);
            $user->update(Arr::only($data, ['name', 'email']));
            ActivityLogger::record($actor, 'user.updated', $user, $before, $user->only(['name', 'email']));

            return $user;
        });
    }

    public function changeRole(User $actor, User $user, bool $isAdmin): User
    {
        return DB::transaction(function () use ($actor, $user, $isAdmin): User {
            $this->lockAdministrators();
            $actor = ActivityLogger::authorize($actor);
            $user = User::withTrashed()->whereKey($user->id)->lockForUpdate()->firstOrFail();
            Gate::forUser($actor)->authorize('update', $user);
            if (!$isAdmin) {
                Gate::forUser($actor)->authorize('demote', $user);
            }
            $before = $user->only(['is_admin', 'deleted_at']);
            $user->update(['is_admin' => $isAdmin]);
            ActivityLogger::record($actor, 'user.updated', $user, $before, $user->only(['is_admin', 'deleted_at']));

            return $user;
        });
    }

    public function deactivate(User $actor, User $user): void
    {
        DB::transaction(function () use ($actor, $user): void {
            $this->lockAdministrators();
            $actor = ActivityLogger::authorize($actor);
            $user = User::withTrashed()->whereKey($user->id)->lockForUpdate()->firstOrFail();
            Gate::forUser($actor)->authorize('delete', $user);
            $before = $user->getAttributes();
            $user->delete();
            ActivityLogger::record($actor, 'user.deactivated', $user, $before, $user->getAttributes());
        });
    }

    public function restore(User $actor, User $user): User
    {
        return DB::transaction(function () use ($actor, $user): User {
            $this->lockAdministrators();
            $actor = ActivityLogger::authorize($actor);
            $user = User::withTrashed()->whereKey($user->id)->lockForUpdate()->firstOrFail();
            Gate::forUser($actor)->authorize('restore', $user);
            $before = $user->getAttributes();
            $user->restore();
            ActivityLogger::record($actor, 'user.restored', $user, $before, $user->getAttributes());

            return $user;
        });
    }

    private function lockAdministrators(): void
    {
        User::where('is_admin', true)->orderBy('id')->lockForUpdate()->get();
    }
}
