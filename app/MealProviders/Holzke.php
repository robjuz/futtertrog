<?php

namespace App\MealProviders;

use App\Exceptions\MealProviderNotConfiguredException;
use App\MealInfo;
use App\MealProviders\Interfaces\HasWeeklyOrders;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use stdClass;

/**
 * Holzke Menü ordering via the RCS "Menüplaner" JSON API that backs
 * https://bestellung-holzke-menue.de (Angular app).
 *
 * Every request is a POST of {command, client, parameter[, token]} to /login or
 * /call. Responses look like {code, content, token}; the token rotates with every
 * authenticated response. Errors are returned as non-2xx HTTP responses.
 */
class Holzke extends AbstractMealProvider implements HasWeeklyOrders
{
    public const API_URL = 'https://proxy.mms-rcs.de';
    public const MANDANT_ID = 'SuperHolzke';
    private const CLIENT = 'web';

    private ?string $token = null;
    private ?string $mandantId = null;
    private ?string $bestellerId = null;
    private ?Carbon $loggedInAt = null;

    public function supportsAutoOrder(): bool
    {
        return true;
    }

    public function supportsOrderUpdate(): bool
    {
        return true;
    }

    public function getMealsDataForDate(Carbon $date): array
    {
        return $this->parseMealPlan($this->getMealPlan($date), $date);
    }

    /**
     * Raw "speiseplan/kundeAPI_1_5_1" content for a single day.
     */
    public function getMealPlan(Carbon $date): array
    {
        $this->login();

        return $this->call('speiseplan/kundeAPI_1_5_1', [
            'mandantId' => $this->mandantId,
            'von' => $date->toDateString(),
            'bis' => $date->toDateString(),
            'bestellerId[0]' => $this->bestellerId,
        ]);
    }

    private function parseMealPlan(array $mealPlan, Carbon $date): array
    {
        $menuTexts = $mealPlan['menuetexte'] ?? [];
        $meals = [];

        foreach ($mealPlan['kunden'] ?? [] as $customer) {
            $day = $customer['speisePlanTage'][$date->toDateString()] ?? null;

            if (!$day || !empty($day['zustellPause'])) {
                continue;
            }

            foreach ($day['tagesMenues'] ?? [] as $menu) {
                $menuTextId = $menu['menueTextId'] ?? null;
                $menuText = $menuTexts[$menuTextId] ?? null;

                if (!$menuTextId || !$menuText || !empty($menuText['gesperrt'])) {
                    continue;
                }

                $description = $this->extractDescription($menuText['menueText'] ?? '');
                if ($description === '') {
                    continue;
                }

                $meals[$menuTextId] = [
                    'title' => $this->normalizeWhitespace($menuText['bezeichnung'] ?? $menu['bezeichnung'] ?? ''),
                    'description' => $description,
                    'price' => (int)round(($menu['preis']['betrag'] ?? 0) * 100),
                    'external_id' => $menuTextId,
                    'info' => $this->extractInfo($menuText['menueText']),
                ];
            }
        }

        return array_values($meals);
    }

    /**
     * Menu texts look like "Dish description[br][br][h]allergens[/h][br][h]123,4 kcal; 1,2 BE[/h]…"
     */
    private function extractDescription(string $menuText): string
    {
        $description = preg_split('/\[br]|\[h]/i', $menuText)[0] ?? '';

        return $this->normalizeWhitespace($description);
    }

    private function extractInfo(string $menuText): MealInfo
    {
        $info = new MealInfo();

        preg_match_all('/\[h](.*?)\[\/h]/is', $menuText, $highlights);

        foreach ($highlights[1] as $highlight) {
            if (preg_match('/([\d.,]+)\s*kcal/i', $highlight, $kcal)) {
                $info->calories = (float)str_replace(',', '.', str_replace('.', '', $kcal[1]));
            } elseif (preg_match('/^\s*[\dA-Z]{1,3}(\s*,\s*[\dA-Z]{1,3})*\s*$/', $highlight)) {
                $info->allergens = array_map('trim', explode(',', $highlight));
            }
        }

        return $info;
    }

    private function normalizeWhitespace(string $text): string
    {
        return trim(preg_replace('/\s+/u', ' ', $text));
    }

    public function configureSchedule(Schedule $schedule): void
    {
        if (!config('services.holzke.schedule')) {
            return;
        }

        $schedule->call([$this, 'getAllUpcomingMeals'])->dailyAt('10:00');
        $schedule->call([$this, 'autoOrder'])->weekdays()->at('6:00');
    }

    public function getAllUpcomingMeals()
    {
        $date = today();

        if ($date->isWeekend()) {
            $date->addWeekday();
        }

        while ($this->createMealsDataForDate(Carbon::parse($date))) {
            $date->addWeekday();
        }

        $this->notifyAboutNewOrderPossibilities();
    }

    public function autoOrder(): void
    {
        $order = $this->getOrder(now());

        $order->autoOrder();
    }

    public function updateOrder(Order $order)
    {
        $this->placeOrder($order);
    }

    /**
     * Synchronises all Holzke menus of the order's week(s) with the order: menus
     * contained in the order get their summed quantity, every other menu of those
     * days is set to 0.
     */
    public function placeOrder(Order $order)
    {
        $this->login();

        $startDate = Carbon::parse($order->meals()->orderBy('date')->value('date'))->startOfWeek();
        $endDate = Carbon::parse($order->meals()->orderByDesc('date')->value('date'))->endOfWeek();

        $quantities = $order->orderItems->groupBy('meal.external_id')->map(function ($orderItems) {
            /** @var OrderItem[]|Collection $orderItems */
            return (int)$orderItems->sum('quantity');
        });

        $orders = $this->call('bestellungen/getAPI_1_5', [
            'mandantId' => $this->mandantId,
            'von' => $startDate->toDateString(),
            'bis' => $endDate->toDateString(),
            'bestellerId[0]' => $this->bestellerId,
        ]);

        $days = $orders['kunden'][$this->bestellerId]['tage'] ?? [];

        $knownMenuTextIds = collect($days)->flatMap(
            fn(array $day) => collect($day['bestellMenues'] ?? [])->pluck('menueTextId')
        );
        $unknown = $quantities->keys()->diff($knownMenuTextIds);
        if ($unknown->isNotEmpty()) {
            throw new RuntimeException('Holzke: meals not orderable: ' . $unknown->join(', '));
        }

        ['deadlines' => $deadlines, 'texts' => $menuTexts] = $this->getMenuDetails($startDate, $endDate);

        foreach ($days as $date => $day) {
            foreach ($day['bestellMenues'] ?? [] as $number => $menu) {
                $old = (int)$menu['mengeAlt'];
                $new = $quantities->get($menu['menueTextId'], 0);
                $deadline = $deadlines[$menu['menueTextId']][$new > $old ? 'order' : 'cancel'] ?? null;

                // Holzke rejects changes after the (cancellation) deadline; menus
                // without a known deadline (past days, holidays) are left untouched.
                if ($new !== $old && (!$deadline || $deadline->isPast())) {
                    if ($quantities->has($menu['menueTextId'])) {
                        Log::warning('Holzke: quantity change skipped, deadline passed', [
                            'date' => $date,
                            'menueTextId' => $menu['menueTextId'],
                            'ordered' => $old,
                            'requested' => $new,
                        ]);
                    }
                    $new = $old;
                }

                $days[$date]['bestellMenues'][$number]['mengeNeu'] = $new;
            }
        }

        $result = $this->call('bestellungen/commit', $this->orderChangesParameters($days, $menuTexts));

        $this->assertNoOrderErrors($result);

        $order->update(
            [
                'status' => Order::STATUS_ORDERED,
                'external_id' => $startDate->toDateString() . '/' . $endDate->toDateString(),
            ]
        );
    }

    /**
     * Order/cancellation deadlines and menu texts (in the commit payload format)
     * per menu text id.
     *
     * @return array{deadlines: array<string, array{order: ?Carbon, cancel: ?Carbon}>, texts: array<string, array>}
     */
    private function getMenuDetails(Carbon $startDate, Carbon $endDate): array
    {
        $mealPlan = $this->call('speiseplan/kundeAPI_1_5_1', [
            'mandantId' => $this->mandantId,
            'von' => $startDate->toDateString(),
            'bis' => $endDate->toDateString(),
            'bestellerId[0]' => $this->bestellerId,
        ]);

        $deadlines = [];
        foreach ($mealPlan['kunden'] ?? [] as $customer) {
            foreach ($customer['speisePlanTage'] ?? [] as $day) {
                foreach ($day['tagesMenues'] ?? [] as $menu) {
                    if (empty($menu['menueTextId'])) {
                        continue;
                    }
                    $deadlines[$menu['menueTextId']] = [
                        'order' => isset($menu['bestellschluss']) ? Carbon::parse($menu['bestellschluss']) : null,
                        'cancel' => isset($menu['abbestellschluss']) ? Carbon::parse($menu['abbestellschluss']) : null,
                    ];
                }
            }
        }

        $texts = [];
        foreach ($mealPlan['menuetexte'] ?? [] as $id => $text) {
            $texts[$id] = [
                'bezeichnung' => $text['bezeichnung'] ?? '',
                'id' => $id,
                'kurzbezeichnung' => $text['kurzBez'] ?? '',
                'menueNr' => $text['menueNr'] ?? null,
                'text' => $text['menueText'] ?? '',
            ];
        }

        return ['deadlines' => $deadlines, 'texts' => $texts];
    }

    /**
     * Parameters for "bestellungen/validate" and "bestellungen/commit", shaped
     * like the web app's single-orderer flow.
     *
     * The texts of changed menus must be included: without them Holzke applies
     * new orders but answers with HTTP 406.
     */
    private function orderChangesParameters(array $days, array $menuTexts = []): array
    {
        ksort($days);

        $changedTexts = [];
        foreach ($days as $day) {
            foreach ($day['bestellMenues'] ?? [] as $menu) {
                $id = $menu['menueTextId'] ?? null;
                if ($id && isset($menuTexts[$id]) && (int)$menu['mengeNeu'] !== (int)$menu['mengeAlt']) {
                    $changedTexts[$id] = $menuTexts[$id];
                }
            }
        }

        return [
            'mandantId' => $this->mandantId,
            'bestellerId' => $this->bestellerId,
            'bestellaenderungen' => json_encode([
                'kunden' => [
                    $this->bestellerId => [
                        'von' => array_key_first($days),
                        'bis' => array_key_last($days),
                        'tage' => $days,
                        'error' => false,
                        'gesamtPreis' => ['betrag' => 0, 'waehrung' => 'EUR'],
                        'bestellBetrag' => ['betrag' => 0, 'waehrung' => 'EUR'],
                    ],
                ],
                'menuetexte' => $changedTexts ?: new stdClass(),
            ]),
        ];
    }

    private function assertNoOrderErrors(array $result): void
    {
        if (!isset($result['kunden'][$this->bestellerId])) {
            throw new RuntimeException('Holzke: unexpected order response.');
        }

        $customer = $result['kunden'][$this->bestellerId];
        $errors = [];

        if (!empty($customer['error'])) {
            $errors[] = 'order';
        }

        foreach ($customer['tage'] ?? [] as $date => $day) {
            foreach ($day['bestellMenues'] ?? [] as $menu) {
                if (!empty($menu['error'])) {
                    $errors[] = "{$date} {$menu['menueTextId']}";
                }
            }
        }

        if ($errors) {
            throw new RuntimeException('Holzke: order rejected for ' . implode(', ', $errors));
        }
    }

    /**
     * @throws MealProviderNotConfiguredException
     */
    private function login(): void
    {
        if ($this->token && $this->loggedInAt?->gt(now()->subMinutes(10))) {
            return;
        }

        $this->token = null;

        if (!config('services.holzke.login') || !config('services.holzke.password')) {
            throw new MealProviderNotConfiguredException('Holzke');
        }

        $content = $this->request('/login', 'kunde/LoginAPI_1_5_1', [
            'mandantId' => self::MANDANT_ID,
            'login' => config('services.holzke.login'),
            'passwd' => config('services.holzke.password'),
            'lang' => 'de',
        ]);

        if (empty($content['mandantId']) || empty($content['bestellerId']) || !$this->token) {
            $this->token = null;
            throw new RuntimeException('Holzke: unexpected login response.');
        }

        $this->mandantId = $content['mandantId'];
        $this->bestellerId = $content['bestellerId'];
        $this->loggedInAt = now();
    }

    private function call(string $command, array $parameters): array
    {
        try {
            return $this->request('/call', $command, $parameters);
        } catch (RuntimeException $exception) {
            $status = $exception->getPrevious() instanceof RequestException
                ? $exception->getPrevious()->response->status()
                : null;

            if (!in_array($status, [401, 403], true)) {
                throw $exception;
            }

            // session expired: log in again and retry once
            $this->token = null;
            $this->login();

            return $this->request('/call', $command, $parameters);
        }
    }

    private function request(string $endpoint, string $command, array $parameters): array
    {
        $payload = [
            'command' => $command,
            'client' => self::CLIENT,
            'parameter' => $parameters,
        ];

        if ($endpoint === '/call') {
            $payload['token'] = $this->token;
        }

        try {
            $response = Http::acceptJson()
                ->timeout(30)
                ->post(self::API_URL . $endpoint, $payload)
                ->throw();
        } catch (RequestException $exception) {
            throw new RuntimeException(
                "Holzke: '{$command}' failed with HTTP {$exception->response->status()}: "
                . mb_substr($exception->response->body(), 0, 500),
                previous: $exception
            );
        }

        $json = $response->json();

        if (!is_array($json) || !array_key_exists('content', $json)) {
            throw new RuntimeException("Holzke: unexpected response for '{$command}'.");
        }

        if (!empty($json['token'])) {
            $this->token = $json['token'];
        }

        return is_array($json['content']) ? $json['content'] : [];
    }
}
