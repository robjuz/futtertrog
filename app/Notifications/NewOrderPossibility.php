<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Carbon;
use NotificationChannels\WebPush\WebPushChannel;
use NotificationChannels\WebPush\WebPushMessage;

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
            ->line(__('New order possibility for :day', ['day' => $day]))
            ->action(__('Click here for more details'), $url);
    }

    public function toWebPush($notifiable)
    {
        $day = $this->date->format(trans('futtertrog.date_format'));
        $url = route('meals.index', [
            'date' => $this->date->toDateString(),
        ]);

        return (new WebPushMessage())
            ->title(__('New order possibility for :day', ['day' => $day]))
            //->icon('/utensils.svg')
            ->body(__('New order possibility for :day', ['day' => $day]))
            ->action('Click here for more details', 'click')
            ->data(['url' =>  $url])
            // ->badge()
            // ->dir()
            // ->image('/utensils.svg')
            ->lang(app()->getLocale())
            // ->renotify()
            // ->requireInteraction()
            // ->tag()
            // ->vibrate()
        ;
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
        return ['mail', WebPushChannel::class];
    }
}
