<?php

namespace Tests\Feature;

use App\Actions\Admin\OrderItems;
use App\Events\OrderUpdated;
use App\Filament\Resources\OrderItems\Pages\ManageOrderItems;
use App\Models\{AdminActivity, Meal, User};
use Filament\Facades\Filament;
use Filament\Forms\Components\Select;
use Illuminate\Support\Facades\Event;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\{Test, TestWith};
use Tests\TestCase;

class MealOrderGroupTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->app->alias(\App\MealProviders\Holzke::class, 'Holzke');
        $this->app->alias(\App\MealProviders\Gourmetta::class, 'Gourmetta');
        $this->app->alias(\App\MealProviders\CallAPizza::class, 'CallAPizza');
    }

    #[Test]
    #[TestWith(['Internal', 'Holzke', 0, 'admin'])]
    #[TestWith(['Internal', 'Holzke', 0, 'self'])]
    #[TestWith(['Holzke', 'Gourmetta', 0, 'admin'])]
    #[TestWith(['Holzke', 'Gourmetta', 0, 'self'])]
    #[TestWith(['Internal', 'Internal', 7, 'admin'])]
    #[TestWith(['Internal', 'Internal', 7, 'self'])]
    #[TestWith(['CallAPizza', 'CallAPizza', 1, 'admin'])]
    #[TestWith(['CallAPizza', 'CallAPizza', 1, 'self'])]
    public function admin_and_self_service_reject_meals_outside_the_existing_group(string $provider, string $nextProvider, int $days, string $channel): void
    {
        Event::fake([OrderUpdated::class]);
        $date = today()->addWeeks(2)->startOfWeek();
        $user = User::factory()->create();
        $meal = Meal::factory()->create(['provider' => $provider, 'date' => $date]);
        $item = $meal->order($user);
        $other = Meal::factory()->create(['provider' => $nextProvider, 'date' => $date->copy()->addDays($days)]);
        $item->order->markOrdered();
        $before = $item->fresh()->getAttributes();
        $orderCount = \App\Models\Order::count();
        Event::fake([OrderUpdated::class]);
        if ($channel === 'self') {
            $this->login($user)->withExceptionHandling()->putJson(route('order_items.update', $item), ['meal_id' => $other->id])
                ->assertUnprocessable()->assertJsonValidationErrors('meal_id');
        } else {
            try {
                app(OrderItems::class)->update(User::factory()->admin()->create(), $item, ['meal_id' => $other->id]);
                $this->fail('A cross-group administrative meal change must be rejected.');
            } catch (ValidationException $exception) {
                $this->assertArrayHasKey('meal_id', $exception->errors());
            }
        }
        $this->assertSame($before, $item->fresh()->getAttributes());
        $this->assertSame($orderCount, \App\Models\Order::count());
        $this->assertSame('ordered', $item->order->fresh()->status);
        $this->assertDatabaseCount('admin_activities', 0);
        Event::assertNotDispatched(OrderUpdated::class);
    }

    #[Test]
    #[TestWith(['Internal', 1])]
    #[TestWith(['CallAPizza', 0])]
    public function valid_changes_keep_the_group_and_panel_excludes_foreign_meals(string $provider, int $days): void
    {
        Event::fake([OrderUpdated::class]);
        Filament::setCurrentPanel(Filament::getPanel('administration'));
        $date = today()->addWeeks(2)->startOfWeek();
        $user = User::factory()->create();
        $meal = Meal::factory()->create(['provider' => $provider, 'date' => $date]);
        $item = $meal->order($user);
        $valid = Meal::factory()->create(['provider' => $provider, 'date' => $date->copy()->addDays($days)]);
        $foreign = Meal::factory()->create(['provider' => $provider, 'date' => $date->copy()->addDays($provider === 'CallAPizza' ? 1 : 7)]);
        $otherProvider = Meal::factory()->create(['provider' => $provider === 'Internal' ? 'Holzke' : 'Internal', 'date' => $date]);
        $this->login($user)->putJson(route('order_items.update', $item), ['meal_id' => $valid->id])->assertOk();
        $admin = User::factory()->admin()->create();
        app(OrderItems::class)->update($admin, $item, ['meal_id' => $meal->id]);
        $this->assertSame($item->order_id, $item->fresh()->order_id);
        $this->assertSame($meal->id, $item->fresh()->meal_id);
        $this->login($admin);
        Livewire::test(ManageOrderItems::class)->mountTableAction('change', $item)
            ->assertFormFieldExists('meal_id', function (Select $field) use ($valid, $foreign, $otherProvider): bool {
                $options = $field->getOptions();
                $search = $field->getSearchResults('');

                return isset($options[$valid->id], $search[$valid->id])
                    && !isset($options[$foreign->id]) && !isset($search[$foreign->id])
                    && !isset($options[$otherProvider->id]) && !isset($search[$otherProvider->id]);
            });
        Livewire::test(ManageOrderItems::class)->callTableAction('change', $item, data: [
            'meal_id' => $foreign->id, 'user_id' => $user->id, 'quantity' => 1,
        ])->assertHasActionErrors(['meal_id']);
        $this->assertSame($meal->id, $item->fresh()->meal_id);
    }

    #[Test]
    public function administrative_creation_assigns_each_provider_and_period_to_its_own_group(): void
    {
        Event::fake([OrderUpdated::class]);
        $admin = User::factory()->admin()->create();
        $date = today()->addWeeks(2)->startOfWeek();
        $ids = [];
        foreach ([['Internal', $date], ['Holzke', $date], ['Internal', $date->copy()->addWeek()]] as [$provider, $day]) {
            $meal = Meal::factory()->create(['provider' => $provider, 'date' => $day]);
            $ids[] = app(OrderItems::class)->create($admin, ['meal_id' => $meal->id])->order_id;
        }
        $this->assertCount(3, array_unique($ids));
    }
}
