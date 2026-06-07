<?php

namespace App\Providers\Filament;

use App\Enums\CustomizationKey;
use App\Filament\Pages\Auth\EditProfile;
use App\Filament\Pages\Auth\Login;
use App\Http\Middleware\LanguageMiddleware;
use App\Http\Middleware\RequireTwoFactorAuthentication;
use Filament\Actions\Action;
use Filament\Auth\MultiFactor\App\AppAuthentication;
use Filament\Auth\MultiFactor\Email\EmailAuthentication;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Panel;
use Filament\PanelProvider as BasePanelProvider;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Support\HtmlString;
use Illuminate\Session\Middleware\AuthenticateSession;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

abstract class PanelProvider extends BasePanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->spa(fn () => !request()->routeIs('filament.server.pages.console'))
            ->spaUrlExceptions([
                '*/oauth/redirect/*',
            ])
            ->databaseNotifications()
            ->brandName(config('app.name', 'Gecko'))
            // Filament hides the text brand name whenever a logo image is
            // set (the name becomes alt text only), so the logo is rendered
            // here as HTML alongside the visible title rather than through
            // ->brandLogo(), which would silently drop the "Gecko" text.
            ->brandLogo(fn () => config('app.logo')
                ? new HtmlString(sprintf(
                    // Inline styles (not Tailwind utility classes) are used here
                    // because this HTML is built from a PHP string and never
                    // scanned by Tailwind's JIT compiler — classes like `h-9`
                    // would be purged from the compiled CSS and silently do
                    // nothing, leaving the logo at its native (huge) size.
                    '<div style="display:flex;align-items:center;gap:0.5rem"><img src="%s" alt="%s" style="height:2.25rem;width:auto" /><span style="font-size:1.25rem;line-height:1.75rem;font-weight:700">%s</span></div>',
                    e(config('app.logo')),
                    e(config('app.name', 'Gecko')),
                    e(config('app.name', 'Gecko')),
                ))
                : null)
            ->brandLogoHeight('2.25rem')
            ->favicon(config('app.favicon', '/favicon.ico'))
            ->renderHook('panels::head.end', fn () => '<script src="/js/pelican-theme.js"></script>')
            ->topNavigation(function () {
                $navigationType = user()?->getCustomization(CustomizationKey::TopNavigation);

                return $navigationType === 'topbar' || $navigationType === true;
            })
            ->topbar(function () {
                $navigationType = user()?->getCustomization(CustomizationKey::TopNavigation);

                return $navigationType === 'topbar' || $navigationType === 'mixed' || $navigationType === true;
            })
            ->maxContentWidth(config('panel.filament.display-width', 'screen-2xl'))
            ->profile(EditProfile::class, false)
            ->userMenuItems([
                'profile' => fn (Action $action) => $action
                    ->url(fn () => EditProfile::getUrl(panel: 'app')),
            ])
            ->login(Login::class)
            ->passwordReset()
            ->multiFactorAuthentication([
                AppAuthentication::make()->recoverable(),
                EmailAuthentication::make(),
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
                LanguageMiddleware::class,
            ])
            ->authMiddleware([
                Authenticate::class,
                RequireTwoFactorAuthentication::class,
            ]);
    }
}
