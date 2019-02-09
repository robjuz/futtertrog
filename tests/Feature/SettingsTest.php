<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

class SettingsTest extends TestCase
{
    /** @test */
    public function it_provides_a_list_of_users_settings()
    {
        $this->login();

        $this->get(route('settings.index'))->assertViewIs('settings.index');
        $this->getJson(route('settings.index'))->assertJson([]);
    }

    /** @test */
    public function it_allows_to_store_settings()
    {
        $data = [
            'noOrderNotification' => true,
            'noOrderForNextDayNotification' => true,
        ];

        $this->login()->post(route('settings.store'),$data)->assertRedirect();
        $this->login()->postJson(route('settings.store'), $data)->assertJson($data);
    }
}
