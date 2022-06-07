<?php

namespace Tests\Feature;

use App\User;
use App\UserSettings;
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
        // $this->withExceptionHandling();
        $data = [
            UserSettings::NEW_ORDER_POSSIBILITY_NOTIFICATION => 1,
            UserSettings::NO_ORDER_NOTIFICATION => 1,
            UserSettings::NO_ORDER_FOR_NEXT_DAY_NOTIFICATION => 1,
            UserSettings::NO_ORDER_FOR_NEXT_WEEK_NOTIFICATION => 1,
            UserSettings::MEAL_PREFERENCES => 'meal1, meal2',
            UserSettings::MEAL_AVERSION => 'meal3, meal4',
            UserSettings::HIDE_ORDERING_MEAL_DESCRIPTION => 0,
            UserSettings::HIDE_DASHBOARD_MEAL_DESCRIPTION => 0,
            UserSettings::LANGUAGE => 'de',
        ];

        $this->login()
            ->post(route('settings.store'), $data)
            ->assertRedirect();

        $jsonData = [
            UserSettings::NEW_ORDER_POSSIBILITY_NOTIFICATION => true,
            UserSettings::NO_ORDER_NOTIFICATION => true,
            UserSettings::NO_ORDER_FOR_NEXT_DAY_NOTIFICATION => true,
            UserSettings::NO_ORDER_FOR_NEXT_WEEK_NOTIFICATION => true,
            UserSettings::MEAL_PREFERENCES => 'meal1, meal2',
            UserSettings::MEAL_AVERSION => 'meal3, meal4',
            UserSettings::HIDE_ORDERING_MEAL_DESCRIPTION => false,
            UserSettings::HIDE_DASHBOARD_MEAL_DESCRIPTION => false,
            UserSettings::LANGUAGE => 'de',
        ];
    
        $this->login()->postJson(route('settings.store'), $jsonData)->assertJson($jsonData);
    }
}
