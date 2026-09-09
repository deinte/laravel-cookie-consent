<?php

declare(strict_types=1);

namespace Deinte\CookieConsent\Facades;

use Deinte\CookieConsent\CookieConsent as CookieConsentManager;
use Illuminate\Support\Facades\Facade;

/**
 * @method static bool isEnabled()
 * @method static CookieConsentManager registerScript(\Deinte\CookieConsent\Data\ScriptDefinition|\Closure $script)
 * @method static \Deinte\CookieConsent\Data\ConsentSettings settings()
 * @method static CookieConsentManager refresh()
 * @method static \Illuminate\Support\Collection<int, \Deinte\CookieConsent\Data\ScriptDefinition> scripts(?\Deinte\CookieConsent\Enums\ScriptPosition $position = null)
 * @method static array<string, mixed> texts(?string $locale = null)
 * @method static array<int, array{key: string, required: bool, label: string, description: string}> categories(?string $locale = null)
 * @method static array<string, mixed> config(?string $locale = null)
 * @method static string policyHash(?string $locale = null)
 * @method static \Illuminate\Support\HtmlString blockHtml(?string $html, \Deinte\CookieConsent\Enums\ConsentCategory $default = \Deinte\CookieConsent\Enums\ConsentCategory::Marketing)
 * @method static \Deinte\CookieConsent\Data\ScriptDefinition googleAnalytics(string $measurementId, \Deinte\CookieConsent\Enums\ScriptPosition $position = \Deinte\CookieConsent\Enums\ScriptPosition::Head)
 * @method static \Deinte\CookieConsent\Data\ScriptDefinition googleTagManager(string $containerId, \Deinte\CookieConsent\Enums\ScriptPosition $position = \Deinte\CookieConsent\Enums\ScriptPosition::Head)
 * @method static void routes(?\Illuminate\Routing\Router $router = null)
 * @method static ?string logEndpoint()
 * @method static string runtimeScript()
 * @method static string runtimeStyles()
 *
 * @see CookieConsentManager
 */
class CookieConsent extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return CookieConsentManager::class;
    }
}
