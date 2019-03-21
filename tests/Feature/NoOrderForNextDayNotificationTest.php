<?php

namespace Tests\Feature;

use App\Notifications\NoOrder;
use App\ScheduledJobs\NoOrderForNextDayNotification;
use App\User;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class NoOrderForNextDayNotificationTest extends TestCase
{
    /** @test */
    public function it_sent_a_notification_to_users_that_opted_in()
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
        Notification::assertSentTo($tom, NoOrder::class);

        $this->assertTrue(true);
    }
}
