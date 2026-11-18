<?php

namespace App\Actions\Admin;

use App\Models\AdminActivity;
use App\Models\Deposit;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;

class Bookings
{
    /** Values follow Deposit's existing convention: integers are cents, floats are euros.
     * @param array{user_id: int, value: int|float|string, comment?: ?string} $data
     */
    public function create(User $actor, array $data, ?string $key = null): Deposit
    {
        return DB::transaction(function () use ($actor, $data, $key): Deposit {
            $actor = $this->lockActor($actor);
            Gate::forUser($actor)->authorize('create', Deposit::class);
            $data = $this->validate($data);
            $hash = $this->hash($data);
            if ($replay = $this->replay($actor, 'deposit.created', $hash, $key)) {
                return Deposit::findOrFail($replay->target_id);
            }
            $deposit = Deposit::create($data);
            $activity = ActivityLogger::record($actor, 'deposit.created', $deposit, after: $deposit->getAttributes());
            $this->remember($activity, $actor, $hash, $key);

            return $deposit;
        });
    }

    /** @param array{user_id: int, value: int|float|string, comment?: ?string} $data */
    public function update(User $actor, Deposit $deposit, array $data): Deposit
    {
        return DB::transaction(function () use ($actor, $deposit, $data): Deposit {
            $actor = $this->lockActor($actor);
            $deposit = Deposit::whereKey($deposit->id)->lockForUpdate()->firstOrFail();
            Gate::forUser($actor)->authorize('update', $deposit);
            $before = $deposit->getAttributes();
            $deposit->update($this->validate($data));
            ActivityLogger::record($actor, 'deposit.updated', $deposit, $before, $deposit->getAttributes());

            return $deposit;
        });
    }

    public function delete(User $actor, Deposit $deposit): void
    {
        DB::transaction(function () use ($actor, $deposit): void {
            $actor = $this->lockActor($actor);
            $deposit = Deposit::whereKey($deposit->id)->lockForUpdate()->firstOrFail();
            Gate::forUser($actor)->authorize('delete', $deposit);
            $before = $deposit->getAttributes();
            $deposit->delete();
            ActivityLogger::record($actor, 'deposit.deleted', $deposit, $before);
        });
    }

    /** @param array{source: int, target: int, value: int|float|string, comment?: ?string} $data
     * @return array{0: Deposit, 1: Deposit}
     */
    public function transfer(User $actor, array $data, ?string $key = null): array
    {
        return DB::transaction(function () use ($actor, $data, $key): array {
            $actor = $this->lockActor($actor);
            $data = Validator::make($data, [
                'source' => 'required|exists:users,id', 'target' => 'required|exists:users,id',
                'value' => 'required|numeric', 'comment' => 'nullable|string',
            ])->validate();
            Gate::forUser($actor)->authorize('transfer', [Deposit::class,
                User::findOrFail($data['source']), User::findOrFail($data['target'])]);
            $hash = $this->hash($data);
            if ($replay = $this->replay($actor, 'deposit.transferred', $hash, $key)) {
                return array_map(fn (int $id): Deposit => Deposit::findOrFail($id), $replay->changes['after']['deposit_ids']);
            }
            $source = Deposit::create(['user_id' => $data['source'], 'value' => -1 * $data['value'], 'comment' => $data['comment'] ?? null]);
            $target = Deposit::create(['user_id' => $data['target'], 'value' => $data['value'], 'comment' => $data['comment'] ?? null]);
            $activity = ActivityLogger::record($actor, 'deposit.transferred', $source, after: [
                'source' => (int) $data['source'], 'target' => (int) $data['target'],
                'value' => $target->value, 'deposit_ids' => [$source->id, $target->id],
            ]);
            $this->remember($activity, $actor, $hash, $key);

            return [$source, $target];
        });
    }

    private function lockActor(User $actor): User
    {
        return ActivityLogger::authorize(User::withTrashed()->whereKey($actor->id)->lockForUpdate()->firstOrFail());
    }

    /** @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    private function validate(array $data): array
    {
        return Validator::make($data, [
            'user_id' => 'required', 'value' => 'required|numeric', 'comment' => 'nullable|string',
        ])->validate();
    }

    /** @param array<string, mixed> $data */
    private function hash(array $data): string
    {
        $value = new Deposit(['value' => $data['value']]);
        $data['value'] = (string) $value->value;
        foreach (['user_id', 'source', 'target'] as $field) {
            if (isset($data[$field])) {
                $data[$field] = (int) $data[$field];
            }
        }
        $data['comment'] = $data['comment'] ?? null;
        ksort($data);

        return hash('sha256', json_encode($data, JSON_THROW_ON_ERROR));
    }

    private function replay(User $actor, string $action, string $hash, ?string $key): ?AdminActivity
    {
        Validator::make(['key' => $key], ['key' => 'nullable|string|max:255'])->validate();
        $query = AdminActivity::where('actor_id', $actor->id)->where('action', $action);
        $activity = $key !== null
            ? $query->where('operation_key', $this->operationKey($actor, $action, $key))->first()
            : $query->where('request_hash', $hash)->where('created_at', '>=', now()->subMinutes(5))->latest('id')->first();
        abort_if($activity && $activity->request_hash !== $hash, 409, __('This booking key was already used for different data.'));

        return $activity;
    }

    private function remember(AdminActivity $activity, User $actor, string $hash, ?string $key): void
    {
        $activity->update([
            'operation_key' => $key === null ? null : $this->operationKey($actor, $activity->action, $key),
            'request_hash' => $hash,
        ]);
    }

    private function operationKey(User $actor, string $action, string $key): string
    {
        return hash('sha256', $actor->id.'|'.$action.'|'.$key);
    }
}
