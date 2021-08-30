<?php

namespace App\Console\Commands;

use App\MealProviders\CallAPizzaService;
use App\Services\MealService;
use DiDom\Exceptions\InvalidSelectorException;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

/**
 * Class CallAPizza.
 */
class CallAPizza extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'import:call-a-pizza {date=today}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import meals from Call a Pizza';

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
     * @param CallAPizzaService $provider
     */
    public function handle(MealService $mealService)
    {
        $mealService
            ->setProvider(app(CallAPizzaService::class))
            ->getMealsForDate(
                Carbon::parse(
                    $this->argument('date')
                )
            );

        if ($this->hasOption('notify')) {
            $mealService->notify();
        }
    }
}
