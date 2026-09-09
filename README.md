# Laravel Cookie Consent

Cookiebot-style consent manager for Laravel: a consent banner with per-category
preferences, server-side and runtime script blocking, Google Consent Mode v2,
consent logging and an auto-generated cookie declaration. No frontend framework
required — the runtime is a ~12 KB vanilla IIFE inlined in `<head>` so it runs
before any tracker.

Works with full-page cached sites: consent lives client-side (cookie +
localStorage), the HTML never varies per visitor.

## Installation

```bash
composer require deinte/laravel-cookie-consent
php artisan vendor:publish --tag="cookie-consent-migrations"
php artisan migrate
php artisan vendor:publish --tag="cookie-consent-config"
```

Optional: publish views (`cookie-consent-views`) or translations (`cookie-consent-translations`).

## Usage

### 1. Layout

```blade
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <x-cookie-consent::head />   {{-- as early as possible, before any tracker --}}
    ...
</head>
<body>
    <x-cookie-consent::body />
    ...
    <footer>
        <x-cookie-consent::settings-link class="footer-link" />
    </footer>
</body>
```

`<x-cookie-consent::head />` emits the runtime config, critical CSS, the runtime
script, the banner template and every managed `head` script as
`<script type="text/plain" data-cookieconsent="…">`. Scripts in the `necessary`
category render as regular executable scripts.

### 2. Register scripts

From the database (`consent_scripts` table, see `ConsentScript`) or at runtime:

```php
use Deinte\CookieConsent\Facades\CookieConsent;
use Deinte\CookieConsent\Data\ScriptDefinition;
use Deinte\CookieConsent\Enums\ConsentCategory;

CookieConsent::registerScript(CookieConsent::googleAnalytics('G-XXXX'));
CookieConsent::registerScript(CookieConsent::googleTagManager('GTM-XXXX'));
CookieConsent::registerScript(fn () => ScriptDefinition::external(
    'Hotjar',
    'https://static.hotjar.com/c/hotjar-123.js',
    ConsentCategory::Statistics,
));
```

Wrap a one-off script in a view:

```blade
<x-cookie-consent::script category="marketing" src="https://connect.facebook.net/en_US/fbevents.js" async />
<x-cookie-consent::script category="statistics">window.myTracker.init();</x-cookie-consent::script>
```

Rewrite free-form HTML (user-provided snippets, rich text with embeds):

```blade
{!! CookieConsent::blockHtml($settings->custom_head_scripts, ConsentCategory::Marketing) !!}
```

### 3. Consent log endpoint

```php
// routes/web.php (inside the middleware group you want)
CookieConsent::routes();
```

Registers `POST /cookie-consent/log` (throttled, CSRF exempt). Records land in
`consent_logs` with a hashed IP. Prune with `php artisan cookie-consent:prune-logs`.

### 4. Cookie declaration

```blade
<x-cookie-consent::declaration />
```

Renders a table per category from the cookies attached to each script.

## Browser API

```js
window.CookieConsent.show();            // open preferences
window.CookieConsent.acceptAll();
window.CookieConsent.rejectAll();
window.CookieConsent.accept(['statistics']);
window.CookieConsent.hasConsent('marketing');
window.CookieConsent.getConsent();      // { id, v, h, ts, c: {...} } | null
window.CookieConsent.onReady(consent => {});
```

Events on `document`: `cookieconsent:ready`, `cookieconsent:changed`,
`cookieconsent:accept:<category>`. Any element with
`data-cookieconsent="show"` opens the preferences modal.

Google Consent Mode v2: defaults are pushed as `denied` before any Google tag
loads, `update` follows each decision and a `cookie_consent_update` dataLayer
event (`cc_statistics`, `cc_marketing`, …) is available as a GTM trigger.

## Customising

Bind your own implementations through `config/cookie-consent.php`:

| Contract | Purpose |
|---|---|
| `SettingsResolver` | Where banner settings come from (config, DB, tenant settings) |
| `ScriptRepository` | Where managed scripts come from |
| `TextProvider` | Banner copy per locale |

Theme the banner through CSS variables (`--cc-primary`, `--cc-bg`, `--cc-fg`,
`--cc-radius`, …) via the `banner.theme` setting or your own stylesheet.

## Auto-blocking: what it can and cannot do

The runtime hooks `document.createElement` and watches the DOM with a
`MutationObserver`, so scripts, iframes and beacons injected at runtime (e.g. by
GTM) are neutralised when their domain matches a rule. Limits:

- Inline scripts already parsed before the observer runs cannot be stopped —
  rewrite server-side with `blockHtml()` or `<x-cookie-consent::script>`.
- A parser-inserted `<script src>` may already be fetched by the preload scanner;
  execution is prevented, the request is not.
- `import()`, `fetch`, workers and CSS `url()` are out of scope.
- Unknown domains pass through unless `blocker.block_unknown` is enabled.

## Development

```bash
composer test && composer analyse && composer format
npm install && npm test && npm run build   # rebuild resources/dist
```

## License

MIT. See [LICENSE.md](LICENSE.md).
