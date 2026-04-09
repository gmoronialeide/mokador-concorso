<?php

namespace App\Providers\Filament;

use App\Filament\Pages\Auth\Login;
use Filament\Http\Middleware\Authenticate;
use Filament\Navigation\NavigationGroup;
use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Widgets;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login(Login::class)
            ->authGuard('admin')
            ->colors([
                'primary' => [
                    50 => '245, 241, 237',
                    100 => '230, 222, 214',
                    200 => '199, 185, 171',
                    300 => '163, 143, 127',
                    400 => '120, 98, 82',
                    500 => '88, 62, 51',
                    600 => '75, 52, 42',
                    700 => '62, 42, 33',
                    800 => '50, 34, 27',
                    900 => '40, 27, 21',
                    950 => '28, 18, 14',
                ],
            ])
            ->brandName('Mokador Concorso')
            ->brandLogo(asset('img/mokador-concorso-logo.svg'))
            ->brandLogoHeight('2.5rem')
            ->darkMode(false)
            ->navigationGroups([
                NavigationGroup::make('Concorso'),
                NavigationGroup::make('Anagrafiche'),
                NavigationGroup::make('Sistema'),
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([
                Pages\Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->widgets([
                Widgets\AccountWidget::class,
            ])
            ->middleware([
                'web',
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
