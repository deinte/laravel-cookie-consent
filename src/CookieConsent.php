<?php

declare(strict_types=1);

namespace Deinte\CookieConsent;

use Closure;
use Deinte\CookieConsent\Contracts\ScriptRepository;
use Deinte\CookieConsent\Contracts\SettingsResolver;
use Deinte\CookieConsent\Contracts\TextProvider;
use Deinte\CookieConsent\Data\ConsentSettings;
use Deinte\CookieConsent\Data\CookieDeclaration;
use Deinte\CookieConsent\Data\ScriptDefinition;
use Deinte\CookieConsent\Enums\ConsentCategory;
use Deinte\CookieConsent\Enums\ScriptPosition;
use Deinte\CookieConsent\Http\Controllers\LogConsentController;
use Deinte\CookieConsent\Support\HtmlBlocker;
use Illuminate\Contracts\Container\Container;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Routing\Router;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\HtmlString;

class CookieConsent
{
    /** @var array<int, ScriptDefinition|Closure(): (ScriptDefinition|array<int, ScriptDefinition>|null)> */
    protected array $registered = [];

    protected ?ConsentSettings $settings = null;

    /** @var ?Collection<int, ScriptDefinition> */
    protected ?Collection $scripts = null;

    public function __construct(protected Container $container) {}

    public function isEnabled(): bool
    {
        return $this->settings()->enabled;
    }

    /**
     * Register a script the host renders without a database row (analytics IDs
     * from settings, framework-level tags). Closures are resolved lazily on
     * first render so they can read tenant or request state.
     *
     * @param  ScriptDefinition|Closure(): (ScriptDefinition|array<int, ScriptDefinition>|null)  $script
     */
    public function registerScript(ScriptDefinition|Closure $script): static
    {
        $this->registered[] = $script;
        $this->scripts = null;

        return $this;
    }

    public function settings(): ConsentSettings
    {
        return $this->settings ??= $this->container->make(SettingsResolver::class)->resolve();
    }

    /**
     * Drop memoised settings and scripts, e.g. after switching tenant.
     */
    public function refresh(): static
    {
        $this->settings = null;
        $this->scripts = null;

        return $this;
    }

    /** @return Collection<int, ScriptDefinition> */
    public function scripts(?ScriptPosition $position = null): Collection
    {
        $this->scripts ??= $this->collectScripts();

        if ($position === null) {
            return $this->scripts;
        }

        return $this->scripts
            ->filter(fn (ScriptDefinition $script): bool => $script->position === $position)
            ->values();
    }

    /** @return array<string, mixed> */
    public function texts(?string $locale = null): array
    {
        return $this->container->make(TextProvider::class)->texts($locale ?? app()->getLocale());
    }

    /**
     * @return array<int, array{
     *     key: string,
     *     required: bool,
     *     label: string,
     *     description: string
     * }>
     */
    public function categories(?string $locale = null): array
    {
        $texts = $this->texts($locale);

        return array_map(fn (ConsentCategory $category): array => [
            'key' => $category->value,
            'required' => $category->isRequired(),
            'label' => (string) ($texts['categories'][$category->value]['label'] ?? ucfirst($category->value)),
            'description' => (string) ($texts['categories'][$category->value]['description'] ?? ''),
        ], ConsentCategory::cases());
    }

    /**
     * Configuration handed to the browser runtime as `window.CookieConsentConfig`.
     *
     * @return array<string, mixed>
     */
    public function config(?string $locale = null): array
    {
        $settings = $this->settings();
        $texts = $this->texts($locale);
        $categories = $this->categories($locale);

        return [
            'version' => $settings->policyVersion,
            'policyHash' => $this->policyHash($locale),
            'locale' => $locale ?? app()->getLocale(),
            'policyUrl' => $settings->policyUrl,
            'logEndpoint' => $settings->logEnabled ? $this->logEndpoint() : null,
            'consentMode' => $settings->consentMode,
            'blockUnknown' => $settings->blockUnknown,
            'reloadOnRevoke' => $settings->reloadOnRevoke,
            'showReject' => $settings->showRejectButton,
            'cookie' => [
                'name' => $settings->cookieName,
                'days' => $settings->cookieDays,
                'domain' => $settings->cookieDomain,
            ],
            'layout' => $settings->layout,
            'position' => $settings->position,
            'theme' => $settings->theme,
            'categories' => $categories,
            'texts' => array_filter($texts, fn (mixed $value): bool => ! is_array($value)),
            'rules' => array_values($settings->rules),
        ];
    }

    /**
     * Changes whenever the copy, category list or policy version changes, so
     * visitors are re-prompted after a material update.
     */
    public function policyHash(?string $locale = null): string
    {
        $payload = json_encode([
            $this->settings()->policyVersion,
            $this->categories($locale),
            $this->scripts()->map(fn (ScriptDefinition $script): array => [$script->name, $script->category->value])->all(),
        ]);

        return substr(sha1((string) $payload), 0, 12);
    }

    public function blocker(): HtmlBlocker
    {
        return new HtmlBlocker($this->settings()->rules);
    }

    /**
     * Rewrite free-form HTML (tenant snippets, rich text embeds) so nothing in
     * it executes before consent. Unknown scripts fall back to `$default`.
     */
    public function blockHtml(?string $html, ConsentCategory $default = ConsentCategory::Marketing): HtmlString
    {
        if ($html === null) {
            return new HtmlString('');
        }

        if (! $this->isEnabled()) {
            return new HtmlString($html);
        }

        return new HtmlString($this->blocker()->block($html, $default));
    }

    public function googleAnalytics(string $measurementId, ScriptPosition $position = ScriptPosition::Head): ScriptDefinition
    {
        $id = $this->escapeJs($measurementId);

        $code = 'window.dataLayer=window.dataLayer||[];function gtag(){dataLayer.push(arguments);}'
            ."(function(d,s){var j=d.createElement(s);j.async=true;j.src='https://www.googletagmanager.com/gtag/js?id={$id}';d.head.appendChild(j);})(document,'script');"
            ."gtag('js',new Date());gtag('config','{$id}');";

        return ScriptDefinition::inline(
            name: 'Google Analytics',
            code: $code,
            category: ConsentCategory::Statistics,
            position: $position,
            cookies: [
                new CookieDeclaration('_ga', 'Google', 'Distinguishes visitors', '2 years'),
                new CookieDeclaration('_ga_*', 'Google', 'Persists session state', '2 years'),
            ],
            provider: 'Google',
            sortOrder: -100,
        );
    }

    /**
     * Tag Manager loads under "necessary" because Consent Mode v2 gates the
     * tags it fires; the container itself sets no cookies until consent.
     */
    public function googleTagManager(string $containerId, ScriptPosition $position = ScriptPosition::Head): ScriptDefinition
    {
        $id = $this->escapeJs($containerId);

        $code = "(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':new Date().getTime(),event:'gtm.js'});"
            ."var f=d.getElementsByTagName(s)[0],j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;"
            ."j.src='https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);})(window,document,'script','dataLayer','{$id}');";

        return ScriptDefinition::inline(
            name: 'Google Tag Manager',
            code: $code,
            category: ConsentCategory::Necessary,
            position: $position,
            provider: 'Google',
            sortOrder: -90,
        );
    }

    /**
     * Register the consent log endpoint inside the caller's route group.
     */
    public function routes(?Router $router = null): void
    {
        $prefix = (string) config('cookie-consent.logging.route_prefix', 'cookie-consent');
        $name = (string) config('cookie-consent.logging.route_name', 'cookieConsent.log');
        $throttle = (string) config('cookie-consent.logging.throttle', '30,1');

        $router ??= Route::getFacadeRoot();

        $router->post("{$prefix}/log", LogConsentController::class)
            ->middleware("throttle:{$throttle}")
            ->withoutMiddleware([ValidateCsrfToken::class])
            ->name($name);

        $router->getRoutes()->refreshNameLookups();
    }

    public function logEndpoint(): ?string
    {
        $name = (string) config('cookie-consent.logging.route_name', 'cookieConsent.log');

        if (! Route::has($name)) {
            return null;
        }

        return route($name, [], false);
    }

    public function runtimeScript(): string
    {
        $path = __DIR__.'/../resources/dist/cookie-consent.min.js';

        if (! is_file($path)) {
            return '';
        }

        return (string) file_get_contents($path);
    }

    public function runtimeStyles(): string
    {
        $path = __DIR__.'/../resources/dist/cookie-consent.min.css';

        if (! is_file($path)) {
            return '';
        }

        return (string) file_get_contents($path);
    }

    /** @return Collection<int, ScriptDefinition> */
    protected function collectScripts(): Collection
    {
        $fromRepository = $this->container->make(ScriptRepository::class)->all();

        $fromRegistry = collect($this->registered)
            ->flatMap(function (ScriptDefinition|Closure $script): array {
                if ($script instanceof ScriptDefinition) {
                    return [$script];
                }

                $resolved = $script();

                if ($resolved === null) {
                    return [];
                }

                return is_array($resolved) ? $resolved : [$resolved];
            });

        return $fromRegistry
            ->merge($fromRepository)
            ->sortBy(fn (ScriptDefinition $script): int => $script->sortOrder)
            ->values();
    }

    protected function escapeJs(string $value): string
    {
        return addcslashes($value, "'\\\n\r<>");
    }
}
