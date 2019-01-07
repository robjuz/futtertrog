<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Carbon;

class NoOrder extends Notification
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
        //
        $this->date = $date;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        $day = $this->date->isToday() ? __('today') : $this->date->localeDayOfWeek;
        return (new MailMessage)
            ->subject(__('No order for', ['day' => $day]))
                    ->line(__('This is a friendly reminder.'))
                    ->line(__('You have ordered no food for', ['day' => $day]));
    }

    /**
     * Get the array representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function toArray($notifiable)
    {
        return [
            //
        ];
    }
}
