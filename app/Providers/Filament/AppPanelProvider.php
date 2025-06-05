<?php

namespace App\Providers\Filament;

use App\Settings\GeneralSettings;
use DutchCodingCompany\FilamentSocialite\Exceptions\ImplementationException;
use DutchCodingCompany\FilamentSocialite\FilamentSocialitePlugin;
use DutchCodingCompany\FilamentSocialite\Provider;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Assets\Css;
use Filament\Support\Assets\Js;
use Filament\Support\Colors\Color;
use Filament\Support\Enums\MaxWidth;
use Filament\Widgets;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Vite;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Jeffgreco13\FilamentBreezy\BreezyCore;

class AppPanelProvider extends PanelProvider
{
    /**
     * @throws ImplementationException
     * @throws \Exception
     */
    public function panel(Panel $panel): Panel
    {
        $panel
            ->default()
            ->id('app')
            ->darkMode()
            ->maxContentWidth(MaxWidth::Full)
            ->sidebarCollapsibleOnDesktop()
            ->plugin(BreezyCore::make()->myProfile(hasAvatars: true))
            ->colors([
                'primary' => Color::Blue,
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([Pages\Dashboard::class])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->widgets([
                Widgets\AccountWidget::class,
                Widgets\FilamentInfoWidget::class,
            ])
            ->databaseNotifications()
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
            ])
            ->navigationGroups([
                __('Management'),
                __('Referential'),
                __('Security'),
                __('Settings'),
            ])
            ->assets([
                Css::make('filament-stylesheet', Vite::asset('resources/css/filament.scss')),
                Css::make('tippy-stylesheet', 'https://unpkg.com/tippy.js@6/dist/tippy.css'),
                Js::make('filament-script', Vite::asset('resources/js/filament.js'))->module(),
            ])
            ->emailVerification()
            ->passwordReset();

        $settings = app(GeneralSettings::class);

        if ($settings->enable_registration) {
            $panel->registration();
        }

        if ($settings->enable_login_form) {
            $panel->login();
        }

        if ($settings->enable_social_login) {
            $panel->plugin(
                FilamentSocialitePlugin::make()
                    ->providers([
                        Provider::make('google')
                            ->label('Google')
                            ->icon('fab-google')
                            ->color(Color::hex('#4285F4')),
                    ])
                    ->registration(true)
                    ->userModelClass(\App\Models\User::class)
            );
        }

        return $panel;
    }
}
