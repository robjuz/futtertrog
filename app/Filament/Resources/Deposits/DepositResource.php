<?php

namespace App\Filament\Resources\Deposits;

use App\Actions\Admin\Bookings;
use App\Filament\Resources\{AdminResource, Fields, PanelAction};
use App\Filament\Resources\Deposits\Pages\ManageDeposits;
use App\Models\Deposit;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Gate;

class DepositResource extends AdminResource
{
    protected static ?string $slug = 'bookings';
    protected static ?string $model = Deposit::class;
    protected static ?string $pluralModelLabel = 'Einzahlungen';
    protected static ?string $modelLabel = 'Einzahlung';

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['user' => fn (mixed $q): mixed => $q->withTrashed()]);
    }

    public static function bookingFields(): array
    {
        return [Fields::user(withTrashed: true)->required(), Fields::cents(), Textarea::make('comment')->label('Kommentar')];
    }

    public static function headerActions(): array
    {
        return [
            PanelAction::make('book')->label('Einzahlung buchen')->authorize(fn (): bool => Gate::allows('create', Deposit::class))
                ->schema([...static::bookingFields(), Fields::operationKey()])
                ->action(fn (array $data, Action $action) => PanelAction::run($action, fn () => app(Bookings::class)->create(auth()->user(), Arr::except($data, 'operation_key'), $data['operation_key']))),
            PanelAction::make('transfer')->label('Guthaben übertragen')->authorize(fn (): bool => Gate::allows('create', Deposit::class))
                ->schema([Fields::user('source', 'Von')->required(), Fields::user('target', 'An')->required(), Fields::cents(), Textarea::make('comment')->label('Kommentar'), Fields::operationKey()])
                ->action(fn (array $data, Action $action) => PanelAction::run($action, fn () => app(Bookings::class)->transfer(auth()->user(), Arr::except($data, 'operation_key'), $data['operation_key']))),
        ];
    }

    public static function table(Table $table): Table
    {
        return $table->defaultSort('id', 'desc')->paginationPageOptions([10, 25, 50])->columns([
            TextColumn::make('id')->label('Nr.')->searchable()->sortable(),
            TextColumn::make('user.name')->label('Benutzer')->searchable()->sortable(),
            TextColumn::make('value')->label('Betrag')->money('EUR', divideBy: 100)->sortable(),
            TextColumn::make('comment')->label('Kommentar')->searchable()->wrap(),
            TextColumn::make('status')->label('Status')->formatStateUsing(fn (string $state): string => $state === Deposit::STATUS_OK ? 'Gebucht' : 'In Bearbeitung'),
            TextColumn::make('created_at')->label('Erstellt')->dateTime('d.m.Y H:i')->sortable(),
        ])->filters([
            SelectFilter::make('user_id')->label('Benutzer')->relationship('user', 'name', fn (Builder $query): Builder => $query->withTrashed())->searchable(),
            SelectFilter::make('status')->label('Status')->options(['ok' => 'Gebucht', 'processing' => 'In Bearbeitung']), Fields::dates('created_at'),
        ])->recordActions([
            PanelAction::make('change')->label('Bearbeiten')->authorize(fn (Deposit $record): bool => Gate::allows('update', $record))
                ->fillForm(fn (Deposit $record): array => $record->only(['user_id', 'value', 'comment']))->schema(static::bookingFields())
                ->action(fn (array $data, Deposit $record, Action $action) => PanelAction::run($action, fn () => app(Bookings::class)->update(auth()->user(), $record, $data))),
            PanelAction::make('remove')->label('Löschen')->color('danger')->authorize(fn (Deposit $record): bool => Gate::allows('delete', $record))
                ->action(fn (Deposit $record, Action $action) => PanelAction::run($action, fn () => app(Bookings::class)->delete(auth()->user(), $record))),
        ]);
    }

    public static function getPages(): array
    {
        return ['index' => ManageDeposits::route('/')];
    }
}
