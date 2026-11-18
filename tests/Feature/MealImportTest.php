<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use App\Filament\Resources\Meals\Pages\ManageMeals;
use Filament\Facades\Filament;
use Livewire\Livewire;
use Tests\TestCase;

class MealImportTest extends TestCase
{
    #[\PHPUnit\Framework\Attributes\Test]
    public function only_admin_can_import_meals_from_providers()
    {
        $this->withExceptionHandling();

        $this
            ->login()
            ->postJson(route('meals.import'))
            ->assertForbidden();

        $this->loginAsAdmin();
        Filament::setCurrentPanel(Filament::getPanel('administration'));
        Livewire::test(ManageMeals::class)->callAction('import', data: [])->assertHasActionErrors(['provider', 'date']);
    }
}
