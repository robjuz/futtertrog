<?php

namespace App\View\Components;

use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\View\Component;

class DisableNoOrderNotification extends Component
{

    public Carbon $date;

    public User $user;

    /**
     * Create a new component instance.
     *
     * @return void
     */
    public function __construct(User $user, Carbon $date)
    {
        $this->user = $user;
        $this->date = $date;
    }

    /**
     * Get the view / contents that represent the component.
     *
     * @return \Illuminate\Contracts\View\View|\Closure|string
     */
    public function render()
    {
        $notificationEnabledThisDay = $this->user->disabledNotifications()
            ->where('date', $this->date)
            ->doesntExist();

        return view('components.disable-no-order-notification', compact('notificationEnabledThisDay'));
    }
}
