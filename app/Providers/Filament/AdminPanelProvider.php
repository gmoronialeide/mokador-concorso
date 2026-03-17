<?php

namespace App\Providers\Filament;

use Filament\Http\Middleware\Authenticate;
use Filament\Navigation\NavigationGroup;
use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            ->authGuard('admin')
            ->colors([
                'primary' => Color::hex('#9D4A15'),
                'gray' => Color::hex('#4F3328'),
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
