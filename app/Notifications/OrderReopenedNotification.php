<?php

namespace App\Notifications;

use App\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OrderReopenedNotification extends Notification
{
    use Queueable;
    /**
     * @var Order
     */
    private $order;

    /**
     * Create a new notification instance.
     *
     * @param Order $order
     */
    public function __construct(Order $order)
    {
        //
        $this->order = $order;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param  mixed $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        $url = route('orders.index', [
            'from' => $this->order->date->format(trans('futtertrog.d.m.Y')),
            'to' => $this->order->date->format(trans('futtertrog.d.m.Y')),
        ]);

        return (new MailMessage)
            ->subject(__('Order reopened'))
            ->line(__('The order for :date was reopened', ['date' => $this->order->date->toDateString()]))
            ->action(__('Click here for more details'), $url);
    }

    /**
     * Get the array representation of the notification.
     *
     * @param  mixed $notifiable
     * @return array
     */
    public function toArray($notifiable)
    {
        return [
            //
        ];
    }
}
