<?php

namespace App\Providers\Filament;

use App\Filament\Middleware\EnsurePanelAccess;
use App\Filament\Widgets\AdministrationStats;
use App\Http\Middleware\Authenticate;
use App\Http\Middleware\AuthenticateCredentialSession;
use App\Http\Middleware\UserLocale;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationItem;
use App\Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Routing\Router;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdministrationPanelProvider extends PanelProvider
{
    public function boot(Router $router): void
    {
        $router->pushMiddlewareToGroup('filament.actions', Authenticate::class.':web');
        $router->pushMiddlewareToGroup('filament.actions', EnsurePanelAccess::class);
    }

    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('administration')
            ->path(config('filament.administration_path'))
            ->authGuard('web')
            ->brandName('Administration')
            ->colors(['primary' => Color::Amber])
            ->viteTheme('resources/css/admin.css')
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->pages([Dashboard::class])->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->widgets([AdministrationStats::class])
            ->navigationItems([
                NavigationItem::make('Bestellen')->url(fn (): string => url('/meals')),
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                ShareErrorsFromSession::class,
                PreventRequestForgery::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class.':web',
                AuthenticateCredentialSession::class,
                EnsurePanelAccess::class,
                UserLocale::class,
            ], isPersistent: true);
    }
}
