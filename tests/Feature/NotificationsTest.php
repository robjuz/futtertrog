<?php

namespace Tests\Feature;

use App\DisabledNotification;
use App\Events\NewOrderPossibility;
use App\Meal;
use App\Notifications\CustomNotification;
use App\Notifications\NewOrderPossibility as NewOrderPossibilityNotification;
use App\Notifications\NoOrder;
use App\Notifications\OpenOrders;
use App\Notifications\OrderReopenedNotification;
use App\Order;
use App\ScheduledJobs\NoOrderForNextDayNotification;
use App\ScheduledJobs\NoOrderForNextWeekNotification;
use App\ScheduledJobs\NoOrderForTodayNotification;
use App\ScheduledJobs\OpenOrdersForNextWeekNotification;
use App\User;
use App\UserSettings;
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

        $john = User::factory()->create();

        $tomSettings = new UserSettings();
        $tomSettings->noOrderNotification = true;
        $tom = User::factory()->create(
            [
                'settings' => $tomSettings,
            ]
        );

        (new NoOrderForTodayNotification)();

        Notification::assertNotSentTo($john, NoOrder::class);
        Notification::assertSentTo($tom, NoOrder::class, function ($message, $channels, $notifialble) {
            $toArray =  $message->toArray($notifialble);
            $toMail = $message->toMail($notifialble);

            $day = __('calendar.today');

            return $toArray['title'] === __('This is a friendly reminder.')
                && $toArray['body'] === __('You have ordered no food for!', ['day' => $day])

                && $toMail->subject === __('No order for', ['day' => $day])
                && in_array(__('This is a friendly reminder.'), $toMail->introLines)
                && in_array(__('You have ordered no food for!', ['day' => $day]), $toMail->introLines);
        });

        $this->assertTrue(true);
    }

    /** @test */
    public function it_sent_a_no_order_for_next_day_notification_to_users_that_opted_in()
    {
        Notification::fake();

        $john = User::factory()->create();

        $tomSettings = new UserSettings();
        $tomSettings->noOrderForNextDayNotification = true;
        $tom = User::factory()->create(
            [
                'settings' => $tomSettings,
            ]
        );

        (new NoOrderForNextDayNotification())();

        Notification::assertNotSentTo($john, NoOrder::class);
        Notification::assertSentTo($tom, NoOrder::class, function ($message, $channels, $notifialble) {
            $toArray =  $message->toArray($notifialble);
            $toMail = $message->toMail($notifialble);

            $day = __('calendar.' . today()->addWeekdays()->englishDayOfWeek);

            return $toArray['title'] === __('This is a friendly reminder.')
                && $toArray['body'] === __('You have ordered no food for!', ['day' => $day])

                && $toMail->subject === __('No order for', ['day' => $day])
                && in_array(__('This is a friendly reminder.'), $toMail->introLines)
                && in_array(__('You have ordered no food for!', ['day' => $day]), $toMail->introLines);
        });
    }

    /** @test */
    public function it_sent_a_no_order_for_next_week_notification_to_users_that_opted_in()
    {
        Notification::fake();

        $john = User::factory()->create();

        $tomSettings = new UserSettings();
        $tomSettings->noOrderForNextWeekNotification = true;
        $tom = User::factory()->create(['settings' => $tomSettings]);

        (new NoOrderForNextWeekNotification())();

        Notification::assertNotSentTo($john, NoOrder::class);
        Notification::assertSentTo($tom, NoOrder::class, function ($message, $channels, $notifialble) {
            $toArray =  $message->toArray($notifialble);
            $toMail = $message->toMail($notifialble);

            $day = __('Next week');

            return $toArray['title'] === __('This is a friendly reminder.')
                && $toArray['body'] === __('You have ordered no food for!', ['day' => $day])

                && $toMail->subject === __('No order for', ['day' => $day])
                && in_array(__('This is a friendly reminder.'), $toMail->introLines)
                && in_array(__('You have ordered no food for!', ['day' => $day]), $toMail->introLines);
        });
    }

    /** @test */
    public function it_sent_a_open_order_for_next_week_notification_to_all_admins()
    {
        Notification::fake();

        $john = User::factory()->admin()->create();


        // (new OpenOrdersForNextWeekNotification())();

        Notification::assertNotSentTo($john, OpenOrders::class);


        $nextMonday = today()->addWeek()->startOfWeek();

        $meal = Meal::factory()->create([
            'date_from' => $nextMonday,
            'date_to' => $nextMonday
        ]);

        $this->actingAs($john)
            ->postJson(route('order_items.store'), [
                'date' => $nextMonday->toDateString(),
                'user_id' => $john->id,
                'quantity' => 1,
                'meal_id' => $meal->id,
            ]);



        (new OpenOrdersForNextWeekNotification())();

        Notification::assertSentTo($john, OpenOrders::class);

        Notification::assertSentTo($john, OpenOrders::class, function ($message, $channels, $notifiable) use ($nextMonday) {
            $toArray =  $message->toArray($notifiable);
            $toMail = $message->toMail($notifiable);


            return $toArray['title'] === __('This is a friendly reminder.')
                && $toArray['body'] === __('There is an open order!', ['day' => __('Next week')])

                && $toMail->subject === __('Open order for', ['day' => __('Next week')])
                && in_array(__('This is a friendly reminder.'), $toMail->introLines)
                && in_array(__('There is an open order!', ['day' => __('Next week')]), $toMail->introLines);
        });
    }

    /** @test */
    public function it_sent_a_new_order_possibility_notification_to_users_that_opted_in()
    {
        Notification::fake();

        $today = today();

        $john = User::factory()->create();

        $tomSettings = new UserSettings();
        $tomSettings->newOrderPossibilityNotification = true;
        $tom = User::factory()->create(['settings' => $tomSettings]);

        event(new NewOrderPossibility($today));

        Notification::assertNotSentTo($john, NewOrderPossibilityNotification::class);

        Notification::assertSentTo($tom, NewOrderPossibilityNotification::class, function ($message, $channels, $notifialble) use ($today) {
            $toArray =  $message->toArray($notifialble);
            $toMail = $message->toMail($notifialble);


            $day = $today->format(trans('futtertrog.date_format'));

            return $toArray['date'] === $today

                && $toMail->subject === __('New order possibility for :day', ['day' => $day])
                && in_array(__('New order possibility for :day', ['day' => $day]), $toMail->introLines)
                && $toMail->actionText === __('Click here for more details');
        });
    }


    /** @test */
    public function it_notifies_an_admin_when_an_closed_order_was_reopened()
    {
        $meal = Meal::factory()->inFuture()->create();
        $admin = User::factory()->create(['is_admin' => true]);

        // Given we have a closed order
        $order = Order::factory()->create([
            'date' => $meal->date_from,
            'status' => Order::STATUS_ORDERED,
            'provider' => $meal->provider
        ]);


        Notification::fake();
        Mail::fake();

        // When a user creates a new order item associated with this order
        $user = User::factory()->create();
        $this->login($user);
        $this->post(route('order_items.store'), [
            'date' => $meal->date_from,
            'user_id' => $user->id,
            'meal_id' => $meal->id
        ]);

        // Admin should be notified
        Notification::assertSentTo(
            $admin,
            OrderReopenedNotification::class,
            function ($notification, $channels) use ($user, $meal, $order) {
                /** @var \Illuminate\Notifications\Messages\MailMessage $mailData */
                $mailData = $notification->toMail($user);
                $this->assertEquals(__('Order reopened'), $mailData->subject);

                $toArray = $notification->toArray($user);
                $this->assertEquals($toArray['date'], $order->date);
                $this->assertEquals($toArray['user'], $user->name);
                $this->assertEquals($toArray['meal'], $meal->title);

                return $notification->order->is($order)
                    && $notification->user->is($user)
                    && $notification->meal->is($meal);
            }
        );

        // When another creates a new order item associated with this order
        $user2 = User::factory()->create();
        $this->login($user2);
        $this->post(route('order_items.store'), [
            'date' => $meal->date_from,
            'user_id' => $user2->id,
            'meal_id' => $meal->id
        ]);

        // Admin should not be notified again
        Notification::assertSentToTimes(
            $admin,
            OrderReopenedNotification::class,
            1
        );
    }

    /** @test */
    public function it_does_not_sent_a_no_order_for_today_notification_to_users_that_opted_in_but_turned_off_the_notification()
    {
        Notification::fake();

        $settings = new UserSettings();
        $settings->noOrderNotification = true;

        /** @var User $tom */
        $tom = User::factory()->create(
            [
                'settings' => $settings,
            ]
        );

        $tom->disabledNotifications()->create(['date' => today()]);

        (new NoOrderForTodayNotification)();

        Notification::assertNotSentTo($tom, NoOrder::class);

    }
}
