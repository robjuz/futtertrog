<?php

namespace Tests\Feature;

use App\Events\NewOrderPossibility;
use App\Meal;
use App\Services\CallAPizzaService;
use Illuminate\Support\Facades\Event;
use Ixudra\Curl\Facades\Curl;
use Mockery;
use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

class HolzkeTest extends TestCase
{
    /** @test */
    public function it_can_create_meals_from_holzke_html()
    {
        Event::fake();

        $holzkeServiceMock = Mockery::mock(CallAPizzaService::class);
        $holzkeServiceMock->shouldReceive('getMealsForDate')
            ->withAnyArgs()
            ->andReturn('<article class="articleGrid meal"><div class="cHead"><h2>Menü 1 blank (3,05 €)</h2></div>
							<div class="cBody grey">
								Cremige Tomatensuppe mit Reiseinlage und Fleischklösschen dazu 1 Scheibe Weißbrot
								<div class="infos clearfix"><span class="kcal">547.7 kcal</span><span class="be">5.9 BE</span><span class="zusatz"><a href="#zusatz" title="mit Farbstoff">1</a><a href="#zusatz" title="enth. Gluten">A</a><a href="#zusatz" title="enth. Ei">C</a><a href="#zusatz" title="Milch, Laktose">G</a><a href="#zusatz" title="enth. Sellerie">I</a><a href="#zusatz" title="enth. Senf">J</a><a href="#zusatz" title="enth. Weizen">A1</a></span></div>
							</div>
						</article>')
            ->once();

        $holzkeServiceMock->shouldReceive('getMealsForDate')
            ->withAnyArgs()
            ->andReturn('<div></div>')
            ->once();



        $this->app->instance(CallAPizzaService::class, $holzkeServiceMock);

        $this->artisan('import:holzke');

        $this->assertEquals(1, Meal::count());

        Event::assertDispatched(NewOrderPossibility::class);
    }

    /** @test */
    public function it_resolves_a_holzke_provider_from_app_container()
    {
        $this->assertInstanceOf(CallAPizzaService::class, app(CallAPizzaService::class));
        $this->assertSame(app(CallAPizzaService::class), app(CallAPizzaService::class));
    }
}
