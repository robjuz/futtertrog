<?php

namespace App\Actions\Admin;

use App\Models\AdminActivity;
use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class AutoOrder
{
    /** Do not wrap in a transaction: provider uncertainty must survive a caller rollback. */
    public function execute(User $actor, Order $order): Order
    {
        $actor = ActivityLogger::authorize($actor);
        Gate::forUser($actor)->authorize('autoOrder', $order);

        return $order->autoOrder($actor);
    }

    /**
     * Record a manually verified provider result; never call the provider here.
     * The order lock excludes an active provider transaction. A delayed worker must
     * recheck the reservation under that same lock before it can contact the provider.
     */
    public function reconcile(?User $actor, Order $order, int $attemptId, string $outcome): Order
    {
        return DB::transaction(function () use ($actor, $order, $attemptId, $outcome): Order {
            Gate::forUser($actor)->authorize('autoOrder', $order);
            $actor = ActivityLogger::authorize($actor);
            $order = Order::whereKey($order->id)->lockForUpdate()->firstOrFail();
            Gate::forUser($actor)->authorize('autoOrder', $order);
            Validator::make(['outcome' => $outcome], [
                'outcome' => ['required', Rule::in(['confirmed', 'not_received'])],
            ])->validate();
            $attempt = AdminActivity::whereKey($attemptId)->where('action', 'order.auto_ordered')
                ->where('target_type', Order::class)->where('target_id', $order->id)->lockForUpdate()->first();
            abort_unless($attempt && in_array($attempt->state, ['running', 'uncertain'], true), 409);
            abort_if($attempt->state === 'running' && $attempt->created_at->gt(now()->subMinutes(30)), 409,
                __('A running attempt can only be reconciled after 30 minutes.'));
            $before = ['state' => $attempt->state, 'status' => $order->status];
            if ($outcome === 'confirmed') {
                $contentChanged = ($attempt->changes['before']['content_fingerprint'] ?? null) !== $order->contentFingerprint();
                if (! $contentChanged) {
                    $order->markOrdered();
                }
            }
            $attempt->update(['state' => $outcome === 'confirmed' ? 'succeeded' : 'failed']);
            AdminActivity::create([
                'actor_id' => $actor->id,
                'action' => 'order.auto_order_reconciled',
                'target_type' => Order::class,
                'target_id' => $order->id,
                'changes' => [
                    'attempt_id' => $attempt->id,
                    'outcome' => $outcome,
                    ...($outcome === 'confirmed' ? ['content_changed' => $contentChanged] : []),
                    'before' => $before,
                    'after' => ['state' => $attempt->state, 'status' => $order->status],
                ],
            ]);

            return $order;
        });
    }
}
