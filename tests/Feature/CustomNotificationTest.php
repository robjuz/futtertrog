<?php

namespace Tests\Feature;

use App\Notifications\CustomNotification;
use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class CustomNotificationTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_allows_the_admin_to_send_a_custom_notification_to_a_user()
    {
        Notification::fake();

        /** @var User $user */
        $user = factory(User::class)->create();

        $this->loginAsAdmin()
            ->postJson(
                route('notification.store'),
                [
                    'user_id' => $user->id,
                    'subject' => 'Notification',
                    'body' => 'Notification message',
                ]
            );

        Notification::assertSentTo(
            $user,
            CustomNotification::class,
            function (CustomNotification $notification, $notifable) {
                $notification = $notification->toMail($notifable);

                return $notification->subject == 'Notification' && in_array(
                        'Notification message',
                        $notification->introLines
                    );
            }
        );
    }

    /** @test */
    public function it_allows_the_admin_to_send_a_custom_notification_to_many_users()
    {
        Notification::fake();

        /** @var User $user */
        $users = factory(User::class, 10)->create();

        $this->loginAsAdmin()
            ->postJson(
                route('notification.store'),
                [
                    'user_id' => $users->pluck('id')->toArray(),
                    'subject' => 'Notification',
                    'body' => 'Notification message',
                ]
            );

        Notification::assertSentTo(
            $users,
            CustomNotification::class,
            function (CustomNotification $notification, $notifable) {
                $notification = $notification->toMail($notifable);

                return $notification->subject == 'Notification' && in_array(
                        'Notification message',
                        $notification->introLines
                    );
            }
        );
    }

    /** @test */
    public function only_admin_can_create_a_notification()
    {
        $this->login()
            ->withExceptionHandling()
            ->postJson(route('notification.store'))
            ->assertForbidden();
    }

    /** @test */
    public function it_validated_the_request()
    {
        $this->loginAsAdmin()
            ->withExceptionHandling()
            ->postJson(route('notification.store'))
            ->assertJsonValidationErrors(
                [
                    'user_id',
                    'subject',
                    'body',
                ]
            );
    }

    /** @test */
    public function it_shows_an__admin_a_create_form()
    {
        $this->login()
            ->withExceptionHandling()
            ->get(route('notifications.create'))
            ->assertForbidden();

        $this->loginAsAdmin()
            ->get(route('notifications.create'))
            ->assertSee('user_id')
            ->assertSee('subject')
            ->assertSee('body')
            ->assertSee('submit');
    }
}
