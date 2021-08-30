<?php

namespace App\Console\Commands;

use App\MealProviders\HolzkeMealProvider;
use App\Services\MealService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

/**
 * Class Holzke.
 */
class Holzke extends Command
{
    private MealService $mealService;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'import:holzke
                            {--date= : Import meals just for a specific day}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import meals from Holzke';

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
     * @param MealService $mealService
     */
    public function handle(MealService $mealService)
    {
        $this->mealService = $mealService->setProvider(app(HolzkeMealProvider::class));

        if ($this->hasOption('date')) {
            $this->importForDate($this->option('date'));
        } else {
            $this->importAll();
        }

        if ($this->hasOption('notify')) {
            $this->mealService->notify();
        }
    }


    private function importAll() {
        $date = today();

        if ($date->isWeekend()) {
            $date->addWeekday();
        }

        while($this->importForDate($date)) {
            $date->addWeekday();
        }

    }

    private function importForDate($date) {
        return $this->mealService->getMealsForDate(Carbon::parse($date));
    }
}
