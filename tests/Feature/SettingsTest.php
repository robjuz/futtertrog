<?php

namespace Tests\Feature;

use App\User;
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
            User::SETTING_NEW_ORDER_POSSIBILITY_NOTIFICATION => true,
            User::SETTING_NO_ORDER_NOTIFICATION => true,
            User::SETTING_NO_ORDER_FOR_NEXT_DAY_NOTIFICATION => true,
            User::SETTING_MEAL_PREFERENCES => 'meal1, meal2',
            User::SETTING_MEAL_AVERSION => 'meal3, meal4',
            User::SETTING_DARK_MODE => 'true',
            User::SETTING_LANGUAGE => 'de'
        ];

        $this->login()->post(route('settings.store'),$data)->assertRedirect();
        $this->login()->postJson(route('settings.store'), $data)->assertJson($data);
    }
}
