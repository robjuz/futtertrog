<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Carbon;
use Illuminate\Support\Enumerable;

class NewOrderPossibilities extends Notification
{
    use Queueable;

    /**
     * @var Enumerable<int, Carbon>
     */
    private Enumerable $dates;

    /**
     * Create a new notification instance.
     *
     * @param  Enumerable<int, Carbon>  $dates
     */
    public function __construct(Enumerable $dates)
    {
        $this->dates = $dates;
    }

    /**
     * Get the array representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return array<string, mixed>
     */
    public function toArray($notifiable)
    {
        return [
            'dates' => $this->dates,
        ];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(User $notifiable): MailMessage
    {
        $message = (new MailMessage)->subject(__('New order possibilities'));

        $locale = $notifiable->settings->language ?? config('app.locale');

        foreach ($this->dates as $date) {
            /** @var Carbon $carbonDate */
            $carbonDate = $date;
            $formatted = $carbonDate->locale($locale)->isoFormat('ddd MMM DD YYYY');
            $message->line(__('New order possibility for :day', ['day' => $formatted]));
        }

        return $message->action(__('Click here for more details'), route('meals.index'));
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  User  $notifiable
     * @return array<int, string>
     */
    public function via($notifiable)
    {
        return ['mail'];
    }
}
