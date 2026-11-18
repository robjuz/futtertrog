<?php

namespace Tests\Feature;

use App\Models\User;
use App\Notifications\CustomNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use App\Filament\Pages\Notifications;
use Filament\Facades\Filament;
use Livewire\Livewire;
use Tests\TestCase;

class CustomNotificationTest extends TestCase
{
    use RefreshDatabase;

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_allows_the_admin_to_send_a_custom_notification_to_a_user()
    {
        Notification::fake();
        $user = User::factory()->create();
        $this->loginAsAdmin();
        Filament::setCurrentPanel(Filament::getPanel('administration'));

        Livewire::test(Notifications::class)->callAction('send', data: ['user_id' => [$user->id], 'subject' => 'Notification', 'body' => 'Notification message'])->assertHasNoActionErrors();

        Notification::assertSentTo($user, CustomNotification::class, function (CustomNotification $notification, $notifiable): bool {
            $mail = $notification->toMail($notifiable);

            return $mail->subject === 'Notification' && in_array('Notification message', $mail->introLines);
        });
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_allows_the_admin_to_send_a_custom_notification_to_many_users()
    {
        Notification::fake();
        $users = User::factory()->count(10)->create();
        $this->loginAsAdmin();
        Filament::setCurrentPanel(Filament::getPanel('administration'));

        Livewire::test(Notifications::class)->callAction('send', data: ['user_id' => $users->modelKeys(), 'subject' => 'Notification', 'body' => 'Notification message'])->assertHasNoActionErrors();

        Notification::assertSentTo($users, CustomNotification::class, function (CustomNotification $notification, $notifiable): bool {
            $mail = $notification->toMail($notifiable);
            $data = $notification->toArray($notifiable);

            return $mail->subject === 'Notification' && in_array('Notification message', $mail->introLines) && $data['title'] === 'Notification' && $data['body'] === 'Notification message';
        });
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function only_admin_can_create_a_notification()
    {
        $this->login()
            ->withExceptionHandling()
            ->postJson(route('notification.store'))
            ->assertForbidden();
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_validated_the_request()
    {
        $this->loginAsAdmin();
        Filament::setCurrentPanel(Filament::getPanel('administration'));

        Livewire::test(Notifications::class)->callAction('send', data: [])->assertHasActionErrors(['user_id', 'subject', 'body']);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_shows_an_admin_a_create_form()
    {
        $this->login()->withExceptionHandling()->get(route('notifications.create'))->assertForbidden();
        $this->loginAsAdmin();
        Filament::setCurrentPanel(Filament::getPanel('administration'));

        $page = Livewire::test(Notifications::class)->mountAction('send');
        $page->assertFormFieldExists('user_id')->assertFormFieldExists('subject')->assertFormFieldExists('body');
    }
}
