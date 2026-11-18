<?php

namespace App\Actions\Admin;

use App\Models\AdminActivity;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Gate;

class ActivityLogger
{
    private const FIELDS = [
        'deposit.created' => ['user_id', 'value', 'status'],
        'deposit.updated' => ['user_id', 'value', 'status'],
        'deposit.deleted' => ['user_id', 'value', 'status'],
        'deposit.transferred' => ['source', 'target', 'value', 'deposit_ids'],
        'order_item.created' => ['order_id', 'user_id', 'meal_id', 'quantity'],
        'order_item.updated' => ['order_id', 'user_id', 'meal_id', 'quantity'],
        'order_item.deleted' => ['order_id', 'user_id', 'meal_id', 'quantity'],
        'order.updated' => ['status', 'payed_at', 'user_id'],
        'order.deleted' => ['status', 'payed_at', 'user_id'],
        'order.auto_ordered' => ['status'],
        'user.created' => ['is_admin'],
        'user.updated' => ['is_admin', 'deleted_at', 'name', 'email'],
        'user.deactivated' => ['is_admin', 'deleted_at'],
        'user.restored' => ['is_admin', 'deleted_at'],
        'meals.imported' => ['provider', 'date', 'count'],
        'meal.created' => ['title', 'description', 'price', 'date', 'provider', 'parent_id', 'can_be_ordered_until'],
        'notification.sent' => ['recipient_ids'],
    ];

    public static function authorize(User $actor): User
    {
        $actor = User::withTrashed()->findOrFail($actor->getKey());
        Gate::forUser($actor)->authorize('access-administration');

        return $actor;
    }

    /** @param array<string, mixed> $before
     * @param array<string, mixed> $after
     */
    public static function record(?User $actor, string $action, Model $target, array $before = [], array $after = []): AdminActivity
    {
        return AdminActivity::create([
            'actor_id' => $actor?->getKey(),
            'action' => $action,
            'target_type' => $target::class,
            'target_id' => $target->getKey(),
            'changes' => self::changes($action, $before, $after),
        ]);
    }

    /** @param array<string, mixed> $before
     * @param array<string, mixed> $after
     * @return array{before: array<string, mixed>, after: array<string, mixed>}
     */
    public static function changes(string $action, array $before, array $after): array
    {
        $fields = self::FIELDS[$action] ?? throw new \InvalidArgumentException('Unknown administrative action.');

        return ['before' => Arr::only($before, $fields), 'after' => Arr::only($after, $fields)];
    }
}
