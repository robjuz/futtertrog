<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

class MealOrderingTest extends TestCase
{
    /** @test */
    public function user_can_order_a_meal_for_himself()
    {
        $meal = factory('App\Meal')->create();

        $this->login();
        $this->post(route('orders.store'), [
            'date' => $meal->date_from,
            'user_id' => auth()->id(),
            'meal_id' => $meal->id
        ]);

        $this->assertTrue(auth()->user()->orderItems()->where('meal_id', $meal->id)->exists());
    }

    /** @test */
    public function user_cannot_order_a_meal_for_other_users()
    {
        $meal = factory('App\Meal')->create();

        $user = factory('App\User')->create();

        $this->login();

        $this->post(route('orders.store'), [
            'date' => $meal->date_from,
            'user_id' => $user->id,
            'meal_id' => $meal->id
        ]);

        $this->assertTrue(auth()->user()->orderItems()->where('meal_id', $meal->id)->exists());
    }

    public function admin_can_order_a_meal_for_other_users()
    {
        $meal = factory('App\Meal')->create();

        $user = factory('App\User')->create();
        $admin = factory('App\User')->create(['is_admin' => true]);

        $this->login($admin);

        $this->post(route('orders.store'), [
            'date' => $meal->date_from,
            'user_id' => $user->id,
            'meal_id' => $meal->id
        ]);

        $this->assertFalse(auth()->user()->meals->contains($meal));
        $this->assertTrue($user->meals->contains($meal));
    }
}
