<?php

namespace App\MealProviders;

use App\Exceptions\MealProviderNotConfiguredException;
use App\MealInfo;
use App\MealProviders\Interfaces\HasWeeklyOrders;
use App\Models\Order;
use App\Models\OrderItem;
use DiDom\Document;
use DiDom\Element;
use DiDom\Exceptions\InvalidSelectorException;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\URL;
use Ixudra\Curl\Facades\Curl;

class Holzke extends AbstractMealProvider implements HasWeeklyOrders
{

    private bool $isLoggedIn = false;

    private string $baseUrl = 'https://bestellung-holzke-menue.de';
    const LOGIN_URL = '/en/accounts/login/';
    const MEAL_URL = '/en/sammel/eb/';

    private string $cookieJar = '';

    public function getCookieJar(): string
    {
        if (!$this->cookieJar) {
            $this->cookieJar = tempnam(sys_get_temp_dir(), 'holzke_cookie');
        }

        return $this->cookieJar;
    }


    private function getUrl($url)
    {
        return URL::format($this->baseUrl, $url) . '/';
    }

    /**
     * @throws MealProviderNotConfiguredException
     */
    private function login(): void
    {
        if ($this->isLoggedIn) {
            return;
        }

        if (!config('services.holzke.login') || !config('services.holzke.password')) {
           throw new MealProviderNotConfiguredException('Holzke');
        }

        /** @var Element $response */
        $response = Curl::to($this->baseUrl)
            ->allowRedirect()
            ->setCookieJar($this->getCookieJar())
            ->withResponseHeaders()
            ->get();


        $csrf = $this->extractCsrfToken($response);


        $response = Curl::to($this->getUrl(self::LOGIN_URL))
            ->withData(
                [
                    'login' => config('services.holzke.login'),
                    'password' => config('services.holzke.password'),
                    'csrfmiddlewaretoken' => $csrf,
                ]
            )
            ->allowRedirect()
            ->setCookieFile($this->getCookieJar())
            ->setCookieJar($this->getCookieJar())
            ->withResponseHeaders()
            ->returnResponseObject()
            ->post();

        $this->baseUrl = 'https://' . parse_url($response->headers['location'][0], PHP_URL_HOST);

        $this->isLoggedIn = true;

    }

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
        return $this->parseResponse(
            $this->getHtml($date)
        );
    }

    /**
     * @param $response
     * @return array[]
     *
     * @throws InvalidSelectorException
     */
    private function parseResponse($response): array
    {
        $items = (new Document($response))->find('.menu-table tr');
        array_shift($items); //remove headings row
        $items = array_map(
            function ($mealElement) {
                $info = new MealInfo();
//                $info->calories = $this->extractCalories($mealElement);
//                $info->allergens = $this->extractAllergens($mealElement);

                $title = $this->extractTitle($mealElement);
                return [
                    'title' => $title,
                    'description' => $this->extractDescription($mealElement),
                    'price' => $this->extractPrice($mealElement),
                    'external_id' => $this->extractExternalId($mealElement) ?? $title,
                    'info' => $info,
                ];
            },
            $items
        );

        return collect($items)->whereNotNull('external_id')->whereNotNull('description')->toArray();
    }

    /**
     * @param Element $mealElement
     * @return float
     *
     * @throws InvalidSelectorException
     */
    private function extractCalories(Element $mealElement): float
    {
        return floatval($mealElement->first('.kcal')->firstChild()->text());
    }

    /**
     * @param Element $mealElement
     * @return string[]
     *
     * @throws InvalidSelectorException
     */
    private function extractAllergens(Element $mealElement): array
    {
        return array_map(function (Element $element) {
            return $element->getAttribute('title');
        }, $mealElement->first('.zusatz')->children());
    }

    /**
     * @param Element $mealElement
     * @return string
     *
     * @throws InvalidSelectorException
     */
    private function extractTitle(Element $mealElement): string
    {
        $title = $mealElement->first('.mealtitel')->firstChild();
        if (!$title) {
            return '';
        }

        $title = $title->text();
        preg_match('/[\w\s]*/mu', $title, $titleMatch);

        return trim($titleMatch[0]);
    }

    /**
     * @param Element $mealElement
     * @return string
     *
     * @throws InvalidSelectorException
     */
    private function extractDescription(Element $mealElement): string
    {
        $mealText = $mealElement->first('#mealtext');
        if (!$mealText) {
            return '';
        }

        $description = $mealText->firstChild();
        if (!$description) {
            return '';
        }

        if ($description->text() === ';') {
            return '';
        }

        return trim($description->text());
    }

    /**
     * @param Element $mealElement
     * @return int
     *
     * @throws InvalidSelectorException
     */
    private function extractPrice(Element $mealElement): int
    {
        $priceElement = $mealElement->first('price');
        if (!$priceElement) {
            return 0;
        }

        $priceText = $priceElement->text();
        $price = preg_replace('/\D*/', '', $priceText) ?? 0;

        return intval($price);
    }

    private function extractExternalId(Element $mealElement): string|null
    {
        $externalId = $mealElement->first('input');
        if (!$externalId) {
            return null;
        }

        return trim($externalId->getAttribute('name'));
    }

    /**
     * @param Carbon $date
     * @return string
     */
    public function getHtml(Carbon $date): string
    {
        $this->login();

        return Curl::to($this->getOrderingUrl($date, $date))
            ->setCookieFile($this->getCookieJar())
            ->get();
    }

    private function getOrderingUrl(Carbon $dateStart, Carbon $dateEnd): string
    {
        $response = Curl::to($this->getUrl(self::MEAL_URL))
            ->setCookieFile($this->getCookieJar())
            ->setCookieJar($this->getCookieJar())
            ->withResponseHeaders()
            ->returnResponseObject()
            ->allowRedirect()
            ->get();

        $orderingUrl = $response->headers['location'];

        $urlParts = explode(
            '/',
            trim(
                $orderingUrl,
                '/',
            ),
        );

        array_pop($urlParts);
        array_pop($urlParts);
        $urlParts[] = $dateStart->toDateString();
        $urlParts[] = $dateEnd->toDateString();

        return $this->getUrl(join('/', $urlParts));
    }

    private function getCsrf(string $url): string
    {
        $response = Curl::to($url)
            ->setCookieFile($this->getCookieJar())
            ->setCookieJar($this->getCookieJar())
            ->allowRedirect()
            ->get();

        return $this->extractCsrfToken($response);
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

    /**
     * @throws InvalidSelectorException
     */
    public function updateOrder(Order $order)
    {
        $this->placeOrder($order);
    }

    /**
     * @param string $external_id
     */
    private function setOrderForEdit(string $external_id): void
    {
        Curl::to('https://holzke-menue.de/ajax/updateMealCount.php')
            ->withData(['vid' => $external_id])
            ->returnResponseObject()
            ->setCookieFile($this->getCookieJar())
            ->post();
    }

    public function placeOrder(Order $order)
    {
        $this->login();

        /** @var Carbon $startDate */
        $startDate = $order->meals()->orderBy('date')->value('date');
        $startDate = $startDate->startOfWeek();
        /** @var Carbon $endDate */
        $endDate = $order->meals()->orderByDesc('date')->value('date');
        $endDate = $endDate->endOfWeek();

        $orderingUrl = $this->getOrderingUrl($startDate, $endDate);
        $csrfToken = $this->getCsrf($orderingUrl);

        $data = $order->orderItems->groupBy('meal.external_id')->mapWithKeys(function ($orderItems, $externalId) {
            /** @var OrderItem[]|Collection $orderItems */
            return [$externalId => $orderItems->sum('quantity')];
        });

        $data['change_order'] = 'Weiter';
        $data['csrfmiddlewaretoken'] = $csrfToken;

        $response = Curl::to($orderingUrl)
            ->withData($data->toArray())
            ->setCookieFile($this->getCookieJar())
            ->setCookieJar($this->getCookieJar())
            ->returnResponseObject()
            ->withResponseHeaders()
            ->allowRedirect()
            ->post();

        $confirmationCsrfToken = $this->extractCsrfToken($response->content);

        Curl::to($this->getUrl($response->headers['location']))
            ->withData(
                [
                    'csrfmiddlewaretoken' => $confirmationCsrfToken,
                    'order' => 'order'
                ]
            )
            ->setCookieFile($this->getCookieJar())
            ->post();


        $order->update(
            [
                'status' => Order::STATUS_ORDERED,
                'external_id' => $orderingUrl,
            ]
        );
    }

    private function extractCsrfToken(string $response): string
    {
        return (new Document($response))->first('[name=csrfmiddlewaretoken]')->attr('value');
    }


}
