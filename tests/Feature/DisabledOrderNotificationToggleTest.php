<?php

namespace Tests\Feature;

use App\Models\User;
use Tests\TestCase;

class DisabledOrderNotificationToggleTest extends TestCase
{

    /** @test */
    public function can_be_toggeled_for_a_specific_date()
    {
        $user = User::factory()->create();
        $this->login($user);

        $this->post(route('notification.disable'), ['date' => '2022-05-12']);
        $this->assertDatabaseHas('disabled_notifications', ['user_id' => $user->id, 'date' => '2022-05-12 00:00:00']);

        $this->delete(route('notification.disable'), ['date' => '2022-05-12']);
        $this->assertDatabaseMissing('disabled_notifications', ['user_id' => $user->id, 'date' => '2022-05-12 00:00:00']);

    }
}
