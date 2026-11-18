<?php

namespace App\Http\Resources;

use App\Models\Meal;
use App\Models\OrderItem;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MealOptionResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        foreach (collect([$this->resource])->concat($this->variants) as $meal) {
            foreach ($meal->orderItems as $item) {
                $item->setRelation('meal', $meal);
            }
        }
        $choices = $this->variants->isEmpty() ? collect([$this->resource]) : $this->variants;
        $orders = collect([$this->resource])->concat($this->variants)
            ->flatMap(fn (Meal $meal) => $meal->orderItems)
            ->filter(fn (OrderItem $item): bool => $item->user_id === $request->user()->id && $item->quantity > 0);

        return [
            'id' => $this->id,
            'title' => $this->title,
            'date' => $this->date->toDateString(),
            'provider' => (string) $this->provider,
            'description' => $request->user()->settings->hideOrderingMealDescription ? null : strip_tags($this->description ?? ''),
            'image' => preg_match('#^(https?://|/(?!/))#', $this->image ?? '') ? $this->image : null,
            'preferred' => $this->is_preferred,
            'avoided' => $this->is_hated,
            'info' => $this->info->toArray(),
            'choices' => $choices->map(fn (Meal $meal): array => [
                'id' => $meal->id, 'title' => $meal->variant_title, 'price' => (string) $meal->price,
            ])->values()->all(),
            'orders' => $orders->map(fn (OrderItem $item): array => (new OwnOrderResource($item))->resolve($request))->values()->all(),
            'canOrder' => $request->user()->can('create', [OrderItem::class, $this->date]),
            'storeUrl' => route('order_items.store', absolute: false),
        ];
    }
}
