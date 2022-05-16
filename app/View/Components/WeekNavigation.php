<?php

namespace App\View\Components;

use App\Repositories\MealsRepository;
use App\Repositories\OrdersRepository;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\Component;
use Illuminate\View\View;


class WeekNavigation extends Component
{
    public Carbon $requestedDate;
    public MealsRepository $meals;
    public OrdersRepository $orders;

    /**
     * Create a new component instance.
     *
     * @return void
     */
    public function __construct(
        Request $request,
        MealsRepository $meals,
        OrdersRepository $orders
    ) {
        $this->requestedDate = Carbon::parse($request->query('date', today()));
        $this->meals = $meals;
        $this->orders = $orders;
    }

    /**
     * Get the view / contents that represent the component.
     *
     * @return View|string
     */
    public function render()
    {
        $previousWeek = $this->requestedDate->clone()->subWeek()->startOfWeek();

        $nextWeek = null;

        if ($this->meals->inFutureFrom($this->requestedDate)->isNotEmpty()) {
            $nextWeek = $this->requestedDate->clone()->addWeek()->startOfWeek();
        }
        $period = CarbonPeriod::create(
            $this->requestedDate->clone()->startOfWeek(),
            $this->requestedDate->clone()->endOfWeek()
        );

        return view('components.week-navigation', compact('previousWeek', 'nextWeek', 'period'));
    }
}
