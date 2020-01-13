<?php

namespace Tests\Feature;

use App\Events\NewOrderPossibility;
use App\Meal;
use App\Services\CallAPizzaService;
use App\Services\HolzkeService;
use Illuminate\Support\Facades\Event;
use Ixudra\Curl\Facades\Curl;
use Mockery;
use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Mockery\MockInterface;

class HolzkeTest extends TestCase
{
    /** @test */
    public function it_can_create_meals_from_holzke_html()
    {
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
        });

        $meals = app(HolzkeService::class)->getMealsForDate(today());

        $this->assertCount(1, $meals);
    }

    /** @test */
    public function it_resolves_a_holzke_provider_from_app_container()
    {
        $this->assertInstanceOf(HolzkeService::class, app(HolzkeService::class));
        $this->assertSame(app(HolzkeService::class), app(HolzkeService::class));
    }

    /** @test */
    public function admin_can_trigger_the_holzke_import()
    {
        $this->instance('Holzke_service', Mockery::mock(HolzkeService::class, function ($mock) {
            $mock->shouldReceive('getMealsForDate')
                ->withAnyArgs()
                ->andReturn(factory(Meal::class, 1)->raw())
                ->once();
        }));

        Carbon::setTestNow(today()->addWeekday()); //ensure we test over a weekday
        $today = today();

        $this->withExceptionHandling();

        $this->login()
            ->post(route('meals.import'))
            ->assertForbidden();

        $this->assertEquals(0, Meal::count());

        $this->loginAsAdmin()
            ->post(route('meals.import'), [
                'date' => $today,
                'provider' => Meal::PROVIDER_HOLZKE
            ]);

        $this->assertEquals(1, Meal::count());
    }
}
