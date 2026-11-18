<?php

namespace App\Actions\Admin;

use App\Events\OrderUpdated;
use App\Models\Meal;
use App\Models\OrderItem;
use App\Models\User;
use App\Rules\MealWithoutVariants;
use App\Rules\MealInOrderGroup;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;

class OrderItems
{
    /** @param array{meal_id: int, user_id?: int, quantity?: int} $data */
    public function create(User $actor, array $data): OrderItem
    {
        return DB::transaction(function () use ($actor, $data): OrderItem {
            $actor = ActivityLogger::authorize($actor);
            Gate::forUser($actor)->authorize('administerAny', OrderItem::class);
            $data = Validator::make($data, [
                'user_id' => ['sometimes', 'required', 'exists:users,id'],
                'quantity' => ['sometimes', 'integer', 'min:1', 'max:10'],
                'meal_id' => ['required', 'exists:meals,id', new MealWithoutVariants()],
            ])->validate();
            $meal = Meal::findOrFail($data['meal_id']);
            $recipient = User::findOrFail($data['user_id'] ?? $actor->id);
            Gate::forUser($actor)->authorize('orderFor', [OrderItem::class, $meal, $recipient]);
            $item = $meal->order($recipient, $data['quantity'] ?? 1);
            ActivityLogger::record(
                $actor, $item->wasRecentlyCreated ? 'order_item.created' : 'order_item.updated', $item,
                $item->wasRecentlyCreated ? [] : array_replace($item->getAttributes(), $item->getPrevious()),
                $item->getAttributes(),
            );
            event(new OrderUpdated($item->order, $item->user, $item));

            return $item;
        });
    }

    /** @param array{meal_id?: int, user_id?: int, quantity?: int} $data */
    public function update(User $actor, OrderItem $item, array $data): OrderItem
    {
        return DB::transaction(function () use ($actor, $item, $data): OrderItem {
            $actor = ActivityLogger::authorize($actor);
            $item = OrderItem::whereKey($item->id)->lockForUpdate()->firstOrFail();
            Gate::forUser($actor)->authorize('administer', $item);
            $data = Validator::make($data, [
                'user_id' => ['sometimes', 'required', 'exists:users,id'],
                'quantity' => ['sometimes', 'integer', 'min:0', 'max:10'],
                'meal_id' => ['sometimes', 'exists:meals,id', new MealWithoutVariants(), new MealInOrderGroup($item->order)],
            ])->validate();
            if (isset($data['user_id'])) {
                Gate::forUser($actor)->authorize('orderFor', [OrderItem::class,
                    Meal::findOrFail($data['meal_id'] ?? $item->meal_id), User::findOrFail($data['user_id'])]);
            }
            $before = $item->getAttributes();
            $item->update($data);
            $item->order->reopen();
            ActivityLogger::record($actor, 'order_item.updated', $item, $before, $item->getAttributes());
            event(new OrderUpdated($item->order, $item->user, $item));

            return $item;
        });
    }

    public function delete(User $actor, OrderItem $item): void
    {
        DB::transaction(function () use ($actor, $item): void {
            $actor = ActivityLogger::authorize($actor);
            $item = OrderItem::whereKey($item->id)->lockForUpdate()->firstOrFail();
            Gate::forUser($actor)->authorize('administer', $item);
            $before = $item->getAttributes();
            $item->delete();
            $item->order->reopen();
            ActivityLogger::record($actor, 'order_item.deleted', $item, $before);
            event(new OrderUpdated($item->order, $item->user, $item));
        });
    }
}
