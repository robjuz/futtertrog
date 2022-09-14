<?php

namespace Tests\Feature;

use App\Meal;
use App\User;
use App\View\Components\WeekNavigation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class WeekNavigationTest extends TestCase
{
    /** @test */
    public function it_doesnt_show_the_next_week_link_when_no_order_possibilities_for_next_week_present()
    {
        $nextWeekMonday = Carbon::now()->addWeek()->startofWeek();

        $this->login()
            ->get(route('meals.index'))
            ->assertDontSee($nextWeekMonday->toDateString(), false);

        // Random day next week
        $date = Carbon::now()->addWeek()->addDays(rand(0, 6));

        Meal::factory()->create([
            'date' => $date,
        ]);

        $this->login()
            ->get(route('meals.index'))
            ->assertSee($nextWeekMonday->toDateString(), false);
    }

    /**
     * @test
     */
    public function it_shows_an_ordered_label(){
        /** @var Meal $meal */
        $meal = Meal::factory()->create(['date' => today()]);

        /** @var User $user */
        $user = User::factory()->create();

        $orderItem = $user->order($meal);

        $this->login($user);

        $view = $this->component(WeekNavigation::class);

        $view->assertSee(__('Ordered'));

        $orderItem->update(['quantity' => 0]);

        $view = $this->component(WeekNavigation::class);

        $view->assertDontSee(__('Ordered'));

    }
}
