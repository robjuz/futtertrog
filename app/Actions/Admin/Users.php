<?php

namespace App\Actions\Admin;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class Users
{
    /** Administrative role changes only; credentials and profile fields stay in their existing path. */
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
            $before = $user->getAttributes();
            $user->update(['is_admin' => $isAdmin]);
            ActivityLogger::record($actor, 'user.updated', $user, $before, $user->getAttributes());

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
