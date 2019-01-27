<?php

namespace Tests\Feature;

use Illuminate\Support\Carbon;
use Tests\TestCase;

class MealTest extends TestCase
{
    /** @test */
    public function user_can_create_a_meal()
    {
        $meal = factory('App\Meal')->raw();

        $this->login();

        $this->post(route('meals.store'), $meal)
            ->assertRedirect(route('meals.index'));

        $this->assertDatabaseHas('meals', $meal);
    }

    /** @test */
    public function guests_are_not_allowed_to_create_meals()
    {
        $this->withExceptionHandling();

        $this->get(route('meals.create'))->assertRedirect(route('login'));
        $this->post(route('meals.store'))->assertRedirect(route('login'));
    }

    /** @test */
    public function guests_are_not_allowed_to_see_meals()
    {
        $meal = factory('App\Meal')->create();

        $this->withExceptionHandling();

        $this->get(route('meals.index'))->assertRedirect(route('login'));
        $this->get(route('meals.show', $meal))->assertRedirect(route('login'));
    }

    /** @test */
    public function meals_can_be_filtered_down_by_date()
    {
        $meal1 = factory('App\Meal')->create([
            'date_from' => Carbon::today(),
            'date_to' => Carbon::today(),
        ]);

        $meal2 = factory('App\Meal')->create([
            'date_from' => Carbon::tomorrow(),
            'date_to' => Carbon::tomorrow(),
        ]);

        $meal3 = factory('App\Meal')->create([
            'date_from' => Carbon::today(),
            'date_to' => Carbon::tomorrow(),
        ]);

        $this->login();

        $this->get(route('meals.index', ['date' => Carbon::today()->toDateString()]))
            ->assertSee($meal1->title)
            ->assertSee($meal3->title)
            ->assertDontSee($meal2->title);
    }

    /** @test */
    public function is_shows_meals_for_the_next_day()
    {
        $meal1 = factory('App\Meal')->create([
            'date_from' => Carbon::today(),
            'date_to' => Carbon::today(),
        ]);

        $meal2 = factory('App\Meal')->create([
            'date_from' => Carbon::today()->addWeekday(),
            'date_to' => Carbon::today()->addWeekday(),
        ]);

        $meal3 = factory('App\Meal')->create([
            'date_from' => Carbon::today(),
            'date_to' => Carbon::today()->addWeekday(),
        ]);

        $this->login();

        $this->get(route('meals.index'))
            ->assertSee($meal2->title)
            ->assertSee($meal3->title)
            ->assertDontSee($meal1->title);
    }


}
