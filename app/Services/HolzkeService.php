<?php

namespace App\Services;

use Illuminate\Support\Carbon;
use Ixudra\Curl\Facades\Curl;

class HolzkeService
{
    protected $cookieJar = '';

    public function __construct()
    {
        $this->cookieJar = storage_path('holtzke_cookie.txt');
        $this->login();
    }

    private function login()
    {
        Curl::to('https://holzke-menue.de/de/speiseplan/erwachsenen-speiseplan/schritt-login.html')
            ->withData([
                'kdnr' => config('services.holzke.login'),
                'passwort' => config('services.holzke.password'),
                'is_send' => 'login',
            ])
            ->setCookieJar($this->cookieJar)
            ->post();
    }

    /**
     * @param \Illuminate\Support\Carbon $date
     * @return mixed
     */
    public function getMealsForDate(Carbon $date)
    {
        return Curl::to('https://holzke-menue.de/de/speiseplan/erwachsenen-speiseplan.html')
            ->withData(['t' => $date->timestamp])
            ->setCookieFile(storage_path('holtzke_cookie.txt'))
            ->get();
    }
}