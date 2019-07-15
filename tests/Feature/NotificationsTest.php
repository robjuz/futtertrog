<?php

namespace Tests\Feature;

use App\Events\NewOrderPossibility;
use App\Notifications\CustomNotification;
use App\Notifications\NewOrderPossibility as NewOrderPossibilityNotification;
use App\Notifications\NoOrder;
use App\ScheduledJobs\NoOrderForNextDayNotification;
use App\ScheduledJobs\NoOrderForNextWeekNotification;
use App\ScheduledJobs\NoOrderForTodayNotification;
use App\User;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class NotificationsTest extends TestCase
{
    /** @test */
    public function it_sent_a_no_order_for_today_notification_to_users_that_opted_in()
    {
        Notification::fake();

        $john = factory(User::class)->create();
        $tom = factory(User::class)->create(
            [
                'settings' => [User::SETTING_NO_ORDER_NOTIFICATION => "1"],
            ]
        );

        (new NoOrderForTodayNotification)();

        Notification::assertNotSentTo($john, NoOrder::class);
        Notification::assertSentTo($tom, NoOrder::class, function($message, $channels, $notifialble) {
            $toArray =  $message->toArray($notifialble);
            $toMail = $message->toMail($notifialble);

            $day = __('calendar.today');

            return $toArray['title'] === __('This is a friendly reminder.')
                && $toArray['body'] === __('You have ordered no food for!', ['day' => $day] )

                && $toMail->subject === __('No order for', ['day' => $day])
                && in_array(__('This is a friendly reminder.'), $toMail->introLines)
                && in_array(__('You have ordered no food for!', ['day' => $day] ), $toMail->introLines);
        });

        $this->assertTrue(true);
    }

    /** @test */
    public function it_sent_a_no_order_for_next_day_notification_to_users_that_opted_in()
    {
        Notification::fake();

        $john = factory(User::class)->create();
        $tom = factory(User::class)->create(
            [
                'settings' => [User::SETTING_NO_ORDER_FOR_NEXT_DAY_NOTIFICATION => "1"],
            ]
        );

        (new NoOrderForNextDayNotification())();

        Notification::assertNotSentTo($john, NoOrder::class);
        Notification::assertSentTo($tom, NoOrder::class, function($message, $channels, $notifialble) {
            $toArray =  $message->toArray($notifialble);
            $toMail = $message->toMail($notifialble);

            $day = __('calendar.'.today()->addWeekdays()->englishDayOfWeek);

            return $toArray['title'] === __('This is a friendly reminder.')
                && $toArray['body'] === __('You have ordered no food for!', ['day' => $day] )

                && $toMail->subject === __('No order for', ['day' => $day])
                && in_array(__('This is a friendly reminder.'), $toMail->introLines)
                && in_array(__('You have ordered no food for!', ['day' => $day] ), $toMail->introLines);
        });
    }

    /** @test */
    public function it_sent_a_no_order_for_next_week_notification_to_users_that_opted_in()
    {
        Notification::fake();

        $john = factory(User::class)->create();
        $tom = factory(User::class)->create(
            [
                'settings' => [User::SETTING_NO_ORDER_FOR_NEXT_WEEK_NOTIFICATION => "1"],
            ]
        );

        (new NoOrderForNextWeekNotification())();

        Notification::assertNotSentTo($john, NoOrder::class);
        Notification::assertSentTo($tom, NoOrder::class, function($message, $channels, $notifialble) {
            $toArray =  $message->toArray($notifialble);
            $toMail = $message->toMail($notifialble);

            $day = __('Next week');

            return $toArray['title'] === __('This is a friendly reminder.')
                && $toArray['body'] === __('You have ordered no food for!', ['day' => $day] )

                && $toMail->subject === __('No order for', ['day' => $day])
                && in_array(__('This is a friendly reminder.'), $toMail->introLines)
                && in_array(__('You have ordered no food for!', ['day' => $day] ), $toMail->introLines);
        });
    }

    /** @test */
    public function it_sent_a_new_order_possibility_notification_to_users_that_opted_in()
    {
        Notification::fake();

        $today = today();

        $john = factory(User::class)->create();
        $tom = factory(User::class)->create(
            [
                'settings' => [User::SETTING_NEW_ORDER_POSSIBILITY_NOTIFICATION => "1"],
            ]
        );

        event(new NewOrderPossibility($today));

        Notification::assertNotSentTo($john, NewOrderPossibilityNotification::class);

        Notification::assertSentTo($tom, NewOrderPossibilityNotification::class, function($message, $channels, $notifialble) use ($today) {
            $toArray =  $message->toArray($notifialble);
            $toMail = $message->toMail($notifialble);



            $day = $today->format(trans('futtertrog.date_format'));

            return $toArray['date'] === $today

                && $toMail->subject === __('New order possibility for :day', ['day' => $day])
                && in_array(__('New order possibility for :day', ['day' => $day]), $toMail->introLines)
                && $toMail->actionText === __('Click here for more details');
        });
    }
}
