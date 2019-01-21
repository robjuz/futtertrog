<?php

namespace App\Console\Commands;

use App\Meal;
use DiDom\Document;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Ixudra\Curl\Facades\Curl;

class Holzke extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'import:holzke';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import coming meals from Holzke';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        //Login
        Curl::to('https://holzke-menue.de/de/speiseplan/erwachsenen-speiseplan/schritt-login.html')
            ->withData([
                'kdnr' => config('services.holzke.login'),
                'passwort' => config('services.holzke.password'),
                'is_send' => 'login'
            ])
            ->setCookieJar(storage_path('holtzke_cookie.txt'))
            ->post();

        //get data
        $date = today();
        if ($date->isWeekend()) {
            $date->addWeekday();
        }

        do {

            $response = Curl::to('https://holzke-menue.de/de/speiseplan/erwachsenen-speiseplan.html')
                ->withData(['t' => $date->timestamp])
                ->setCookieFile(storage_path('holtzke_cookie.txt'))
                ->get();

            $document = new Document($response);

            $meals = $document->find('.meal');

            foreach ($meals as $meal) {
                $title = $meal->find('h2')[0]->text();

                preg_match('/^[\w\s]*/mu', $title, $titleMatch);
                preg_match('/\((\S*)/', $title, $priceMatch);

                Meal::updateOrCreate([
                    'title' => trim($titleMatch[0]),
                    'date' => $date
                ], [
                    'description' => trim($meal->find('.cBody')[0]->removeChildren()[0]->text()),
                    'price' => floatval(str_replace(',', '.', $priceMatch[1])),
                    'orderable_until' => $date->setTime('7', '30'),
                    'provider' => Meal::PROVIDER_HOLZKE
                ]);
            }

            $date->addWeekday();

        } while (count($meals));
    }
}
