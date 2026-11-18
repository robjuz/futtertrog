<?php

namespace App\Filament\Resources\OrderItems;

use App\Actions\Admin\OrderItems;
use App\Filament\Resources\{AdminResource, Fields, PanelAction};
use App\Filament\Resources\OrderItems\Pages\ManageOrderItems;
use App\Models\OrderItem;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Gate;

class OrderItemResource extends AdminResource
{
    protected static ?string $slug = 'order-positions';
    protected static ?string $model = OrderItem::class;
    protected static string $listAbility = 'administerAny';
    protected static ?string $pluralModelLabel = 'Bestellpositionen';
    protected static ?string $modelLabel = 'Bestellposition';

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['meal.parent.parent', 'order', 'user' => fn (mixed $q): mixed => $q->withTrashed()]);
    }

    public static function fields(int $minimum = 1): array
    {
        return [Fields::meal(), Fields::user()->required(), TextInput::make('quantity')->label('Menge')->required()->integer()->minValue($minimum)->maxValue(10)->default(1)];
    }

    public static function headerActions(): array
    {
        return [PanelAction::make('orderFor')->label('In Stellvertretung bestellen')->authorize(fn (): bool => Gate::allows('administerAny', OrderItem::class))
            ->modalDescription('Administrative Sonderaktion: Die normalen Bestellfristen gelten hier nicht.')
            ->schema(static::fields())->action(fn (array $data, Action $action) => PanelAction::run($action, fn () => app(OrderItems::class)->create(auth()->user(), $data)))];
    }

    public static function table(Table $table): Table
    {
        return $table->defaultSort('id', 'desc')->paginationPageOptions([10, 25, 50])->columns([
            TextColumn::make('order_id')->label('Sammelbestellung')->searchable()->sortable(),
            TextColumn::make('meal.date')->label('Datum')->date('d.m.Y')->sortable(),
            TextColumn::make('meal.title')->label('Gericht / Variante')->searchable(),
            TextColumn::make('user.name')->label('Benutzer')->searchable(),
            TextColumn::make('quantity')->label('Menge')->sortable(),
            TextColumn::make('subtotal')->label('Summe')->formatStateUsing(fn ($state): string => (string) $state),
            TextColumn::make('order.status')->label('Status'),
        ])->filters([
            SelectFilter::make('order_id')->label('Sammelbestellung')->relationship('order', 'id')->searchable(),
            SelectFilter::make('user_id')->label('Benutzer')->relationship('user', 'name', fn (Builder $query): Builder => $query->withTrashed())->searchable(),
            Fields::dates(relationship: 'meal'), Fields::providerFilter('meal'),
            SelectFilter::make('status')->label('Status')->options(\App\Filament\Resources\Orders\OrderResource::statuses())
                ->query(fn (Builder $query, array $data): Builder => $query->when($data['value'] ?? null, fn (Builder $query, string $status): Builder => $query->whereRelation('order', 'status', $status))),
        ])->recordActions([
            PanelAction::make('change')->label('Sonderänderung')->authorize(fn (OrderItem $record): bool => Gate::allows('administer', $record))
                ->fillForm(fn (OrderItem $record): array => ['meal_id' => $record->meal_id, 'user_id' => $record->user_id, 'quantity' => $record->quantity])
                ->schema(static::fields(0))->action(fn (array $data, OrderItem $record, Action $action) => PanelAction::run($action, fn () => app(OrderItems::class)->update(auth()->user(), $record, $data))),
            PanelAction::make('remove')->label('Stornieren')->color('danger')->authorize(fn (OrderItem $record): bool => Gate::allows('administer', $record))
                ->action(fn (OrderItem $record, Action $action) => PanelAction::run($action, fn () => app(OrderItems::class)->delete(auth()->user(), $record))),
        ]);
    }

    public static function getPages(): array
    {
        return ['index' => ManageOrderItems::route('/')];
    }
}
