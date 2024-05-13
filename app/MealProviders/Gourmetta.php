<?php

namespace App\MealProviders;

use App\MealInfo;
use App\MealProviders\Interfaces\HasWeeklyOrders;
use App\Order;
use App\OrderItem;
use DiDom\Document;
use DiDom\Element;
use DiDom\Exceptions\InvalidSelectorException;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\URL;
use Ixudra\Curl\Facades\Curl;
use Symfony\Component\HttpFoundation\Response;

class Gourmetta extends AbstractMealProvider implements HasWeeklyOrders
{

    const LOGIN_URL = '/login';
    private string $baseUrl = 'https://bestellung-rest.gourmetta.de';
    private string $token = '';
    private string $userId = '';

    public function supportsAutoOrder(): bool
    {
        return true;
    }

    public function supportsOrderUpdate(): bool
    {
        return true;
    }

    /**
     * @param Carbon $date
     *
     * @throws InvalidSelectorException
     */
    public function getMealsDataForDate(Carbon $date): array
    {
        $this->login();

        $response = Curl::to($this->getOrderUrl())
            ->withBearer($this->token)
            ->asJsonResponse(true)
            ->withData(['from' => $date->toDateString(), 'to' => $date->toDateString()])
            ->get();

        return collect($response['orderDays'])
            ->flatten(3)
            ->filter(fn($item) => is_array($item))
            ->map(function ($meal) {
                $info = new MealInfo();
                $info->allergens = array_column($meal['allergens'], 'name');
                $info->tags = $meal['tags'];
                return [
                    'title' => $meal['categoryName'] . ' - ' . $meal['name'],
                    'description' => $meal['description'],
                    'price' => $meal['price'],
                    'external_id' => $meal['id'],
                    'info' => $info,
                ];
            })
            ->toArray();
    }

    private function login(): void
    {
        if ($this->token) {
            return;
        }

        if (!config('services.gourmetta.login') || !config('services.gourmetta.password')) {
            return;
        }

        $response = Curl::to($this->getUrl(self::LOGIN_URL))
            ->withBearer(null)
            ->withData(
                [
                    'login' => config('services.gourmetta.login'),
                    'password' => config('services.gourmetta.password'),
                ]
            )
            ->asJsonResponse()
            ->asJsonRequest()
            ->post();

        $this->token = $response->token;

        $tks = \explode('.', $this->token);
        list($headb64, $bodyb64, $cryptob64) = $tks;

        $decoded = json_decode(base64_decode($bodyb64));

        $this->userId = $decoded->userUuid;
    }

    private function getUrl($url)
    {
        return URL::format($this->baseUrl, $url);
    }

    /**
     * @return string
     */
    private function getOrderUrl(): string
    {
        return $this->getUrl("/users/" . $this->userId . "/order");
    }

    public function configureSchedule(Schedule $schedule): void
    {
        if (!config('services.gourmetta.schedule')) {
            return;
        }

        $schedule->call([$this, 'getAllUpcomingMeals'])->dailyAt('10:00');
        $schedule->call([$this, 'autoOrder'])->dailyAt('6:00');
    }

    public function getAllUpcomingMeals()
    {
        $date = today();

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

    /**
     * @throws InvalidSelectorException
     */
    public function updateOrder(Order $order)
    {
        $this->placeOrder($order);
    }

    public function placeOrder(Order $order)
    {
//        $this->login();

        $data =
            [
                'orderDays' => $order->orderItems->groupBy('meal.date')->map(
                    function ($orderItems, $date) {
                        /** @var OrderItem[]|Collection $orderItems */
                        return [
                            'date' => Carbon::parse($date)->toDateString(),
                            'orderedMeals' => $orderItems->groupBy('meal.external_id')->map(
                                function ($orderItems, $externalId) {
                                    return [
                                        'mealId' => $externalId,
                                        'quantity' => $orderItems->sum('quantity')
                                    ];
                                })->values()->toArray()
                        ];
                    }
                )->values()->toArray()
            ];


        $response = Curl::to($this->getOrderUrl())
            ->withBearer($this->token)
            ->asJson()
            ->withData($data)
            ->returnResponseObject()
            ->put();

        abort_if($response->status !== 200, 500, $response->content->errorSummary);

        $order->update(
            [
                'status' => Order::STATUS_ORDERED,
                'external_id' => $this->getOrderUrl(),
            ]
        );
    }
}
