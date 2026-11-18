<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OwnOrderResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'meal_id' => $this->meal_id,
            'title' => $this->meal->title,
            'description' => $request->user()->settings->hideDashboardMealDescription ? null : strip_tags($this->meal->description ?? ''),
            'date' => $this->meal->date->toDateString(),
            'quantity' => $this->quantity,
            'price' => (string) $this->meal->price,
            'subtotal' => (string) $this->subtotal,
            'status' => __('futtertrog.orderStatus.'.$this->status),
            'canChange' => $request->user()->can('update', $this->resource),
            'updateUrl' => route('order_items.update', $this->id, false),
            'deleteUrl' => route('order_items.destroy', $this->id, false),
        ];
    }
}
