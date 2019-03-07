<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Carbon;

class NewOrderPossibility extends Notification
{
    use Queueable;

    /**
     * @var Carbon|null
     */
    private $date;

    /**
     * Create a new notification instance.
     *
     * @param Carbon $date
     */
    public function __construct(Carbon $date)
    {
        $this->date = $date;
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
            'date' => $this->date,
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
        $day = $this->date->format(trans('futtertrog.date_format'));
        $url = route('meals.index', [
            'date' => $this->date->toDateString(),
        ]);

        return (new MailMessage)
            ->subject(__('New order possibility for :day', ['day' => $day]))
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
