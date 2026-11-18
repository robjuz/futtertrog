<?php

namespace App\Http\Resources;

use App\Models\Deposit;
use App\Models\OrderItem;
use Illuminate\Http\Request;
use Illuminate\Pagination\AbstractPaginator;

class OrderingData
{
    /** @return array<string, mixed> */
    public static function orders(AbstractPaginator $orders, Request $request): array
    {
        return self::page($orders, $orders->getCollection()->map(
            fn (OrderItem $item): array => (new OwnOrderResource($item))->resolve($request)
        )->all());
    }

    /** @return array<string, mixed> */
    public static function deposits(AbstractPaginator $deposits): array
    {
        return self::page($deposits, $deposits->getCollection()->map(fn (Deposit $deposit): array => [
            'id' => $deposit->id, 'value' => (string) money($deposit->value),
            'comment' => $deposit->comment, 'date' => $deposit->created_at->toDateString(),
        ])->all());
    }

    /**
     * @param array<int, array<string, mixed>> $items
     *  @return array<string, mixed>
     */
    private static function page(AbstractPaginator $page, array $items): array
    {
        return [
            'items' => array_values($items), 'previous' => $page->previousPageUrl(),
            'next' => $page->nextPageUrl(), 'current' => $page->currentPage(),
        ];
    }
}
