<?php

namespace App\Filament\Pages;

use App\Actions\Admin\SendNotification;
use App\Filament\Resources\{Fields, PanelAction};
use App\Models\User;
use Filament\Actions\Action;
use Filament\Forms\Components\{Textarea, TextInput};
use Filament\Pages\Page;
use Illuminate\Support\Facades\Gate;

class Notifications extends Page
{
    protected static ?string $title = 'Mitteilungen';
    protected string $view = 'filament.pages.notifications';

    public static function canAccess(): bool
    {
        $actor = User::withTrashed()->find(auth()->id());

        return $actor && Gate::forUser($actor)->allows('send-notifications');
    }

    protected function getHeaderActions(): array
    {
        return [PanelAction::make('send')->label('Mitteilung versenden')->authorize(fn (): bool => static::canAccess())
            ->schema([Fields::user()->multiple()->required(), TextInput::make('subject')->label('Betreff')->required()->maxLength(255), Textarea::make('body')->label('Nachricht')->required()])
            ->action(fn (array $data, Action $action) => PanelAction::run($action, fn () => app(SendNotification::class)->execute(auth()->user(), $data)))];
    }
}
