<?php

namespace Tests\Feature;

use App\Events\NewOrderPossibilities;
use App\Events\NewOrderPossibility;
use App\Meal;
use App\Notifications\NewOrderPossibilities as NewOrderPossibilitiesNotification;
use App\MealProviders\CallAPizza;
use App\MealProviders\Holzke;
use App\Order;
use App\Services\MealService;
use App\User;
use App\UserSettings;
use Illuminate\Support\Facades\Event;
use Ixudra\Curl\Builder;
use Ixudra\Curl\Facades\Curl;
use Mockery;
use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;
use Mockery\MockInterface;
use function PHPUnit\Framework\once;

class HolzkeTest extends TestCase
{
    /** @test */
    public function it_can_create_meals_from_holzke_html()
    {
        $holzkeServiceMock = Mockery::mock(Holzke::class)->makePartial();

        $holzkeServiceMock->shouldReceive('getHtml')
            ->withAnyArgs()
            ->andReturn('<div><article class="articleGrid meal"><div class="cHead"><h2>Menü 1 blank (3,05 €)</h2></div>
							<div class="cBody grey">
								Cremige Tomatensuppe mit Reiseinlage und Fleischklösschen dazu 1 Scheibe Weißbrot
								<div class="infos clearfix"><span class="kcal">547.7 kcal</span><span class="be">5.9 BE</span><span class="zusatz"><a href="#zusatz" title="mit Farbstoff">1</a><a href="#zusatz" title="enth. Gluten">A</a><a href="#zusatz" title="enth. Ei">C</a><a href="#zusatz" title="Milch, Laktose">G</a><a href="#zusatz" title="enth. Sellerie">I</a><a href="#zusatz" title="enth. Senf">J</a><a href="#zusatz" title="enth. Weizen">A1</a></span></div>
							</div>
						</article></div>')
            ->once();

        $meals = $holzkeServiceMock->getMealsDataForDate(today());

        $this->assertCount(1, $meals);
    }

    /** @test */
    public function it_resolves_a_holzke_provider_from_app_container()
    {
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
            ->andReturn('<div><article class="articleGrid meal"><div class="cHead"><h2>Menü 1 blank (3,05 €)</h2></div>
							<div class="cBody grey">
								Cremige Tomatensuppe mit Reiseinlage und Fleischklösschen dazu 1 Scheibe Weißbrot
								<div class="infos clearfix"><span class="kcal">547.7 kcal</span><span class="be">5.9 BE</span><span class="zusatz"><a href="#zusatz" title="mit Farbstoff">1</a><a href="#zusatz" title="enth. Gluten">A</a><a href="#zusatz" title="enth. Ei">C</a><a href="#zusatz" title="Milch, Laktose">G</a><a href="#zusatz" title="enth. Sellerie">I</a><a href="#zusatz" title="enth. Senf">J</a><a href="#zusatz" title="enth. Weizen">A1</a></span></div>
							</div>
						</article></div>')
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

            return $today->isSameAs($toArray['dates'][0])
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
                ->andReturn('<div><article class="articleGrid meal"><div class="cHead"><h2>Menü 1 blank (3,05 €)</h2></div>
							<div class="cBody grey">
								Cremige Tomatensuppe mit Reiseinlage und Fleischklösschen dazu 1 Scheibe Weißbrot
								<div class="infos clearfix"><span class="kcal">547.7 kcal</span><span class="be">5.9 BE</span><span class="zusatz"><a href="#zusatz" title="mit Farbstoff">1</a><a href="#zusatz" title="enth. Gluten">A</a><a href="#zusatz" title="enth. Ei">C</a><a href="#zusatz" title="Milch, Laktose">G</a><a href="#zusatz" title="enth. Sellerie">I</a><a href="#zusatz" title="enth. Senf">J</a><a href="#zusatz" title="enth. Weizen">A1</a></span></div>
							</div>
						</article></div>')
                ->once();

            $mock->shouldReceive('getHtml')
                ->withAnyArgs()
                ->andReturn('<div></div>')
                ->once();
        });

        app(Holzke::class)->getAllUpcomingMeals();
    }

    /** @test */
    public function it_allows_to_auto_order()
    {
        /** @var User $user */
        $user = User::factory()->create();

        /** @var Meal $meal1 */
        $meal = Meal::factory()->create([
            'provider' => Holzke::class,
            'date' => Carbon::today(),
            'external_id' => '111'
        ]);

        /** @var Meal $meal1 */
        $meal2 = Meal::factory()->create([
            'provider' => Holzke::class,
            'date' => Carbon::today()->addWeek(),
            'external_id' => '222'
        ]);

        $orderItem1 = $user->order($meal);
        $order1 = $orderItem1->order;
        $order2 = $user->order($meal2)->order;

        $this->assertTrue($order1->canBeAutoOrdered());
        $this->assertTrue($order2->canBeAutoOrdered());


        $this->partialMock(Holzke::class, function (MockInterface $mock) {
            $mock->shouldAllowMockingProtectedMethods();
            $mock->shouldReceive('getKey')
                ->andReturn('Holzke');

            $mock
                ->shouldReceive('getLastOrderId')
                ->once()
                ->andReturn('123');
        });

        app(Holzke::class)->autoOrder();

        $order1->refresh();

        $this->assertEquals('123', $order1->external_id);
        $this->assertEquals(Order::STATUS_ORDERED, $order1->status);

        $this->assertNull($order2->fresh()->external_id);
        $this->assertEquals(Order::STATUS_OPEN, $order2->status);

        $this->login($user)
            ->putJson(route('order_items.update', $orderItem1), ['quantity' => 2]);

        $order1->refresh();

        $this->assertEquals(Order::STATUS_OPEN, $order1->status);

        $this->assertTrue($order1->canBeAutoOrdered());
        $this->assertTrue($order1->canBeUpdated());
    }

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
