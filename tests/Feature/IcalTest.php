<?php

namespace Tests\Feature;

use App\Meal;
use App\Order;
use App\OrderItem;
use App\User;
use Tests\TestCase;

class IcalTest extends TestCase
{
    /** @test */
    public function it_allows_to_export_users_order_history_to_ical()
    {
        $user = factory(User::class)->create();
        $orderItem = factory(OrderItem::class)->create(['user_id' => $user->id]);

        $this->login($user)
            ->get(route('meals.ical'))
            ->assertSee($orderItem->meal->title.' ('.$orderItem->quantity.')')
            ->assertSee($orderItem->meal->description);
    }

    /** @test */
    public function it_can_be_filtered_out_by_date_range()
    {
        $user = factory(User::class)->create();

        $todayMeal = factory(Meal::class)->create(
            [
                'date_from' => today(),
                'date_to' => today(),
            ]
        );

        $tomorrowMeal = factory(Meal::class)->create(
            [
                'date_from' => today()->addDay(),
                'date_to' => today()->addDay(),
            ]
        );

        factory(OrderItem::class)->create(
            [
                'user_id' => $user->id,
                'meal_id' => $todayMeal->id,
                'order_id' => factory(Order::class)->create(['date' => $todayMeal->date_from])
            ]
        );

        factory(OrderItem::class)->create(
            [
                'user_id' => $user->id,
                'meal_id' => $tomorrowMeal->id,
                'order_id' => factory(Order::class)->create(['date' => $tomorrowMeal->date_from])
            ]
        );

        $this->login($user)
            ->get(route('meals.ical', [
                'from' => today()->addDay()->toDateString()
            ]))
        ->assertSee($tomorrowMeal->title)
        ->assertDontSee($todayMeal->title);

        $this->login($user)
            ->get(route('meals.ical', [
                'to' => today()->toDateString()
            ]))
            ->assertSee($todayMeal->title)
            ->assertDontSee($tomorrowMeal->title);
    }
}
