<?php

namespace Tests\Feature;

use App\Events\NewOrderPossibilities;
use App\MealProviders\Holzke;
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

class HolzkeTest extends TestCase
{
    const MEAL_HTML= '<table class="menu-table menu-week-grid-normal weekdayCount1" id="menu-table_KW">
  <tbody><tr>
    <th class="weekday"></th>
      <th class="weekday ">
        Montag<br>
        <date>03.04.2023</date>
      </th>
    </tr>
    <tr>
      <th class="mealtitel menuhash-96e587 menu-1">
        Menü 1   blank

      </th>

<td mealid="01" splanid="2318182" day="2023-04-03" class="
           meal
           menuGroup_0
           Menü 1   blank

           td1" for="">
  <meal>
    <mealtxt>
      <span id="mealtext">Grüne Bohneneintopf mit Kasslerstückchen und 1 Roggenbrötchen<br><br><sup>2, 3, A, F, G, I, A1, A2, A3</sup><br><sup>320,3 kcal; 3,3 BE</sup></span>
      <br>
      <sub>
      </sub>
    </mealtxt>
  </meal>
   <!-- endet am Ende der Meal-Cell -->
    <cellfooter>
        <row>
        <!-- number input is needed for IE9 support -->

        </row>

    </cellfooter>
   <!-- if meal.menueText -->
</td>
    </tr>
</tbody></table>';

    /** @test */
    public function it_can_create_meals_from_holzke_html()
    {
        $holzkeServiceMock = Mockery::mock(Holzke::class)->makePartial();

        $holzkeServiceMock->shouldReceive('getHtml')
            ->withAnyArgs()
            ->andReturn(self::MEAL_HTML)
            ->once();

        $meals = $holzkeServiceMock->getMealsDataForDate(today());

        $this->assertCount(1, $meals);
    }

    /** @test */
    public function it_resolves_a_holzke_provider_from_app_container()
    {
        config(['services.holzke.enabled' => true]);
        $this->app->register(MealProvidersServiceProvider::class, true);

        $this->assertInstanceOf(Holzke::class, app(Holzke::class));
        $this->assertSame(app(Holzke::class), app(Holzke::class));
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

        $this->partialMock(Holzke::class, function (MockInterface $mock) {
            $mock->shouldReceive('getHtml')
            ->withAnyArgs()
            ->andReturn(self::MEAL_HTML)
            ->once();

            $mock->shouldReceive('getHtml')
            ->withAnyArgs()
            ->andReturn('<div></div>')
            ->once();
        });

       app(Holzke::class)->getAllUpcomingMeals();

        Notification::assertSentTo($tom, NewOrderPossibilitiesNotification::class, function ($message, $channels, User $notifiable) use ($today) {
            $toArray =  $message->toArray($notifiable);
            $toMail = $message->toMail($notifiable);

            $day = $today->locale($notifiable->settings->language)->isoFormat('ddd MMM DD YYYY');

            return $today->isSameAs('Y-m-d', $toArray['dates'][0])
                && $toMail->subject === __('New order possibilities')
                && in_array(__('New order possibility for :day', ['day' => $day]), $toMail->introLines)
                && $toMail->actionText === __('Click here for more details');
        });
    }

    /** @test */
    public function it_fires_a_event_when_new_meals_were_created()
    {
        Event::fake();

        Carbon::setTestNow(today()->addWeekday()); //ensure we test over a weekday

        $this->partialMock(Holzke::class, function (MockInterface $mock) {

            $mock->shouldReceive('getHtml')
                ->withAnyArgs()
                ->andReturn('<div></div>')
                ->once();
        });

        app(Holzke::class)->getAllUpcomingMeals();

        Event::assertNotDispatched(NewOrderPossibilities::class);

        $this->partialMock(Holzke::class, function (MockInterface $mock) {
            $mock->shouldReceive('getHtml')
                ->withAnyArgs()
                ->andReturn(self::MEAL_HTML)
                ->once();

            $mock->shouldReceive('getHtml')
                ->withAnyArgs()
                ->andReturn('<div></div>')
                ->once();
        });

        app(Holzke::class)->getAllUpcomingMeals();
    }

//    /** @test */
//    public function it_allows_to_auto_order()
//    {
//        /** @var User $user */
//        $user = User::factory()->create();
//
//        /** @var Meal $meal1 */
//        $meal = Meal::factory()->create([
//            'provider' => Holzke::class,
//            'date' => Carbon::today(),
//            'external_id' => '111'
//        ]);
//
//        /** @var Meal $meal1 */
//        $meal2 = Meal::factory()->create([
//            'provider' => Holzke::class,
//            'date' => Carbon::today()->addWeek(),
//            'external_id' => '222'
//        ]);
//
//        $orderItem1 = $user->order($meal);
//        $order1 = $orderItem1->order;
//        $order2 = $user->order($meal2)->order;
//
//        $this->assertTrue($order1->canBeAutoOrdered());
//        $this->assertTrue($order2->canBeAutoOrdered());
//
//
//        $this->partialMock(Holzke::class, function (MockInterface $mock) {
//            $mock->shouldReceive('getKey')
//                ->andReturn('Holzke');
//        });
//
//        app(Holzke::class)->autoOrder();
//
//        $order1->refresh();
//
//        $this->assertEquals('123', $order1->external_id);
//        $this->assertEquals(Order::STATUS_ORDERED, $order1->status);
//
//        $this->assertNull($order2->fresh()->external_id);
//        $this->assertEquals(Order::STATUS_OPEN, $order2->status);
//
//        $this->login($user)
//            ->putJson(route('order_items.update', $orderItem1), ['quantity' => 2]);
//
//        $order1->refresh();
//
//        $this->assertEquals(Order::STATUS_OPEN, $order1->status);
//
//        $this->assertTrue($order1->canBeAutoOrdered());
//        $this->assertTrue($order1->canBeUpdated());
//    }

    public function testGetOrder()
    {
        $holzke = app(Holzke::class);

        /** @var User $user */
        $user = User::factory()->create();

        /** @var Meal $meal */
        $meal = Meal::factory()->create(
            [
                'provider' => Holzke::class,
                'date' => Carbon::today(),
                'external_id' => '111'
            ]
        );

        $this->assertDatabaseCount('orders', 0);

        $user->order($meal);

        $order = $holzke->getOrder(now());


        $this->assertEquals(Order::STATUS_OPEN, $order->status);
        $this->assertDatabaseCount('orders', 1);

        $order->markOrdered();
        $order = $holzke->getOrder(now());

        $this->assertEquals(Order::STATUS_ORDERED, $order->status);
        $this->assertDatabaseCount('orders', 1);

        //---//
        $order = $holzke->getOrder(now()->addWeek());
        $this->assertEquals(Order::STATUS_OPEN, $order->status);
        $this->assertDatabaseCount('orders', 2);
    }
}
