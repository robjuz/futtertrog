<?php

namespace Tests\Feature;

use App\Models\Meal;
use App\Models\OrderItem;
use App\Models\User;
use Tests\TestCase;

class IcalTest extends TestCase
{
    /** @test */
    public function it_allows_to_export_users_order_history_to_ical()
    {
        /** @var User $user */
        $user = User::factory()->create();
        $meal = Meal::factory()->create(['title' => 'menu 1', 'description' => 'menu 1 desc']);

        $orderItem = $user->order($meal);

        $this->login($user)
            ->get(route('meals.ical'))
            ->assertSee($orderItem->meal->title.' ('.$orderItem->quantity.')')
            ->assertSee($orderItem->meal->description);

        $meal = Meal::factory()->create(['title' => 'menu 1', 'description' => null]);
        $orderItem = OrderItem::factory()->create(['user_id' => $user->id, 'meal_id' => $meal->id]);

        $this->login($user)
            ->get(route('meals.ical'))
            ->assertSee($orderItem->meal->title.' ('.$orderItem->quantity.')');
    }

    /** @test */
    public function it_can_be_filtered_out_by_date_range()
    {
        $user = User::factory()->create();

        $todayMeal = Meal::factory()->create(
            [
                'date' => today(),
                'title' => 'today meal'
            ]
        );

        $tomorrowMeal = Meal::factory()->create(
            [
                'date' => today()->addDay(),
                'title' => 'tomorrow meal'
            ]
        );

        OrderItem::factory()->create(
            [
                'user_id' => $user->id,
                'meal_id' => $todayMeal->id,
            ]
        );

        OrderItem::factory()->create(
            [
                'user_id' => $user->id,
                'meal_id' => $tomorrowMeal->id,
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

    /**
     * @test
     */
    public function it_does_not_show_order_without_quantity()
    {
        /** @var User $user */
        $user = User::factory()->create();

        /** @var Meal $meal */
        $meal = Meal::factory()->create();

        $orderItem = $user->order($meal);

        $orderItem->update(['quantity' => 0]);

        $this->login($user)
            ->get(route('meals.ical'))
            ->assertDontSee($orderItem->meal->title);


        /** @var Meal $meal */
        $meal = Meal::factory()->inFuture()->create();

        $orderItem = $user->order($meal);

        $orderItem->update(['quantity' => 0]);

        $this->login($user)
            ->get(route('meals.ical'))
            ->assertDontSee($orderItem->meal->title);
    }
}
