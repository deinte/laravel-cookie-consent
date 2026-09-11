<?php

declare(strict_types=1);

use Deinte\CookieConsent\Models\ConsentLog;
use Deinte\CookieConsent\Models\ConsentScript;
use Deinte\CookieConsent\Repositories\EloquentScriptRepository;
use Deinte\CookieConsent\Resolvers\ConfigSettingsResolver;
use Deinte\CookieConsent\Texts\TranslationTextProvider;

return [
    /*
     * When disabled, the head component renders every managed script as a plain
     * executable script and no banner, config or runtime is emitted.
     */
    'enabled' => env('COOKIE_CONSENT_ENABLED', true),

    'banner' => [
        /*
         * Supported: "bar" (full-width strip) or "box" (floating card).
         */
        'layout' => 'bar',

        /*
         * Supported: "bottom", "top", "bottom-left", "bottom-right", "center".
         */
        'position' => 'bottom',

        /*
         * Show a "reject all" button next to "accept all". Required for an
         * equal-prominence choice under most EU DPA guidance.
         */
        'show_reject_button' => true,

        /*
         * Reload the page when a visitor withdraws a previously granted category,
         * since already-executed scripts cannot be unloaded.
         */
        'reload_on_revoke' => false,

        /*
         * CSS custom properties applied to the banner root. Any `--cc-*` variable
         * from the stylesheet can be overridden here.
         */
        'theme' => [
            // '--cc-primary' => '#0f766e',
        ],
    ],

    'policy' => [
        /*
         * Absolute or relative URL of the cookie / privacy policy page.
         */
        'url' => null,

        /*
         * Bump this whenever the policy or the script list changes materially.
         * Visitors whose stored consent carries an older version are asked again.
         */
        'version' => '1',
    ],

    /*
     * Emit Google Consent Mode v2 defaults (all denied) before any Google tag
     * loads, and push updates when the visitor decides.
     */
    'google_consent_mode' => true,

    'blocker' => [
        /*
         * Block scripts, iframes and beacons from domains that match no rule.
         * Fail-closed is safest but can break unrelated embeds; keep false unless
         * every third party on the site is listed below.
         */
        'block_unknown' => false,

        /*
         * Domain (substring) patterns the runtime classifies when it sees a
         * script, iframe or image being inserted. Order matters: first match wins.
         */
        'rules' => [
            ['pattern' => 'googletagmanager.com/gtag/js', 'category' => 'statistics'],
            ['pattern' => 'google-analytics.com', 'category' => 'statistics'],
            ['pattern' => 'analytics.google.com', 'category' => 'statistics'],
            ['pattern' => 'googletagmanager.com', 'category' => 'necessary'],
            ['pattern' => 'googleadservices.com', 'category' => 'marketing'],
            ['pattern' => 'doubleclick.net', 'category' => 'marketing'],
            ['pattern' => 'googlesyndication.com', 'category' => 'marketing'],
            ['pattern' => 'connect.facebook.net', 'category' => 'marketing'],
            ['pattern' => 'facebook.com/tr', 'category' => 'marketing'],
            ['pattern' => 'hotjar.com', 'category' => 'statistics'],
            ['pattern' => 'clarity.ms', 'category' => 'statistics'],
            ['pattern' => 'snap.licdn.com', 'category' => 'marketing'],
            ['pattern' => 'px.ads.linkedin.com', 'category' => 'marketing'],
            ['pattern' => 'analytics.tiktok.com', 'category' => 'marketing'],
            ['pattern' => 'static.ads-twitter.com', 'category' => 'marketing'],
            ['pattern' => 'pinimg.com', 'category' => 'marketing'],
            ['pattern' => 'youtube.com/embed', 'category' => 'marketing'],
            ['pattern' => 'youtube-nocookie.com', 'category' => 'marketing'],
            ['pattern' => 'player.vimeo.com', 'category' => 'marketing'],
            ['pattern' => 'google.com/maps/embed', 'category' => 'marketing'],
            ['pattern' => 'cloud.umami.is', 'category' => 'statistics'],
            ['pattern' => 'plausible.io', 'category' => 'statistics'],
            ['pattern' => 'matomo', 'category' => 'statistics'],
        ],
    ],

    'cookie' => [
        /*
         * Name of the first-party cookie (and localStorage key) that stores the decision.
         */
        'name' => 'cc_consent',

        /*
         * Lifetime of the stored decision in days. EU guidance suggests re-asking
         * at least yearly.
         */
        'days' => 180,

        /*
         * Cookie domain, e.g. ".example.com" to share consent across subdomains.
         */
        'domain' => null,
    ],

    'logging' => [
        /*
         * Record every decision through the log endpoint as proof of consent.
         */
        'enabled' => true,

        /*
         * Route prefix and name registered by CookieConsent::routes().
         */
        'route_prefix' => 'cookie-consent',
        'route_name' => 'cookieConsent.log',

        /*
         * Override when the host exposes the log route under another URL, for
         * example behind a proxy prefix. `null` derives it from the named route.
         */
        'endpoint' => null,

        /*
         * Throttle applied to the log endpoint (Laravel rate limiter syntax).
         */
        'throttle' => '30,1',

        /*
         * Days to keep logs; `cookie-consent:prune-logs` deletes older rows.
         */
        'retention_days' => 400,

        /*
         * Salt mixed into the IP hash so raw addresses are never stored.
         * Defaults to the application key.
         */
        'ip_salt' => env('COOKIE_CONSENT_IP_SALT'),
    ],

    'tables' => [
        'scripts' => 'consent_scripts',
        'logs' => 'consent_logs',
    ],

    'models' => [
        'script' => ConsentScript::class,
        'log' => ConsentLog::class,
    ],

    /*
     * Swap any of these to change where scripts, settings and copy come from.
     */
    'script_repository' => EloquentScriptRepository::class,
    'settings_resolver' => ConfigSettingsResolver::class,
    'text_provider' => TranslationTextProvider::class,

    /*
     * How the runtime JavaScript and stylesheet are delivered: "inline" embeds
     * the minified bundle and CSS in <head> (no extra request, blocks before any
     * tracker), "asset" links both published files from
     * public/vendor/cookie-consent.
     */
    'script_delivery' => 'inline',
];
