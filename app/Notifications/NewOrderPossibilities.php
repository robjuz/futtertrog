<?php

namespace App\Notifications;

use App\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Carbon;
use NotificationChannels\WebPush\WebPushChannel;
use NotificationChannels\WebPush\WebPushMessage;
use Traversable;

class NewOrderPossibilities extends Notification
{
    use Queueable;

    /**
     * @var Carbon[]|null
     */
    private $dates;

    /**
     * Create a new notification instance.
     * @param Traversable $dates
     */
    public function __construct(Traversable $dates)
    {
        $this->dates = $dates;
    }

    /**
     * Get the array representation of the notification.
     *
     * @param mixed $notifiable
     *
     * @return array
     */
    public function toArray($notifiable)
    {
        return [
            'dates' => $this->dates,
        ];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param mixed $notifiable
     *
     * @return MailMessage
     */
    public function toMail(User $notifiable)
    {
        $message = (new MailMessage)->subject(__('New order possibilities'));

        foreach ($this->dates as $date) {
            $formatted = Carbon::parse($date)->locale($notifiable->settings[User::SETTING_LANGUAGE])->isoFormat('ddd MMM DD YYYY');
            $message->line(__('New order possibility for :day', ['day' => $formatted]));
        }

        return $message->action(__('Click here for more details'), route('meals.index'));
    }

    public function toWebPush(User $notifiable, $notification)
    {
        return (new WebPushMessage())
            ->title(__('New order possibilities'))
            //->icon('/utensils.svg')
            ->body(__('New order possibilities'))
            ->action('Click here for more details', 'click')
            ->data(['url' => route('meals.index')])
            // ->badge()
            // ->dir()
            // ->image('/utensils.svg')
            ->lang($notifiable->settings[User::SETTING_LANGUAGE])
            // ->renotify()
            // ->requireInteraction()
            // ->tag()
            // ->vibrate()
;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param mixed $notifiable
     *
     * @return array
     */
    public function via($notifiable)
    {
        return ['mail', WebPushChannel::class];
    }
}
