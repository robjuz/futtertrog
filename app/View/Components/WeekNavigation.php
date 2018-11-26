<?php

namespace App\View\Components;

use App\Repositories\MealsRepository;
use App\Repositories\OrdersRepository;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\Component;
use Illuminate\View\View;


class WeekNavigation extends Component
{
    public Carbon $requestedDate;
    public MealsRepository $meals;

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

        $this->user = Auth::user();
    }

    public function notificationDisabled($date)
    {
        return $this->user->disabledNotifications()
            ->where('date', $date)
            ->exists();
    }

    public function listItemClasses($date)
    {
        $classes = [];

        if ($date->isWeekend()) {
            $classes[] = 'week-navigation__list-item--weekend';
        }

        if ($date->isToday()) {
            $classes[] = 'week-navigation__list-item--today';
        }

        if ($date->isSameDay($this->requestedDate)) {
            $classes[] = 'week-navigation__list-item--selected';
            $classes[] = 'selected';
            $classes[] = 'pot';
        }

        return join(' ', $classes);
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
