<?php

namespace App\Console\Commands;

use App\MealProviders\CallAPizzaMealProvider;
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
    protected $signature = 'import:call-a-pizza
                            {date=today}
                            {--notify : Send notifications}';

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
     * @param CallAPizzaMealProvider $provider
     */
    public function handle(MealService $mealService)
    {
        $mealService
            ->setProvider(app(CallAPizzaMealProvider::class))
            ->getMealsForDate(
                Carbon::parse(
                    $this->argument('date')
                )
            );

        if ($this->option('notify')) {
            $mealService->notify();
        }
    }
}
