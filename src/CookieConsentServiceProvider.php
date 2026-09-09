<?php

declare(strict_types=1);

namespace Deinte\CookieConsent;

use Deinte\CookieConsent\Commands\PruneConsentLogsCommand;
use Deinte\CookieConsent\Contracts\ScriptRepository;
use Deinte\CookieConsent\Contracts\SettingsResolver;
use Deinte\CookieConsent\Contracts\TextProvider;
use Deinte\CookieConsent\Repositories\EloquentScriptRepository;
use Deinte\CookieConsent\Resolvers\ConfigSettingsResolver;
use Deinte\CookieConsent\Texts\TranslationTextProvider;
use Illuminate\Support\Facades\Blade;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class CookieConsentServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('cookie-consent')
            ->hasConfigFile()
            ->hasViews()
            ->hasTranslations()
            ->hasAssets()
            ->hasMigrations(['create_consent_scripts_table', 'create_consent_logs_table'])
            ->hasCommand(PruneConsentLogsCommand::class);
    }

    public function packageRegistered(): void
    {
        $this->app->singleton(CookieConsent::class);

        $this->app->bind(
            ScriptRepository::class,
            fn ($app) => $app->make(config('cookie-consent.script_repository', EloquentScriptRepository::class)),
        );

        $this->app->bind(
            SettingsResolver::class,
            fn ($app) => $app->make(config('cookie-consent.settings_resolver', ConfigSettingsResolver::class)),
        );

        $this->app->bind(
            TextProvider::class,
            fn ($app) => $app->make(config('cookie-consent.text_provider', TranslationTextProvider::class)),
        );
    }

    public function packageBooted(): void
    {
        Blade::componentNamespace('Deinte\\CookieConsent\\View\\Components', 'cookie-consent');
    }
}
