<?php

namespace Tests\Feature;

use App\Events\NewOrderPossibility;
use App\Meal;
use App\OrderItem;
use App\User;
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

        $meal = factory(Meal::class)->raw();

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
        $meal = factory(Meal::class)->create();

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
            'price' => Money::parse(100)->formatByDecimal()
        ]);
    }

    /** @test */
    public function admin_can_delete_a_meal()
    {
        $meal = factory(Meal::class)->create();

        $this->loginAsAdmin();

        $this->delete(route('meals.destroy', $meal))
            ->assertRedirect(route('meals.create'));
        $this->assertDatabaseMissing('meals', $meal->toArray());

        $meal = factory(Meal::class)->create();
        $this->deleteJson(route('meals.destroy', $meal))
            ->assertSuccessful();
        $this->assertDatabaseMissing('meals', $meal->toArray());
    }

    /** @test */
    public function admin_can_create_a_meal_and_stay_on_the_create_page()
    {
        $meal = factory(Meal::class)->raw();

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
        $meal = factory(Meal::class)->create();

        $this->withExceptionHandling();

        $this->get(route('meals.edit', $meal))->assertRedirect(route('login'));
        $this->getJson(route('meals.edit', $meal))->assertStatus(Response::HTTP_UNAUTHORIZED);

        $this->putJson(route('meals.update', $meal))->assertStatus(Response::HTTP_UNAUTHORIZED);
    }

    /** @test */
    public function guests_and_users_are_not_allowed_to_delete_meals()
    {
        $meal = factory(Meal::class)->create();

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
        $meal = factory(Meal::class)->create();

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
        /** @var Meal $meal1 */
        $meal1 = factory(Meal::class)->create([
            'date_from' => Carbon::today(),
            'date_to' => Carbon::today(),
        ]);

        /** @var Meal $meal2 */
        $meal2 = factory(Meal::class)->create([
            'date_from' => Carbon::tomorrow(),
            'date_to' => Carbon::tomorrow(),
        ]);

        /** @var Meal $meal3 */
        $meal3 = factory(Meal::class)->create([
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
    public function is_shows_meals_for_the_current_day()
    {
        /** @var Meal $meal1 */
        $meal1 = factory(Meal::class)->create([
            'date_from' => Carbon::today(),
            'date_to' => Carbon::today(),
        ]);

        /** @var Meal $variant1 */
        $variant1 = factory(Meal::class)->create([
            'date_from' => Carbon::today(),
            'date_to' => Carbon::today(),
        ]);

        $meal1->variants()->save($variant1);

        /** @var Meal $meal2 */
        $meal2 = factory(Meal::class)->create([
            'date_from' => Carbon::today()->addWeekday(),
            'date_to' => Carbon::today()->addWeekday(),
        ]);

        /** @var Meal $meal3 */
        $meal3 = factory(Meal::class)->create([
            'date_from' => Carbon::today(),
            'date_to' => Carbon::today()->addWeekday(),
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
        $meal = factory(Meal::class)->create([
            'date_from' => Carbon::today(),
            'date_to' => Carbon::today(),
        ]);

        /** @var Meal $variant */
        $variant = factory(Meal::class)->create([
            'date_from' => Carbon::today(),
            'date_to' => Carbon::today(),
            'parent_id' => $meal->id
        ]);

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
        $meal = factory(Meal::class)->create();


        $this->loginAsAdmin();

        $this->get(route('meals.show', $meal))
            ->assertSee($meal->title);

        $this->getJson(route('meals.show', $meal))
            ->assertJson($meal->toArray());
    }

    /** @test */
    public function it_throws_an_exception_when_trying_to_delete_on_ordered_meal()
    {
        //$this->withExceptionHandling();

        $meal = factory(Meal::class)->create();

        $meal->orderItems()->save(factory(OrderItem::class)->make());

        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        $this->loginAsAdmin()->delete(route('meals.destroy', $meal));
    }

    /** @test */
    public function it_can_send_a_notifications_when_a_new_meal_was_created_when_user_opted_in()
    {
        /** @var User $john */
        $john = factory(User::class)->create([
            'settings' => [
                User::SETTING_NEW_ORDER_POSSIBILITY_NOTIFICATION => "1"
            ]
        ]);

        /** @var User $sara */
        $sara = factory(User::class)->create([
            'settings' => [
                User::SETTING_NEW_ORDER_POSSIBILITY_NOTIFICATION => "0"
            ]
        ]);

        Notification::fake();

        /** @var Meal $meal */
        $meal = factory(Meal::class)->create();
        event(new NewOrderPossibility($meal->date_from));

        Notification::assertSentTo($john, \App\Notifications\NewOrderPossibility::class);
        Notification::assertNotSentTo($sara, \App\Notifications\NewOrderPossibility::class);
    }

    /** @test */
    public function meals_can_be_filtered_by_provider()
    {
        $date = today()->addDay()->toDateString();

        /** @var Meal $meal1 */
        $meal1 = factory(Meal::class)->create([
            'provider' => 'provider_1',
            'date_from' => $date,
            'date_to' => $date
        ]);

        /** @var Meal $meal2 */
        $meal2 = factory(Meal::class)->create([
            'provider' => 'provider_2',
            'date_from' => $date,
            'date_to' => $date
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
            ->get(route('meals.index', ['date' => $date, 'provider' => $meal1->provider]))
            ->assertSee($meal1->title)
            ->assertDontSee($meal2->title);

        $this->login()
            ->getJson(route('meals.index', ['date' => $date, 'provider' => $meal1->provider]))
            ->assertSee($meal1->title)
            ->assertDontSee($meal2->title);
    }
}
