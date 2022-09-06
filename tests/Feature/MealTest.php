<?php

namespace Tests\Feature;

use App\Events\NewOrderPossibility;
use App\Meal;
use App\MealProviders\Basic;
use App\MealProviders\CallAPizza;
use App\MealProviders\Holzke;
use App\Order;
use App\OrderItem;
use App\User;
use App\UserSettings;
use Cknow\Money\Money;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Tests\TestCase;

class MealTest extends TestCase
{
    /** @test */
    public function only_admin_can_create_a_meal()
    {

        $meal = Meal::factory()->raw();

        $this->login()->withExceptionHandling();

        $this->get(route('meals.create'))
            ->assertForbidden();

        $this->post(route('meals.store'), $meal)
            ->assertForbidden();

        $this->postJson(route('meals.store'), $meal)
            ->assertForbidden();

        $this->assertDatabaseMissing('meals', $meal);

        $this->loginAsAdmin();

        $this->get(route('meals.create'))
            ->assertViewIs('meal.create');

        $this->post(route('meals.store'), $meal)
            ->assertRedirect(route('meals.index'));

        $this->postJson(route('meals.store'), $meal)
            ->assertSuccessful();

        $this->assertDatabaseHas('meals', $meal);
    }

    /** @test */
    public function only_admin_can_update_a_meal()
    {
        $meal = Meal::factory()->create();

        $attributes = [
            'title' => 'Changed title',
            'description' => 'Changed description',
            'price' => 100
        ];


        $this->login()->withExceptionHandling();

        $this->get(route('meals.edit', $meal))
            ->assertForbidden();

        $this->put(route('meals.update', $meal), $attributes)
            ->assertForbidden();

        $this->putJson(route('meals.update', $meal), $attributes)
            ->assertForbidden();

        $this->assertDatabaseMissing('meals', $attributes);

        $this->loginAsAdmin();

        $this->get(route('meals.edit', $meal))
            ->assertViewIs('meal.edit');

        $this->put(route('meals.update', $meal), $attributes)
            ->assertRedirect(route('meals.index'));

        $this->putJson(route('meals.update', $meal), $attributes)
            ->assertJsonFragment([
                'title' => 'Changed title',
                'description' => 'Changed description',
                'price' => Money::parse(100)
            ]);

        $this->assertDatabaseHas('meals', [
            'title' => 'Changed title',
            'description' => 'Changed description',
            'price' => Money::parse(100)->getAmount()
        ]);
    }

    /** @test */
    public function admin_can_delete_a_meal()
    {
        $meal = Meal::factory()->create();

        $this->loginAsAdmin();

        $this->delete(route('meals.destroy', $meal))
            ->assertRedirect(route('meals.create'));
        $this->assertDatabaseMissing('meals', $meal->toArray());

        $meal = Meal::factory()->create();
        $this->deleteJson(route('meals.destroy', $meal))
            ->assertSuccessful();
        $this->assertDatabaseMissing('meals', $meal->toArray());
    }

    /** @test */
    public function admin_can_create_a_meal_and_stay_on_the_create_page()
    {
        $meal = Meal::factory()->raw();

        $this->loginAsAdmin();

        $this->post(route('meals.store'), $meal + ['saveAndNew' => 'saveAndNew'])
            ->assertRedirect(route('meals.create'));
    }

    /** @test */
    public function guests_are_not_allowed_to_create_meals()
    {
        $this->withExceptionHandling();

        $this->get(route('meals.create'))->assertRedirect(route('login'));
        $this->getJson(route('meals.create'))->assertStatus(Response::HTTP_UNAUTHORIZED);
        $this->post(route('meals.store'))->assertRedirect(route('login'));
        $this->postJson(route('meals.store'))->assertStatus(Response::HTTP_UNAUTHORIZED);
    }

    /** @test */
    public function guests_are_not_allowed_to_see_meals()
    {
        $this->withExceptionHandling();

        $this->get(route('meals.index'))->assertRedirect(route('login'));
        $this->getJson(route('meals.index'))->assertStatus(Response::HTTP_UNAUTHORIZED);
    }

    /** @test */
    public function guests_are_not_allowed_to_edit_meals()
    {
        $meal = Meal::factory()->create();

        $this->withExceptionHandling();

        $this->get(route('meals.edit', $meal))->assertRedirect(route('login'));
        $this->getJson(route('meals.edit', $meal))->assertStatus(Response::HTTP_UNAUTHORIZED);

        $this->putJson(route('meals.update', $meal))->assertStatus(Response::HTTP_UNAUTHORIZED);
    }

    /** @test */
    public function guests_and_users_are_not_allowed_to_delete_meals()
    {
        $meal = Meal::factory()->create();

        $this->withExceptionHandling();

        $this->delete(route('meals.destroy', $meal))->assertRedirect(route('login'));
        $this->deleteJson(route('meals.destroy', $meal))->assertStatus(Response::HTTP_UNAUTHORIZED);


        $this->login()
            ->delete(route('meals.destroy', $meal))
            ->assertForbidden();
    }

    /** @test */
    public function guests_and_users_are_not_allowed_to_see_meal_details()
    {
        $meal = Meal::factory()->create();

        $this->withExceptionHandling();

        $this->get(route('meals.show', $meal))->assertRedirect(route('login'));
        $this->getJson(route('meals.show', $meal))->assertStatus(Response::HTTP_UNAUTHORIZED);

        $this->login()
            ->get(route('meals.show', $meal))
            ->assertForbidden();
    }

    /** @test */
    public function meals_can_be_filtered_down_by_date()
    {
        /** @var User $user */
        $user = User::factory()->create();

        /** @var Meal $meal1 */
        $meal1 = Meal::factory()->create([
            'date' => Carbon::today(),
        ]);

        /** @var Meal $meal2 */
        $meal2 = Meal::factory()->create([
            'date' => Carbon::tomorrow(),
        ]);

        $user->order($meal1);
        $user->order($meal2);

        $this->login($user);

        $this->get(route('meals.index', ['date' => Carbon::today()->toDateString()]))
            ->assertSee($meal1->title)
            ->assertDontSee($meal2->title);
    }

    /** @test */
    public function is_shows_meals_for_the_current_day()
    {
        /** @var Meal $meal1 */
        $meal1 = Meal::factory()->create([
            'date' => Carbon::today(),
        ]);

        /** @var Meal $variant1 */
        $variant1 = Meal::factory()->create([
            'date' => Carbon::today(),
        ]);

        $meal1->variants()->save($variant1);

        /** @var Meal $meal2 */
        $meal2 = Meal::factory()->create([
            'date' => Carbon::today()->addWeekday(),
        ]);

        /** @var Meal $meal3 */
        $meal3 = Meal::factory()->create([
            'date' => Carbon::today(),
        ]);

        $this->login();

        $this->get(route('meals.index'))
            ->assertSee($meal1->title)
            ->assertSee($variant1->variant_title)
            ->assertSee($meal3->title)
            ->assertDontSee($meal2->title);

        $this->getJson(route('meals.index'))
            ->assertSee($meal1->title)
            ->assertSee($variant1->variant_title)
            ->assertSee($meal3->title)
            ->assertDontSee($meal2->title);
    }

    /** @test */
    public function meal_variants_are_listed_as_relations_in_json_response()
    {
        /** @var Meal $meal */
        $meal = Meal::factory()->create();

        /** @var Meal $variant */
        $variant = $meal->variants()->save(
            Meal::factory()->make()
        );

        $this->login();

        $this->getJson(route('meals.index'))
            ->assertJson([
                [
                    'title' => $meal->title,
                    'variants' => [
                        [
                            'title' => $variant->title
                        ]
                    ]
                ]
            ])
            ->assertJsonCount(1);
    }

    /** @test */
    public function admin_can_see_meal_details()
    {
        $meal = Meal::factory()->create();


        $this->loginAsAdmin();

        $this->get(route('meals.show', $meal))
            ->assertSee($meal->title);

        $this->getJson(route('meals.show', $meal))
            ->assertJsonFragment($meal->toArray());
    }

    /** @test */
    public function it_throws_an_exception_when_trying_to_delete_on_ordered_meal()
    {
        //$this->withExceptionHandling();

        $meal = Meal::factory()->create();

        $meal->orderItems()->save(OrderItem::factory()->make());

        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        $this->loginAsAdmin()->delete(route('meals.destroy', $meal));
    }

    /** @test */
    public function it_can_send_a_notifications_when_a_new_meal_was_created_when_user_opted_in()
    {
        $johnSettings = new UserSettings();
        $johnSettings->newOrderPossibilityNotification = true;

        /** @var User $john */
        $john = User::factory()->create([
            'settings' => $johnSettings
        ]);


        $saraSettings = new UserSettings();
        $saraSettings->newOrderPossibilityNotification = false;

        /** @var User $sara */
        $sara = User::factory()->create([
            'settings' => $saraSettings
        ]);

        Notification::fake();

        /** @var Meal $meal */
        $meal = Meal::factory()->create();
        event(new NewOrderPossibility($meal->date));

        Notification::assertSentTo($john, \App\Notifications\NewOrderPossibility::class);
        Notification::assertNotSentTo($sara, \App\Notifications\NewOrderPossibility::class);
    }

    /** @test */
    public function meals_can_be_filtered_by_provider()
    {
        $date = today()->addDay()->toDateString();

        /** @var Meal $meal1 */
        $meal1 = Meal::factory()->create([
            'provider' => app(Holzke::class),
            'date' => $date,
        ]);

        /** @var Meal $meal2 */
        $meal2 = Meal::factory()->create([
            'provider' => app(CallAPizza::class),
            'date' => $date,
        ]);

        $this->login()
            ->get(route('meals.index', ['date' => $date]))
            ->assertSee($meal1->title)
            ->assertSee($meal2->title);

        $this->login()
            ->getJson(route('meals.index', ['date' => $date]))
            ->assertSee($meal1->title)
            ->assertSee($meal2->title);

        $this->login()
            ->get(route('meals.index', ['date' => $date, 'provider' => $meal1->provider->getKey()]))
            ->assertSee($meal1->title)
            ->assertDontSee($meal2->title);

        $this->login()
            ->getJson(route('meals.index', ['date' => $date, 'provider' => $meal1->provider->getKey()]))
            ->assertSee($meal1->title)
            ->assertDontSee($meal2->title);
    }

    /**
     * @test
     */
    public function it_redirects_to_the_next_day_without_orders_when_user_opted_in()
    {
        $johnSettings = new UserSettings();
        $johnSettings->redirectToNextDay = true;

        /** @var User $john */
        $john = User::factory()->create(
            [
                'settings' => $johnSettings
            ]
        );


        $saraSettings = new UserSettings();
        $saraSettings->redirectToNextDay = false;

        /** @var User $sara */
        $sara = User::factory()->create(
            [
                'settings' => $saraSettings
            ]
        );


        /** @var Meal $meal1 */
        $meal1 = Meal::factory()->create(
            [
                'date' => today(),
            ]
        );

        $dateToRedirect = today()->addWeekdays(3);

        Meal::factory()->create(
            [
                'date' => $dateToRedirect
            ]
        );

        $john->order($meal1);
        $sara->order($meal1);

        $this->login($john)
            ->get(route('meals.index'))
            ->assertRedirect(route('meals.index', ['date' => $dateToRedirect->toDateString()]));

        $this->login($sara)
            ->get(route('meals.index'))
            ->assertOk();
    }
}
