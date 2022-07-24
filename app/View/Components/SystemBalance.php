<?php

namespace App\View\Components;

use Cknow\Money\Money;
use Illuminate\View\Component;

class SystemBalance extends Component
{
    public Money $balance;

    /**
     * Create a new component instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->balance = app('system_balance');
    }

    public function balanceClass()
    {
        return $this->balance->isPositive() ? 'positive-value' : 'negative-value';
    }

    /**
     * Get the view / contents that represent the component.
     *
     * @return \Illuminate\Contracts\View\View|\Closure|string
     */
    public function render()
    {
        return view('components.system-balance');
    }
}
