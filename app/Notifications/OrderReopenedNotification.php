<?php

namespace App\Notifications;

use App\Meal;
use App\Order;
use App\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OrderReopenedNotification extends Notification
{
    use Queueable;
    /**
     * @var Order
     */
    public $order;
    /**
     * @var User
     */
    public $user;
    /**
     * @var Meal
     */
    public $meal;

    /**
     * Create a new notification instance.
     *
     * @param Order $order
     * @param User $user
     * @param Meal $meal
     */
    public function __construct(Order $order, User $user, Meal $meal)
    {
        //
        $this->order = $order;
        $this->user = $user;
        $this->meal = $meal;
    }

    /**
     * Get the array representation of the notification.
     *
     * @param  mixed $notifiable
     *
     * @return array
     */
    public function toArray($notifiable)
    {
        return [
            'date' => $this->order->date,
            'user' => $this->user->name,
            'meal' => $this->meal->title,
        ];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param  mixed $notifiable
     *
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        $url = route('orders.index', [
            'from' => $this->order->date->toDateString(),
            'to'   => $this->order->date->toDateString(),
        ]);

        return (new MailMessage)
            ->subject(__('Order reopened'))
            ->line(__('The order for :date was reopened',
                ['date' => $this->order->date->format(trans('futtertrog.date_format'))]))
            ->line(__(':user updated :meal', ['user' => $this->user->name, 'meal' => $this->meal->title]))
            ->action(__('Click here for more details'), $url);
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed $notifiable
     *
     * @return array
     */
    public function via($notifiable)
    {
        return ['mail'];
    }
}
