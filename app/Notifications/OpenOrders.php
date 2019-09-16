<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Messages\NexmoMessage;
use Illuminate\Notifications\Notification;

class OpenOrders extends Notification
{
    use Queueable;

    /**
     * @var string
     */
    private $day;

    /**
     * Create a new notification instance.
     *
     * @param string $day
     */
    public function __construct(string $day)
    {
        $this->day = $day;
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
            'title' => __('This is a friendly reminder.'),
            'body' => __('There is an open order!', ['day' => $this->day]),
        ];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param mixed $notifiable
     *
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        return (new MailMessage)->subject(__('Open order for', ['day' => $this->day]))
            ->line(__('This is a friendly reminder.'))
            ->line(__('There is an open order!', ['day' => $this->day]));
    }

    /**
     * Get the Nexmo / SMS representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return NexmoMessage
     */
    public function toNexmo($notifiable)
    {
        return (new NexmoMessage())
            ->content(__('There is an open order!', ['day' => $this->day]))
            ->unicode();
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
        return ['mail', 'nexmo'];
    }
}
