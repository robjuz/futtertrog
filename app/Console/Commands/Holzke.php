<?php

namespace App\Console\Commands;

use App\Events\NewOrderPossibility;
use App\Meal;
use DiDom\Document;
use Illuminate\Console\Command;
use Ixudra\Curl\Facades\Curl;

/**
 * Class Holzke.
 *
 * @codeCoverageIgnore
 */
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
                'is_send' => 'login',
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
            $createdMealsCount = 0;

            foreach ($meals as $meal) {
                $title = $meal->find('h2')[0]->text();

                preg_match('/^[\w\s]*/mu', $title, $titleMatch);
                preg_match('/\((\S*)/', $title, $priceMatch);

                $meal = Meal::updateOrCreate([
                    'title' => trim($titleMatch[0]),
                    'date_from' => $date->toDateString(),
                    'date_to' => $date->toDateString(),
                    'provider' => Meal::PROVIDER_HOLZKE,
                ], [
                    'description' => trim($meal->find('.cBody')[0]->removeChildren()[0]->text()),
                    'price' => floatval(str_replace(',', '.', $priceMatch[1])),
                ]);

                if ($meal->wasRecentlyCreated) {
                    $createdMealsCount++;
                }
            }

            if ($createdMealsCount > 0) {
                event(new NewOrderPossibility($date));
            }

            $date->addWeekday();
        } while (count($meals));
    }
}
