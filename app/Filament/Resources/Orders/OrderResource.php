<?php

namespace App\Filament\Resources\Orders;

use App\Actions\Admin\{AutoOrder, Orders};
use App\Filament\Resources\{AdminResource, Fields, PanelAction};
use App\Filament\Resources\OrderItems\OrderItemResource;
use App\Filament\Resources\Orders\Pages\ManageOrders;
use App\Models\Order;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use App\Models\AdminActivity;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Gate;

class OrderResource extends AdminResource
{
    protected static ?string $slug = 'collective-orders';
    protected static ?string $model = Order::class;
    protected static string $listAbility = 'list';
    protected static ?string $pluralModelLabel = 'Sammelbestellungen';
    protected static ?string $modelLabel = 'Sammelbestellung';

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->has('orderItems')->with(['orderItems.meal.parent.parent', 'payedBy' => fn (mixed $q): mixed => $q->withTrashed()])
            ->withCount('pendingAutoOrders')->withMin('meals', 'date')->withMax('meals', 'date');
    }

    public static function table(Table $table): Table
    {
        return $table->defaultSort('id', 'desc')->paginationPageOptions([10, 25, 50])->columns([
            TextColumn::make('id')->label('Nr.')->searchable()->sortable(),
            TextColumn::make('provider')->label('Anbieter')->formatStateUsing(fn ($state): string => (string) $state),
            TextColumn::make('meals_min_date')->label('Von')->date('d.m.Y')->sortable(),
            TextColumn::make('meals_max_date')->label('Bis')->date('d.m.Y')->sortable(),
            TextColumn::make('status')->label('Status')->formatStateUsing(fn (string $state): string => static::statuses()[$state] ?? $state),
            TextColumn::make('pending_auto_orders_count')->label('Anbieterabgleich')->badge()
                ->formatStateUsing(fn (int $state): string => $state > 0 ? 'Abgleich erforderlich' : 'Kein Abgleich offen')
                ->color(fn (int $state): string => $state > 0 ? 'warning' : 'gray'),
            TextColumn::make('subtotal')->label('Summe')->formatStateUsing(fn ($state): string => (string) $state),
            TextColumn::make('payed_at')->label('Bezahlt am')->date('d.m.Y'),
            TextColumn::make('payedBy.name')->label('Bezahlt von')->searchable(),
        ])->filters([
            Fields::providerFilter(), SelectFilter::make('status')->label('Status')->options(static::statuses()),
            Fields::dates(relationship: 'meals'),
            Filter::make('auto_order_pending')->label('Anbieterabgleich erforderlich')
                ->query(fn (Builder $query): Builder => $query->has('pendingAutoOrders')),
            SelectFilter::make('recipient')->label('Bestellt für')->relationship('orderItems.user', 'name')->searchable(),
            TernaryFilter::make('payed_at')->label('Bezahlt')->nullable(),
        ])->recordActions([
            Action::make('items')->label('Positionen')->authorize(fn (Order $record): bool => Gate::allows('view', $record))
                ->url(fn (Order $record): string => OrderItemResource::getUrl('index', ['filters' => ['order_id' => ['value' => $record->id]]])),
            PanelAction::make('change')->label('Bearbeiten / abschließen')->authorize(fn (Order $record): bool => Gate::allows('update', $record))
                ->fillForm(fn (Order $record): array => $record->only(['status', 'payed_at', 'user_id']))
                ->schema([Select::make('status')->label('Status')->options(static::statuses())->required(), DatePicker::make('payed_at')->label('Bezahlt am'), Fields::user(label: 'Bezahlt von', withTrashed: true)])
                ->action(fn (array $data, Order $record, Action $action) => PanelAction::run($action, fn () => app(Orders::class)->update(auth()->user(), $record, $data))),
            PanelAction::make('autoOrder')->label('Beim Anbieter bestellen / aktualisieren')->authorize(fn (Order $record): bool => Gate::allows('autoOrder', $record))
                ->modalDescription('Die Bestellung wird an den Anbieter gesendet. Bei unklarem Ergebnis vor einem erneuten Versuch den Anbieterstatus klären.')
                ->action(fn (Order $record, Action $action) => PanelAction::run($action, fn () => app(AutoOrder::class)->execute(auth()->user(), $record))),
            PanelAction::make('reconcileAutoOrder')->label('Anbieterergebnis abgleichen')
                ->authorize(fn (Order $record): bool => Gate::allows('autoOrder', $record))
                ->visible(fn (Order $record): bool => $record->pendingAutoOrders()->exists())
                ->modalDescription('Prüfen Sie zuerst beim Anbieter, ob diese Bestellung angekommen ist. Bestätigen Sie ausschließlich das dort geprüfte Ergebnis. Laufende Versuche können frühestens nach 30 Minuten und nach Ende des aktiven Aufrufs geklärt werden. Es wird nichts an den Anbieter gesendet.')
                ->schema([
                    Select::make('attempt_id')->label('Ungeklärter Versuch')->required()
                        ->options(fn (Order $record): array => $record->pendingAutoOrders()->orderBy('id')->get()
                            ->mapWithKeys(fn (AdminActivity $attempt): array => [$attempt->id => 'Versuch '.$attempt->id.' · '.$attempt->created_at->format('d.m.Y H:i').' · '.($attempt->state === 'running' ? 'Laufend' : 'Ergebnis unklar')])->all())
                        ->default(fn (Order $record): ?int => $record->pendingAutoOrders()->value('id')),
                    Select::make('outcome')->label('Beim Anbieter geprüftes Ergebnis')->required()->options([
                        'confirmed' => 'Bestellung beim Anbieter bestätigt',
                        'not_received' => 'Nicht beim Anbieter angekommen',
                    ])->helperText('Bestätigt schließt die Sammelbestellung ab. Nicht angekommen erlaubt einen neuen, ausdrücklich gestarteten Versuch.'),
                ])
                ->action(fn (array $data, Order $record, Action $action) => PanelAction::run($action,
                    function () use ($data, $record, $action): void {
                        $order = app(AutoOrder::class)->reconcile(auth()->user(), $record, (int) $data['attempt_id'], $data['outcome']);
                        if ($data['outcome'] === 'confirmed' && $order->status === Order::STATUS_OPEN) {
                            $action->successNotificationTitle('Eingang bestätigt; die Bestellung wurde seither geändert und bleibt offen.');
                        }
                    })),
            PanelAction::make('remove')->label('Löschen')->color('danger')->authorize(fn (Order $record): bool => Gate::allows('delete', $record))
                ->action(fn (Order $record, Action $action) => PanelAction::run($action, fn () => app(Orders::class)->delete(auth()->user(), $record))),
        ]);
    }

    public static function statuses(): array
    {
        return [Order::STATUS_OPEN => 'Offen', Order::STATUS_ORDERED => 'Abgeschlossen / bestellt'];
    }

    public static function getPages(): array
    {
        return ['index' => ManageOrders::route('/')];
    }
}
