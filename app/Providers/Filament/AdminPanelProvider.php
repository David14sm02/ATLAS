<?php

namespace App\Providers\Filament;

use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\View\PanelsRenderHook;
use Filament\Widgets;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\HtmlString;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            ->brandName('ATLAS TecNM')
            // Tipografía oficial Noto Sans (Manual de Identidad Gráfica TecNM Pág. 10)
            ->font('Noto Sans')
            // Paleta oficial TecNM y Gobierno de México (MIG Págs. 9 y 15)
            ->colors([
                'primary' => Color::hex('#1B396A'), // Azul TecNM (Pantone 294 C)
                'gray' => Color::Slate,             // Grises neutros de apoyo
                'warning' => Color::hex('#A57F2C'), // Dorado institucional (Pantone 1255 C)
                'success' => Color::hex('#1E5B4F'), // Verde oficial (Pantone 626 C)
                'danger' => Color::hex('#9B2247'),  // Guinda institucional (Pantone 7420 C)
                'info' => Color::hex('#1B396A'),
            ])
            // Inyectar el Lema Institucional en el pie del panel
            ->renderHook(
                PanelsRenderHook::FOOTER,
                fn (): HtmlString => new HtmlString('
                    <div class="w-full py-4 text-center text-xs tracking-widest text-gray-400 dark:text-gray-500 uppercase font-semibold border-t border-gray-100 dark:border-gray-800">
                        Tecnológico Nacional de México • "EXCELENCIA EN EDUCACIÓN TECNOLÓGICA®"
                    </div>
                ')
            )
            ->discoverClusters(in: app_path('Filament/Clusters'), for: 'App\\Filament\\Clusters')
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([
                Pages\Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->widgets([
                Widgets\AccountWidget::class,
                Widgets\FilamentInfoWidget::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
