<?php

namespace Tests\Feature;

use App\Events\NewOrderPossibilities;
use App\Events\NewOrderPossibility;
use App\Models\Meal;
use App\Notifications\NewOrderPossibilities as NewOrderPossibilitiesNotification;
use App\MealProviders\CallAPizza;
use App\MealProviders\Holzke;
use App\Models\Order;
use App\Providers\MealProvidersServiceProvider;
use App\Services\MealService;
use App\Models\User;
use App\UserSettings;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\Request;
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
    private const BESTELLER_ID = '105094-holzke-abc';
    private const MANDANT_ID = '105094-holzke';

    private static function mealPlan(Carbon $date, ?array $menus = null): array
    {
        $day = $date->toDateString();
        $menus ??= [
            '1' => ['Menü 1   blank', 'Lauch-Käse-Suppe mit Porree dazu 1 Scheibe Weißbrot[br][br][h]1, A, G, A1[/h][br][h]1.327,6 kcal; 1,9 BE[/h]', 5.45, false],
            '2' => ['Menü 2   schwarz', 'Butterspirelli mit Jagdwurst[br][br][h]2, 3, A[/h]', 5.95, false],
            '3' => ['Menü 3   rot', '', 7.55, false],
            '4' => ['Menü 4   blau', 'Gesperrtes Gericht', 6.30, true],
        ];

        $tagesMenues = [];
        $menuetexte = [];
        foreach ($menus as $nr => [$title, $text, $price, $locked]) {
            $id = "{$date->timestamp}_{$nr}";
            $tagesMenues[$nr] = [
                'menueTextId' => $id,
                'preis' => ['betrag' => $price, 'waehrung' => 'EUR'],
                'bezeichnung' => $title,
                'kurzBez' => "M{$nr}",
            ];
            $menuetexte[$id] = [
                'bezeichnung' => $title,
                'gesperrt' => $locked,
                'menueText' => $text,
                'menueNr' => (int)$nr,
            ];
        }

        return [
            'kunden' => [
                self::BESTELLER_ID => [
                    'bestellerId' => self::BESTELLER_ID,
                    'speisePlanTage' => [
                        $day => ['datum' => $day, 'zustellPause' => false, 'tagesMenues' => $tagesMenues],
                    ],
                ],
            ],
            'menuetexte' => $menuetexte,
        ];
    }

    private static function apiResponse(array $content, string $token = 'token-next'): array
    {
        return ['code' => 7102, 'content' => $content, 'token' => $token];
    }

    private function fakeApi(array $callResponses): void
    {
        config(['services.holzke.login' => 'user', 'services.holzke.password' => 'secret']);

        Http::fake([
            Holzke::API_URL . '/login' => Http::response(self::apiResponse([
                'mandantId' => self::MANDANT_ID,
                'bestellerId' => self::BESTELLER_ID,
            ], 'token-login')),
            Holzke::API_URL . '/call' => Http::sequence($callResponses),
        ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_can_create_meals_from_the_holzke_meal_plan()
    {
        $date = today();
        $holzkeServiceMock = Mockery::mock(Holzke::class)->makePartial();

        $holzkeServiceMock->shouldReceive('getMealPlan')
            ->withAnyArgs()
            ->andReturn(self::mealPlan($date))
            ->once();

        $meals = $holzkeServiceMock->getMealsDataForDate($date);

        $this->assertCount(2, $meals);
        $this->assertSame('Menü 1 blank', $meals[0]['title']);
        $this->assertSame('Lauch-Käse-Suppe mit Porree dazu 1 Scheibe Weißbrot', $meals[0]['description']);
        $this->assertSame(545, $meals[0]['price']);
        $this->assertSame("{$date->timestamp}_1", $meals[0]['external_id']);
        $this->assertSame(1327.6, $meals[0]['info']->calories);
        $this->assertSame(['1', 'A', 'G', 'A1'], $meals[0]['info']->allergens);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_logs_in_and_fetches_the_meal_plan_from_the_api()
    {
        $date = today();
        $this->fakeApi([Http::response(self::apiResponse(self::mealPlan($date)))]);

        $meals = (new Holzke())->getMealsDataForDate($date);

        $this->assertCount(2, $meals);

        Http::assertSent(fn(Request $request) => $request->url() === Holzke::API_URL . '/login'
            && $request['command'] === 'kunde/LoginAPI_1_5_1'
            && $request['parameter']['mandantId'] === Holzke::MANDANT_ID
            && $request['parameter']['login'] === 'user'
            && $request['parameter']['passwd'] === 'secret');

        Http::assertSent(fn(Request $request) => $request->url() === Holzke::API_URL . '/call'
            && $request['command'] === 'speiseplan/kundeAPI_1_5_1'
            && $request['token'] === 'token-login'
            && $request['parameter']['mandantId'] === self::MANDANT_ID
            && $request['parameter']['bestellerId[0]'] === self::BESTELLER_ID
            && $request['parameter']['von'] === $date->toDateString());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_throws_a_descriptive_exception_when_the_api_fails()
    {
        config(['services.holzke.login' => 'user', 'services.holzke.password' => 'secret']);
        Http::fake([Holzke::API_URL . '/login' => Http::response(['content' => ['name' => 'login_fehlgeschlagen']], 401)]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage("Holzke: 'kunde/LoginAPI_1_5_1' failed with HTTP 401");

        (new Holzke())->getMealsDataForDate(today());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_places_an_order_by_syncing_the_quantities_of_the_week()
    {
        Carbon::setTestNow(Carbon::parse('2026-11-11 08:00'));
        $monday = Carbon::parse('2026-11-09');
        $tuesday = Carbon::parse('2026-11-10');
        $wednesday = Carbon::parse('2026-11-11');

        $user = User::factory()->create();
        $meal = Meal::factory()->create([
            'provider' => app(Holzke::class),
            'date' => $wednesday,
            'external_id' => "{$wednesday->timestamp}_2",
        ]);
        $orderItem = $user->order($meal);
        $orderItem->update(['quantity' => 2]);
        $order = $orderItem->order;

        $menu = fn(Carbon $date, int $nr, int $mengeAlt) => [
            'menueId' => $nr, 'menueTextId' => "{$date->timestamp}_{$nr}", 'menueNr' => $nr,
            'mengeAlt' => $mengeAlt, 'mengeNeu' => $mengeAlt, 'error' => false,
        ];
        $existing = ['kunden' => [self::BESTELLER_ID => ['tage' => [
            $monday->toDateString() => ['datum' => $monday->toDateString(), 'bestellMenues' => ['1' => $menu($monday, 1, 1)]],
            $wednesday->toDateString() => ['datum' => $wednesday->toDateString(), 'bestellMenues' => [
                '1' => $menu($wednesday, 1, 1),
                '2' => $menu($wednesday, 2, 0),
            ]],
        ]]]];

        $deadline = fn(Carbon $date, int $nr, string $at) => [
            'menueTextId' => "{$date->timestamp}_{$nr}", 'bestellschluss' => $at, 'abbestellschluss' => $at,
        ];
        $deadlines = ['menuetexte' => [
            "{$wednesday->timestamp}_2" => ['bezeichnung' => 'Menü 2', 'kurzBez' => 'M2', 'menueNr' => 2, 'menueText' => 'Spirelli'],
        ], 'kunden' => [self::BESTELLER_ID => ['speisePlanTage' => [
            $monday->toDateString() => ['tagesMenues' => ['1' => $deadline($monday, 1, '2026-11-09T07:30:00+0100')]],
            $tuesday->toDateString() => ['tagesMenues' => ['1' => $deadline($tuesday, 1, '2026-11-10T07:30:00+0100')]],
            $wednesday->toDateString() => ['tagesMenues' => [
                '1' => $deadline($wednesday, 1, '2026-11-11T09:30:00+0100'),
                '2' => $deadline($wednesday, 2, '2026-11-11T09:30:00+0100'),
            ]],
        ]]]];

        $this->fakeApi([
            Http::response(self::apiResponse($existing, 'token-2')),
            Http::response(self::apiResponse($deadlines, 'token-3')),
            Http::response(self::apiResponse(['kunden' => [self::BESTELLER_ID => ['error' => false, 'tage' => []]]])),
        ]);

        app(Holzke::class)->placeOrder($order);

        Http::assertSent(fn(Request $request) => ($request['command'] ?? null) === 'bestellungen/getAPI_1_5'
            && $request['parameter']['von'] === '2026-11-09'
            && $request['parameter']['bis'] === '2026-11-15');

        Http::assertSent(function (Request $request) use ($monday, $wednesday) {
            if (($request['command'] ?? null) !== 'bestellungen/commit') {
                return false;
            }
            $changes = json_decode($request['parameter']['bestellaenderungen'], true);
            $days = $changes['kunden'][self::BESTELLER_ID]['tage'];

            return $request['token'] === 'token-3'
                && $request['parameter']['bestellerId'] === self::BESTELLER_ID
                && $changes['kunden'][self::BESTELLER_ID]['von'] === $monday->toDateString()
                && $changes['kunden'][self::BESTELLER_ID]['bis'] === $wednesday->toDateString()
                // monday is past its deadline and therefore left unchanged
                && $days[$monday->toDateString()]['bestellMenues']['1']['mengeNeu'] === 1
                && $days[$wednesday->toDateString()]['bestellMenues']['1']['mengeNeu'] === 0
                && $days[$wednesday->toDateString()]['bestellMenues']['2']['mengeNeu'] === 2
                && array_keys($changes['menuetexte']) === ["{$wednesday->timestamp}_2"]
                && $changes['menuetexte']["{$wednesday->timestamp}_2"]['text'] === 'Spirelli';
        });

        $this->assertEquals(Order::STATUS_ORDERED, $order->fresh()->status);
        $this->assertSame('2026-11-09/2026-11-15', $order->fresh()->external_id);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_logs_in_again_when_the_session_expired()
    {
        $date = today();
        $this->fakeApi([
            Http::response(['content' => ['name' => 'token_ungueltig']], 401),
            Http::response(self::apiResponse(self::mealPlan($date))),
        ]);

        $meals = (new Holzke())->getMealsDataForDate($date);

        $this->assertCount(2, $meals);
        Http::assertSentCount(4);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_does_not_mark_the_order_as_ordered_when_holzke_rejects_it()
    {
        $date = Carbon::parse('2026-11-11');
        $user = User::factory()->create();
        $meal = Meal::factory()->create([
            'provider' => app(Holzke::class),
            'date' => $date,
            'external_id' => "{$date->timestamp}_1",
        ]);
        $user->order($meal);
        $order = $meal->orderItems()->first()->order;

        $day = ['datum' => $date->toDateString(), 'bestellMenues' => ['1' => [
            'menueTextId' => "{$date->timestamp}_1", 'mengeAlt' => 0, 'mengeNeu' => 0, 'error' => false,
        ]]];
        $rejected = $day;
        $rejected['bestellMenues']['1']['error'] = true;

        $this->fakeApi([
            Http::response(self::apiResponse(['kunden' => [self::BESTELLER_ID => ['tage' => [$date->toDateString() => $day]]]])),
            Http::response(self::apiResponse(['kunden' => []])),
            Http::response(self::apiResponse(['kunden' => [self::BESTELLER_ID => ['error' => false, 'tage' => [$date->toDateString() => $rejected]]]])),
        ]);

        try {
            app(Holzke::class)->placeOrder($order);
            $this->fail('Expected exception');
        } catch (\RuntimeException $exception) {
            $this->assertStringContainsString('Holzke: order rejected', $exception->getMessage());
        }

        $this->assertEquals(Order::STATUS_OPEN, $order->fresh()->status);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_resolves_a_holzke_provider_from_app_container()
    {
        config(['services.holzke.enabled' => true]);
        $this->app->register(MealProvidersServiceProvider::class, true);

        $this->assertInstanceOf(Holzke::class, app(Holzke::class));
        $this->assertSame(app(Holzke::class), app(Holzke::class));
    }

    #[\PHPUnit\Framework\Attributes\Test]
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
            $mock->shouldReceive('getMealPlan')
            ->withAnyArgs()
            ->andReturn(self::mealPlan(today()))
            ->once();

            $mock->shouldReceive('getMealPlan')
            ->withAnyArgs()
            ->andReturn([])
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

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_fires_a_event_when_new_meals_were_created()
    {
        Event::fake();

        Carbon::setTestNow(today()->addWeekday()); //ensure we test over a weekday

        $this->partialMock(Holzke::class, function (MockInterface $mock) {

            $mock->shouldReceive('getMealPlan')
                ->withAnyArgs()
                ->andReturn([])
                ->once();
        });

        app(Holzke::class)->getAllUpcomingMeals();

        Event::assertNotDispatched(NewOrderPossibilities::class);

        $this->partialMock(Holzke::class, function (MockInterface $mock) {
            $mock->shouldReceive('getMealPlan')
                ->withAnyArgs()
                ->andReturn(self::mealPlan(today()))
                ->once();

            $mock->shouldReceive('getMealPlan')
                ->withAnyArgs()
                ->andReturn([])
                ->once();
        });

        app(Holzke::class)->getAllUpcomingMeals();
    }

//    #[\PHPUnit\Framework\Attributes\Test]
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
