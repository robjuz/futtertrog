<?php

namespace Feature;

use App\Events\NewOrderPossibilities;
use App\MealProviders\Gourmetta;
use App\Models\Meal;
use App\Models\Order;
use App\Models\User;
use App\Notifications\NewOrderPossibilities as NewOrderPossibilitiesNotification;
use App\Providers\MealProvidersServiceProvider;
use App\UserSettings;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;

class GourmettaTest extends TestCase
{
    const MEAL_JSON = '{"userUuid":"12345","from":"2024-06-03","to":"2024-06-03","orderDays":[{"date":"2024-06-03","quantity":0,"orderedMeals":[{"mealId":"1717365600_1","meal":{"id":"1717365600_1","rcsMenuId":1,"date":"2024-06-03","categoryName":"Menü 1","categoryShortName":"M1","textId":"1717365600_1","nr":1,"type":"M","name":"Steinpilzcremesuppe","description":"mit Walnussravioli","tags":["VEGGIE"],"price":4.75,"multipleOrders":true,"locked":false,"deadlineOrder":"2024-06-03T08:00:00+0200","deadlineCancel":"2024-06-03T08:00:00+0200","ingredients":[],"foodAdditives":[],"allergens":[{"code":"A1","name":"Weizen"},{"code":"G","name":"Milch, Laktose"},{"code":"H3","name":"Walnüsse"},{"code":"I","name":"Sellerie"}],"matchingFilters":["Menüs","Veggie"]},"quantity":0}]}]}';

    /** @test */
    public function it_can_create_meals_from_gourmetta_json()
    {
        $gourmettaServiceMock = Mockery::mock(Gourmetta::class)->makePartial();

        $gourmettaServiceMock->shouldReceive('getMealsDataForDateJson')
            ->withAnyArgs()
            ->andReturn(json_decode(self::MEAL_JSON, true))
            ->once();

        $meals = $gourmettaServiceMock->getMealsDataForDate(today());

        $this->assertCount(1, $meals);
    }

    /** @test */
    public function it_resolves_a_gourmetta_provider_from_app_container()
    {
        config(['services.gourmetta.enabled' => true]);
        $this->app->register(MealProvidersServiceProvider::class, true);

        $this->assertInstanceOf(Gourmetta::class, app(Gourmetta::class));
        $this->assertSame(app(Gourmetta::class), app(Gourmetta::class));
    }

    /** @test */
    public function it_sent_a_new_order_possibility_notification_to_users_that_opted_in()
    {
        Notification::fake();

        Carbon::setTestNow(today()->addWeekday()); //ensure we test over a weekday

        $today = today();

        $settings = new UserSettings();
        $settings->newOrderPossibilityNotification = true;
        $settings->language = 'de';


        $tom = User::factory()->create(
            [
                'settings' => $settings,
            ]
        );

        $this->partialMock(Gourmetta::class, function (MockInterface $mock) {
            $mock->shouldReceive('getMealsDataForDateJson')
                ->withAnyArgs()
                ->andReturn(json_decode(self::MEAL_JSON, true))
                ->once();

            $mock->shouldReceive('getMealsDataForDateJson')
                ->withAnyArgs()
                ->andReturn([ 'orderDays' => []])
                ->once();
        });

        app(Gourmetta::class)->getAllUpcomingMeals();

        Notification::assertSentTo(
            $tom,
            NewOrderPossibilitiesNotification::class,
            function ($message, $channels, User $notifiable) use ($today) {
                $toArray = $message->toArray($notifiable);
                $toMail = $message->toMail($notifiable);

                $day = $today->locale($notifiable->settings->language)->isoFormat('ddd MMM DD YYYY');

                return $today->isSameAs('Y-m-d', $toArray['dates'][0])
                    && $toMail->subject === __('New order possibilities')
                    && in_array(__('New order possibility for :day', ['day' => $day]), $toMail->introLines)
                    && $toMail->actionText === __('Click here for more details');
            }
        );
    }

    /** @test */
    public function it_fires_a_event_when_new_meals_were_created()
    {
        Event::fake();

        Carbon::setTestNow(today()->addWeekday()); //ensure we test over a weekday

        $this->partialMock(Gourmetta::class, function (MockInterface $mock) {
            $mock->shouldReceive('getMealsDataForDateJson')
                ->withAnyArgs()
                ->andReturn([ 'orderDays' => []])
                ->once();
        });

        app(Gourmetta::class)->getAllUpcomingMeals();

        Event::assertNotDispatched(NewOrderPossibilities::class);

        $this->partialMock(Gourmetta::class, function (MockInterface $mock) {
            $mock->shouldReceive('getMealsDataForDateJson')
                ->withAnyArgs()
                ->andReturn(json_decode(self::MEAL_JSON, true))
                ->once();

            $mock->shouldReceive('getMealsDataForDateJson')
                ->withAnyArgs()
                ->andReturn([ 'orderDays' => []])
                ->once();
        });

        app(Gourmetta::class)->getAllUpcomingMeals();
    }

    /** @test */
    public function it_allows_to_auto_order()
    {
        /** @var User $user */
        $user = User::factory()->create();

        /** @var Meal $meal1 */
        $meal = Meal::factory()->create([
            'provider' => Gourmetta::class,
            'date' => Carbon::today(),
            'external_id' => '111'
        ]);


        $orderItem1 = $user->order($meal);
        $order = $orderItem1->order;

        $gourmettaService = app(Gourmetta::class);

        $this->assertStringContainsString($meal->external_id, json_encode($gourmettaService->prepareOrderItems($order)));

    }

    public function testGetOrder()
    {
        $gourmetta = app(Gourmetta::class);

        /** @var User $user */
        $user = User::factory()->create();

        /** @var Meal $meal */
        $meal = Meal::factory()->create(
            [
                'provider' => Gourmetta::class,
                'date' => Carbon::today(),
                'external_id' => '111'
            ]
        );

        $this->assertDatabaseCount('orders', 0);

        $user->order($meal);

        $order = $gourmetta->getOrder(now());


        $this->assertEquals(Order::STATUS_OPEN, $order->status);
        $this->assertDatabaseCount('orders', 1);

        $order->markOrdered();
        $order = $gourmetta->getOrder(now());

        $this->assertEquals(Order::STATUS_ORDERED, $order->status);
        $this->assertDatabaseCount('orders', 1);

        //---//
        $order = $gourmetta->getOrder(now()->addWeek());
        $this->assertEquals(Order::STATUS_OPEN, $order->status);
        $this->assertDatabaseCount('orders', 2);
    }
}
