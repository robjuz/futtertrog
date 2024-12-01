<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class MealImportTest extends TestCase
{
    /** @test */
    public function only_admin_can_import_meals_from_providers()
    {
        $this->withExceptionHandling();

        $this
            ->login()
            ->postJson(route('meals.import'))
            ->assertForbidden();

        $this->loginAsAdmin()
            ->postJson(route('meals.import'))
            ->assertUnprocessable();
    }

    /** @test */
    public function it_allows_to_import_meals_from_holzke(): void
    {
        $this->loginAsAdmin()
            ->post()
    }
}
