<?php

namespace App\Filament\Resources\Users;

use App\Actions\Admin\Users;
use App\Actions\Auth\CredentialChallenges;
use App\Filament\Resources\{AdminResource, PanelAction};
use App\Filament\Resources\Users\Pages\{ManageUsers, ViewUser};
use App\Models\{CredentialChallenge, User};
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Forms\Components\{TextInput, Toggle};
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
        return [PanelAction::make('create')->label('Benutzer anlegen')
            ->authorize(fn (): bool => Gate::allows('create', User::class))
            ->modalDescription('Das Konto startet ohne lokales Passwort. Senden Sie anschließend über „Passwortbestätigung senden“ den bestehenden Bestätigungslink.')
            ->schema([...static::identityFields(), Toggle::make('is_admin')->label('Administrator')->default(false)])
            ->action(fn (array $data, Action $action) => PanelAction::run($action, function () use ($data): void {
                $user = app(Users::class)->create(auth()->user(), $data);
                Notification::make()->success()->title('Benutzer angelegt.')->persistent()
                    ->body('Das Konto richtet sein Passwort über die bestehende E-Mail-Bestätigung ein.')
                    ->actions([Action::make('passwordLink')->label('Passwortbestätigung senden')
                        ->url(static::getUrl(parameters: ['tableAction' => 'passwordLink', 'tableActionRecord' => $user->id]))])->send();
            }))];
    }

    public static function identityFields(): array
    {
        return [TextInput::make('name')->label('Name')->required()->maxLength(255),
            TextInput::make('email')->label('E-Mail')->email()->required()->maxLength(255)->unique(table: User::class, column: 'email', ignoreRecord: true)];
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
                ->visible(fn (User $record): bool => !$record->trashed())->url(fn (User $record): string => static::getUrl('view', ['record' => $record])),
            PanelAction::make('edit')->label('Name / E-Mail ändern')->authorize(fn (User $record): bool => Gate::allows('update', $record))
                ->visible(fn (User $record): bool => !$record->trashed())
                ->fillForm(fn (User $record): array => $record->only(['name', 'email']))->schema(static::identityFields())
                ->action(fn (array $data, User $record, Action $action) => PanelAction::run($action, fn () => app(Users::class)->update(auth()->user(), $record, $data))),
            PanelAction::make('role')->label('Rechte ändern')->authorize(fn (User $record): bool => Gate::allows('update', $record))
                ->fillForm(fn (User $record): array => ['is_admin' => (bool) $record->is_admin])->schema([Toggle::make('is_admin')->label('Administrator')->required()])
                ->action(fn (array $data, User $record, Action $action) => PanelAction::run($action, function () use ($data, $record, $action): void {
                    $user = app(Users::class)->changeRole(auth()->user(), $record, $data['is_admin']);
                    if ($user->is(auth()->user()) && !$user->is_admin) {
                        $action->getLivewire()->redirectRoute('home');
                    }
                }, 'Der letzte aktive Administrator darf seine Rechte nicht verlieren.')),
            PanelAction::make('deactivate')->label('Deaktivieren')->color('danger')->visible(fn (User $record): bool => !$record->trashed())
                ->authorize(fn (): bool => Gate::allows('viewAny', User::class))
                ->action(fn (User $record, Action $action) => PanelAction::run($action, fn () => app(Users::class)->deactivate(auth()->user(), $record), 'Das eigene Konto und der letzte aktive Administrator dürfen nicht deaktiviert werden.')),
            PanelAction::make('restore')->label('Wiederherstellen')->visible(fn (User $record): bool => $record->trashed())
                ->authorize(fn (User $record): bool => Gate::allows('restore', $record))
                ->action(fn (User $record, Action $action) => PanelAction::run($action, fn () => app(Users::class)->restore(auth()->user(), $record))),
            PanelAction::make('passwordLink')->label('Passwortbestätigung senden')->visible(fn (User $record): bool => !$record->trashed())
                ->authorize(fn (User $record): bool => Gate::allows('update', $record))
                ->modalDescription(CredentialChallenges::PUBLIC_MESSAGE)
                ->action(fn (User $record, Action $action) => PanelAction::run($action, function () use ($record): void {
                    $actor = User::withTrashed()->findOrFail(auth()->id());
                    Gate::forUser($actor)->authorize('update', $record);
                    app(CredentialChallenges::class)->send(User::findOrFail($record->id), CredentialChallenge::RESET_PASSWORD);
                })),
        ]);
    }

    public static function getPages(): array
    {
        return ['index' => ManageUsers::route('/'), 'view' => ViewUser::route('/{record}')];
    }
}
