<?php

namespace Tests\Feature;

use App\Events\NewOrderPossibility;
use App\Meal;
use App\Notifications\NewOrderPossibilities as NewOrderPossibilitiesNotification;
use App\Services\CallAPizzaService;
use App\Services\HolzkeService;
use App\User;
use Illuminate\Support\Facades\Event;
use Ixudra\Curl\Facades\Curl;
use Mockery;
use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;
use Mockery\MockInterface;

class HolzkeTest extends TestCase
{
    /** @test */
    public function it_can_create_meals_from_holzke_html()
    {
        $holzkeServiceMock = Mockery::mock(HolzkeService::class)->makePartial();

        $holzkeServiceMock->shouldReceive('getHtml')
            ->withAnyArgs()
            ->andReturn('<div><article class="articleGrid meal"><div class="cHead"><h2>Menü 1 blank (3,05 €)</h2></div>
							<div class="cBody grey">
								Cremige Tomatensuppe mit Reiseinlage und Fleischklösschen dazu 1 Scheibe Weißbrot
								<div class="infos clearfix"><span class="kcal">547.7 kcal</span><span class="be">5.9 BE</span><span class="zusatz"><a href="#zusatz" title="mit Farbstoff">1</a><a href="#zusatz" title="enth. Gluten">A</a><a href="#zusatz" title="enth. Ei">C</a><a href="#zusatz" title="Milch, Laktose">G</a><a href="#zusatz" title="enth. Sellerie">I</a><a href="#zusatz" title="enth. Senf">J</a><a href="#zusatz" title="enth. Weizen">A1</a></span></div>
							</div>
						</article></div>')
            ->once();

        $meals = $holzkeServiceMock->getMealsForDate(today());

        $this->assertCount(1, $meals);
    }

    /** @test */
    public function it_resolves_a_holzke_provider_from_app_container()
    {
        $this->assertInstanceOf(HolzkeService::class, app(HolzkeService::class));
        $this->assertSame(app(HolzkeService::class), app(HolzkeService::class));
    }

    /** @test */
    public function it_sent_a_new_order_possibility_notification_to_users_that_opted_in()
    {
        Notification::fake();

        Carbon::setTestNow(today()->addWeekday()); //ensure we test over a weekday

        $today = today();


        $tom = factory(User::class)->create(
            [
                'settings' => [
                    User::SETTING_NEW_ORDER_POSSIBILITY_NOTIFICATION => "1",
                    User::SETTING_LANGUAGE => 'de'
                ],
            ]
        );

        $this->partialMock(HolzkeService::class, function (MockInterface $mock) {
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

        $this->artisan('import:holzke');

        Notification::assertSentTo($tom, NewOrderPossibilitiesNotification::class, function ($message, $channels, $notifiable) use ($today) {
            $toArray =  $message->toArray($notifiable);
            $toMail = $message->toMail($notifiable);

            $day = $today->locale($notifiable->settings[User::SETTING_LANGUAGE])->isoFormat('ddd MMM DD YYYY');

            return $today->isSameAs($toArray['dates'][0])
                && $toMail->subject === __('New order possibilities')
                && in_array(__('New order possibility for :day', ['day' => $day]), $toMail->introLines)
                && $toMail->actionText === __('Click here for more details');
        });
    }
}
