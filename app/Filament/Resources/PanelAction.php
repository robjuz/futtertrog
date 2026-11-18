<?php

namespace App\Filament\Resources;

use Closure;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Throwable;

class PanelAction extends Action
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->databaseTransaction(false)->requiresConfirmation()
            ->modalDescription('Bitte bestätigen Sie diesen Vorgang.')
            ->modalSubmitActionLabel('Bestätigen')->modalCancelActionLabel('Abbrechen');
    }

    public static function run(Action $action, Closure $operation, ?string $deniedMessage = null): void
    {
        try {
            $operation();
        } catch (ValidationException $exception) {
            Notification::make()->danger()->title('Bitte prüfen Sie Ihre Eingaben.')
                ->body(collect($exception->errors())->flatten()->implode(' '))->send();
            throw $exception;
        } catch (Throwable $exception) {
            $message = match (true) {
                $exception instanceof AuthorizationException => $deniedMessage ?? 'Für diesen Vorgang fehlt die Berechtigung. Bitte laden Sie die Seite neu.',
                $exception instanceof ModelNotFoundException => 'Der Datensatz ist nicht mehr verfügbar. Bitte laden Sie die Seite neu.',
                $exception instanceof HttpExceptionInterface && $exception->getStatusCode() === 409 => 'Der Vorgang läuft bereits, wurde mit anderen Daten gesendet oder sein Ergebnis ist unklar (409). Bitte vor einem neuen Versuch klären.',
                $exception instanceof HttpExceptionInterface && $exception->getStatusCode() === 400 => 'Dieser Vorgang ist im aktuellen Zustand nicht möglich.',
                $exception instanceof HttpExceptionInterface && $exception->getStatusCode() < 500 => $exception->getMessage(),
                default => 'Der Vorgang ist fehlgeschlagen. Bei einer Anbieteraktion kann das Ergebnis unklar sein. Bitte prüfen Sie den Zustand vor einem neuen Versuch.',
            };
            if (!($exception instanceof AuthorizationException) && !($exception instanceof HttpExceptionInterface) && !($exception instanceof ModelNotFoundException)) {
                report($exception);
            }
            Notification::make()->danger()->title('Vorgang nicht ausgeführt.')->body($message)->send();
            $action->halt();
        }
        Notification::make()->success()->title($action->getSuccessNotificationTitle() ?? 'Vorgang erfolgreich.')->send();
    }
}
