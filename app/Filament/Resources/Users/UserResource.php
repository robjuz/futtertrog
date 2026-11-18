<?php

namespace App\Filament\Resources\Users;

use App\Actions\Admin\Users;
use App\Actions\Auth\CredentialChallenges;
use App\Filament\Resources\{AdminResource, PanelAction};
use App\Filament\Resources\Users\Pages\ManageUsers;
use App\Models\{CredentialChallenge, User};
use Filament\Actions\Action;
use Filament\Forms\Components\Toggle;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Gate;

class UserResource extends AdminResource
{
    protected static ?string $model = User::class;
    protected static ?string $slug = 'people';
    protected static ?string $pluralModelLabel = 'Benutzer';
    protected static ?string $modelLabel = 'Benutzer';

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->withTrashed()->select(['id', 'name', 'email', 'is_admin', 'deleted_at']);
    }

    public static function headerActions(): array
    {
        return [Action::make('legacyCreate')->label('Benutzer anlegen (bestehende Verwaltung)')
            ->authorize(fn (): bool => Gate::allows('create', User::class))->url(fn (): string => route('users.create'))];
    }

    public static function table(Table $table): Table
    {
        return $table->defaultSort('name')->paginationPageOptions([10, 25, 50])->columns([
            TextColumn::make('name')->label('Name')->searchable()->sortable(),
            TextColumn::make('email')->label('E-Mail')->searchable()->sortable(),
            IconColumn::make('is_admin')->label('Administrator')->boolean(),
            TextColumn::make('deleted_at')->label('Deaktiviert seit')->dateTime('d.m.Y H:i')->placeholder('Aktiv'),
        ])->filters([TrashedFilter::make()->label('Kontostatus')->default(null), TernaryFilter::make('is_admin')->label('Administrator')])->recordActions([
            Action::make('history')->label('Guthaben / Verlauf')->authorize(fn (User $record): bool => Gate::allows('view', $record))
                ->visible(fn (User $record): bool => !$record->trashed())->url(fn (User $record): string => route('users.show', $record)),
            Action::make('legacyEdit')->label('Name / E-Mail (bestehende Verwaltung)')->authorize(fn (User $record): bool => Gate::allows('update', $record))
                ->visible(fn (User $record): bool => !$record->trashed())->url(fn (User $record): string => route('users.edit', $record)),
            PanelAction::make('role')->label('Rechte ändern')->authorize(fn (User $record): bool => Gate::allows('update', $record))
                ->fillForm(fn (User $record): array => ['is_admin' => (bool) $record->is_admin])->schema([Toggle::make('is_admin')->label('Administrator')->required()])
                ->action(fn (array $data, User $record, Action $action) => PanelAction::run($action, fn () => app(Users::class)->changeRole(auth()->user(), $record, $data['is_admin']), 'Der letzte aktive Administrator darf seine Rechte nicht verlieren.')),
            PanelAction::make('deactivate')->label('Deaktivieren')->color('danger')->visible(fn (User $record): bool => !$record->trashed())
                ->authorize(fn (): bool => Gate::allows('viewAny', User::class))
                ->action(fn (User $record, Action $action) => PanelAction::run($action, fn () => app(Users::class)->deactivate(auth()->user(), $record), 'Das eigene Konto und der letzte aktive Administrator dürfen nicht deaktiviert werden.')),
            PanelAction::make('restore')->label('Wiederherstellen')->visible(fn (User $record): bool => $record->trashed())
                ->authorize(fn (User $record): bool => Gate::allows('restore', $record))
                ->action(fn (User $record, Action $action) => PanelAction::run($action, fn () => app(Users::class)->restore(auth()->user(), $record))),
            PanelAction::make('passwordLink')->label('Passwortbestätigung senden')->visible(fn (User $record): bool => !$record->trashed())
                ->authorize(fn (User $record): bool => Gate::allows('update', $record))
                ->modalDescription(CredentialChallenges::PublicMessage)
                ->action(fn (User $record, Action $action) => PanelAction::run($action, function () use ($record): void {
                    $actor = User::withTrashed()->findOrFail(auth()->id());
                    Gate::forUser($actor)->authorize('update', $record);
                    app(CredentialChallenges::class)->send(User::findOrFail($record->id), CredentialChallenge::ResetPassword);
                })),
        ]);
    }

    public static function getPages(): array
    {
        return ['index' => ManageUsers::route('/')];
    }
}
