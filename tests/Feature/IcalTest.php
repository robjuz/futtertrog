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
        $user = User::factory()->create();
        $meal = Meal::factory()->create(['title' => 'menu 1', 'description' => 'menu 1 desc']);
        $orderItem = OrderItem::factory()->create(['user_id' => $user->id, 'meal_id' => $meal->id]);

        $this->login($user)
            ->get(route('meals.ical'))
            ->assertSee($orderItem->meal->title.' ('.$orderItem->quantity.')')
            ->assertSee($orderItem->meal->description);
    }

    /** @test */
    public function it_can_be_filtered_out_by_date_range()
    {
        $user = User::factory()->create();

        $todayMeal = Meal::factory()->create(
            [
                'date_from' => today(),
                'date_to' => today(),
                'title' => 'today meal'
            ]
        );

        $tomorrowMeal = Meal::factory()->create(
            [
                'date_from' => today()->addDay(),
                'date_to' => today()->addDay(),
                'title' => 'tomorrow meal'
            ]
        );

        OrderItem::factory()->create(
            [
                'user_id' => $user->id,
                'meal_id' => $todayMeal->id,
                'order_id' => Order::factory()->create(['date' => $todayMeal->date_from])
            ]
        );

        OrderItem::factory()->create(
            [
                'user_id' => $user->id,
                'meal_id' => $tomorrowMeal->id,
                'order_id' => Order::factory()->create(['date' => $tomorrowMeal->date_from])
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
