<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NoOrder extends Notification
{
    use Queueable;

    /**
     * @var string
     */
    private $day;

    /**
     * Create a new notification instance.
     *
     * @param  string  $day
     */
    public function __construct(string $day)
    {
        $this->day = $day;
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
            'title' => __('This is a friendly reminder.'),
            'body' => __('You have ordered no food for!', ['day' => $this->day]),
        ];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        return (new MailMessage)->subject(__('No order for', ['day' => $this->day]))
            ->line(__('This is a friendly reminder.'))
            ->line(__('You have ordered no food for!', ['day' => $this->day]));
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
}
