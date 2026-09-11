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

## Publishing

| Tag | Publishes to |
|---|---|
| `cookie-consent-config` | `config/cookie-consent.php` |
| `cookie-consent-migrations` | `database/migrations/…_create_consent_scripts_table.php` and `…_create_consent_logs_table.php` |
| `cookie-consent-views` | `resources/views/vendor/cookie-consent/` |
| `cookie-consent-translations` | `lang/vendor/cookie-consent/` (`en`, `nl`, `fr`) |
| `cookie-consent-assets` | `public/vendor/cookie-consent/` (the built `cookie-consent.min.js` / `.min.css`) |

Only the config and migrations are needed to get going; views and translations
are published to override them, assets only for `script_delivery => 'asset'`.

### `script_delivery`

- `inline` (default) — the minified runtime is read from
  `resources/dist/cookie-consent.min.js` and embedded in a `<script id="cc-runtime">`
  inside `<head>`. No extra request, and it executes before any tracker can.
- `asset` — the head component links
  `asset('vendor/cookie-consent/cookie-consent.min.js')` instead, so the file is
  cacheable and CSP-friendlier. Publish `cookie-consent-assets` first, and
  re-publish it whenever the package is updated.

The stylesheet follows the same mode: `inline` embeds it as `<style id="cc-css">`,
`asset` links `asset('vendor/cookie-consent/cookie-consent.min.css')` as
`<link rel="stylesheet" id="cc-css">`.

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

Both components take a `locale` prop; `head` also takes a `nonce` that is copied
onto the `<script>` tags it emits.

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

Renders a table per category from the cookies attached to each script. Props:
`locale` and `show-settings-link` (defaults to `true`).

### 5. Reopening the preferences modal

Visitors must be able to change their mind. Any of these works:

```blade
<x-cookie-consent::settings-link />
<x-cookie-consent::settings-link label="Manage cookies" class="footer-link" />

<button type="button" data-cookieconsent="show">Cookie settings</button>
```

```js
window.CookieConsent.show();
```

`<x-cookie-consent::settings-link />` renders an `<a href="#" data-cookieconsent="show">`
labelled from the `settings_link` text, overridable with the `label` prop. The
runtime delegates a single click listener on the document, so any element
carrying `data-cookieconsent="show"` — including markup added after page load —
opens the preferences modal. The component renders nothing when the package is
disabled.

## Configuration

Every key in `config/cookie-consent.php`:

| Key | What it does |
|---|---|
| `enabled` | Master switch (`COOKIE_CONSENT_ENABLED`). When off, no banner, config or runtime is emitted and every managed script renders as a plain executable script. |
| `banner.layout` | `bar` (full-width strip) or `box` (floating card). |
| `banner.position` | `bottom`, `top`, `bottom-left`, `bottom-right` or `center`. |
| `banner.show_reject_button` | Show "reject all" beside "accept all" — equal prominence, as most EU DPAs expect. |
| `banner.reload_on_revoke` | Reload the page when a previously granted category is withdrawn, since executed scripts cannot be unloaded. |
| `banner.theme` | Map of CSS custom properties applied to the banner root, e.g. `['--cc-primary' => '#0f766e']`. |
| `policy.url` | URL of the cookie/privacy policy, linked from the banner. `null` hides the link. |
| `policy.version` | Consent version string. Bump it to re-prompt everyone (see below). |
| `google_consent_mode` | Emit Consent Mode v2 defaults and updates. |
| `blocker.block_unknown` | Fail-closed: block scripts, iframes and beacons from domains matching no rule. Safest, but breaks unlisted embeds. |
| `blocker.rules` | Ordered list of `['pattern' => …, 'category' => …]`; the pattern is a plain substring match against the URL (or inline code) and **the first match wins**. Ships with rules for Google Tag Manager, Google Analytics, Google Ads/DoubleClick, Facebook, Hotjar, Clarity, LinkedIn, TikTok, X/Twitter, Pinterest, YouTube, Vimeo, Google Maps embeds, Umami, Plausible and Matomo. |
| `cookie.name` | Name of the first-party cookie and of the localStorage key holding the decision. |
| `cookie.days` | Lifetime of the stored decision in days (EU guidance: re-ask at least yearly). |
| `cookie.domain` | Cookie domain, e.g. `.example.com` to share consent across subdomains. |
| `logging.enabled` | Record every decision as proof of consent. When off the endpoint returns `204` without writing. |
| `logging.route_prefix` | Prefix of the route registered by `CookieConsent::routes()` (`{prefix}/log`). |
| `logging.route_name` | Route name; also how the runtime endpoint URL is resolved. |
| `logging.endpoint` | Explicit URL the runtime posts decisions to. Override when the host exposes the log route under another URL, e.g. behind a proxy prefix. `null` (default) derives it from the named route. |
| `logging.throttle` | Rate limiter string applied to the endpoint, e.g. `30,1`. |
| `logging.retention_days` | Default age cutoff for `cookie-consent:prune-logs`. |
| `logging.ip_salt` | Salt mixed into the IP hash (`COOKIE_CONSENT_IP_SALT`). Falls back to `config('app.key')`. |
| `tables.scripts` / `tables.logs` | Table names, honoured by the models and the migrations. |
| `models.script` / `models.log` | Model classes, so you can extend `ConsentScript` / `ConsentLog`. |
| `script_repository` | `ScriptRepository` implementation; defaults to `EloquentScriptRepository`. |
| `settings_resolver` | `SettingsResolver` implementation; defaults to `ConfigSettingsResolver`. |
| `text_provider` | `TextProvider` implementation; defaults to `TranslationTextProvider` (reads `cookie-consent::banner`). |
| `script_delivery` | `inline` or `asset` — applies to both the runtime script and the stylesheet, see [Publishing](#publishing). |

## Managed scripts table

`consent_scripts` rows are turned into `ScriptDefinition`s by
`EloquentScriptRepository` (active rows only, ordered by `sort_order` then `id`):

| Column | Meaning |
|---|---|
| `name` | Human label, shown in the declaration. |
| `provider` | Third party behind the script, used as the declaration fallback provider. |
| `category` | `necessary`, `preferences`, `statistics` or `marketing`. |
| `position` | `head` or `body` — which component renders it. |
| `kind` | `external` (uses `src`) or `inline` (uses `code`). |
| `src` | Script URL for `external` scripts. |
| `code` | Inline JavaScript for `inline` scripts. |
| `script_attributes` | JSON map of extra attributes copied onto the activated tag, e.g. `{"async": true, "data-domain": "example.com"}`. |
| `cookies` | JSON list feeding the cookie declaration (see below). |
| `sort_order` | Render order. |
| `is_active` | Inactive rows are skipped entirely. |

`cookies` is a list of objects:

```json
[
    {"name": "_ga", "provider": "Google", "purpose": "Distinguishes visitors", "expiry": "2 years"}
]
```

Each entry becomes a `CookieDeclaration` and one row in
`<x-cookie-consent::declaration />`. A script without cookies still gets a row,
listed by name.

The same shape is available without the database through
`ScriptDefinition::external()` and `ScriptDefinition::inline()`:

```php
use Deinte\CookieConsent\Data\CookieDeclaration;
use Deinte\CookieConsent\Data\ScriptDefinition;
use Deinte\CookieConsent\Enums\ConsentCategory;
use Deinte\CookieConsent\Enums\ScriptPosition;

ScriptDefinition::external(
    name: 'Plausible',
    src: 'https://plausible.io/js/script.js',
    category: ConsentCategory::Statistics,
    position: ScriptPosition::Head,
    attributes: ['defer' => true, 'data-domain' => 'example.com'],
    cookies: [new CookieDeclaration('_pa', 'Plausible', 'Measures page views', 'Session')],
    provider: 'Plausible',
    sortOrder: 10,
);
```

Registered definitions and database rows are merged and sorted together by
`sortOrder`, so a runtime-registered tag can be placed before or after the
managed ones.

## Re-prompting visitors

A stored decision carries the consent id, the policy version (`v`), a policy
hash (`h`), a timestamp and the per-category booleans. The runtime ignores it —
and shows the banner again — when the version or the hash no longer matches the
current page.

`CookieConsent::policyHash()` is the first 12 characters of a `sha1` over:

1. `policy.version`,
2. the rendered categories for the locale, including their labels and descriptions,
3. the name and category of every registered script.

So adding a tracker, moving one to another category or editing the category copy
re-prompts automatically. Bump `policy.version` yourself for a material change
the hash cannot see — a new purpose, a new processor, a rewritten policy page.

The consent id is *not* regenerated: the runtime keeps the id of the previous
record when it builds a new one, so successive `consent_logs` rows for the same
visitor stay linked.

## Consent logging

When `logging.enabled` is true and `CookieConsent::routes()` has been called, the
runtime posts every decision to the log endpoint — `navigator.sendBeacon` first,
`fetch(…, {keepalive: true})` as a fallback:

```json
{
    "id": "1f0b…-uuid",
    "categories": ["necessary", "statistics"],
    "version": "1",
    "policyHash": "9c1b2f0ad3e1",
    "url": "https://example.com/pricing"
}
```

The controller then:

- adds `necessary` to `categories` (it is always granted) and de-duplicates;
- stores the IP only as `sha256("{salt}|{ip}")`, salted with `logging.ip_salt`
  and falling back to `config('app.key')` — the raw address is never written;
- truncates the user agent to 250 characters;
- writes a `consent_logs` row and dispatches `ConsentLogged` with the model;
- answers `204 No Content` (also when logging is disabled).

Listen for the event to fan out to your own audit trail:

```php
use Deinte\CookieConsent\Events\ConsentLogged;

Event::listen(fn (ConsentLogged $event) => logger()->info($event->log->consent_id));
```

Prune on a schedule:

```php
Schedule::command('cookie-consent:prune-logs')->daily();      // uses logging.retention_days
Schedule::command('cookie-consent:prune-logs --days=180')->daily();
```

Two deployment notes:

- The route is registered **inside the caller's route group**, so it inherits
  your `web` middleware, locale prefixes and domain constraints. Call
  `CookieConsent::routes()` in `routes/web.php` (or pass a `Router` explicitly:
  `CookieConsent::routes($router)`). It is throttled with
  `logging.throttle` and exempt from `ValidateCsrfToken` so beacons from a
  cached page never fail on a stale token.
- If the site sits behind full-page caching (Varnish, a CDN, a response-cache
  package), exclude the log path from the cache — a cached `204` would silently
  swallow every subsequent decision.

## Google Consent Mode v2 and GTM

With `google_consent_mode` enabled the runtime defines `gtag()`/`dataLayer` and
pushes defaults **before any Google tag can load** — everything denied except
`security_storage`, plus `wait_for_update: 500` so tags briefly wait for a stored
decision:

```js
gtag('consent', 'default', {
    ad_storage: 'denied',
    ad_user_data: 'denied',
    ad_personalization: 'denied',
    analytics_storage: 'denied',
    functionality_storage: 'denied',
    personalization_storage: 'denied',
    security_storage: 'granted',
    wait_for_update: 500,
});
```

Each decision pushes an `update` with this mapping. A stored decision found on
load always pushes one too — including a stored "reject all", which is signalled
explicitly as denied rather than left to the defaults:

| Category | Consent Mode signals |
|---|---|
| `statistics` | `analytics_storage` |
| `marketing` | `ad_storage`, `ad_user_data`, `ad_personalization` |
| `preferences` | `functionality_storage`, `personalization_storage` |
| — | `security_storage` is always `granted` |

Alongside the `update`, a dataLayer event is pushed:

```js
dataLayer.push({
    event: 'cookie_consent_update',
    cc_necessary: true,
    cc_preferences: false,
    cc_statistics: true,
    cc_marketing: false,
});
```

`CookieConsent::googleTagManager()` registers the container under **`necessary`**
on purpose: the container itself sets no cookies, and Consent Mode gates the tags
it fires. Blocking the container instead would also block the consent signals it
needs to forward. Individual tags stay gated by their own categories (the default
`blocker.rules` classify `googletagmanager.com/gtag/js` as `statistics` and the
ad domains as `marketing`).

In GTM, wire tags to the event:

1. **Triggers → New → Custom Event**, event name `cookie_consent_update`.
2. Optionally add a condition on a Data Layer Variable, e.g. `cc_marketing` equals `true`.
3. Use that trigger on tags that must only fire after consent, and enable
   "additional consent checks" on tags that rely on Consent Mode instead.

## Browser API

```js
window.CookieConsent.show();            // open preferences
window.CookieConsent.showBanner();
window.CookieConsent.hide();
window.CookieConsent.acceptAll();
window.CookieConsent.rejectAll();
window.CookieConsent.accept(['statistics']);
window.CookieConsent.hasConsent('marketing');
window.CookieConsent.hasDecided();
window.CookieConsent.getConsent();      // { id, v, h, ts, c: {...} } | null
window.CookieConsent.getGranted();      // ['necessary', 'statistics']
window.CookieConsent.reset();           // forget the decision, show the banner
window.CookieConsent.onReady(consent => {});
```

Events on `document`: `cookieconsent:ready`, `cookieconsent:changed`,
`cookieconsent:accept:<category>`. Any element with
`data-cookieconsent="show"` opens the preferences modal.

Google Consent Mode v2: defaults are pushed as `denied` before any Google tag
loads, an `update` follows each decision and every stored decision on load
(a stored "reject all" included), and a `cookie_consent_update` dataLayer event
(`cc_statistics`, `cc_marketing`, …) is available as a GTM trigger.

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

## Multi-tenant and long-running runtimes

`CookieConsent` is bound as a **singleton** and memoises both the resolved
settings and the collected scripts on first use. That is what you want inside one
request; it is a leak across tenants or across requests in a persistent worker.
Call `CookieConsent::refresh()` to drop both caches (`registerScript()` already
clears the script cache on its own).

Switching tenant — with Spatie's multitenancy, for example:

```php
use Deinte\CookieConsent\Facades\CookieConsent;
use Spatie\Multitenancy\Events\MadeTenantCurrentEvent;

Event::listen(fn (MadeTenantCurrentEvent $event) => CookieConsent::refresh());
```

On Octane (Swoole, RoadRunner, FrankenPHP) the container survives between
requests, so refresh on the request boundary too:

```php
use Deinte\CookieConsent\Facades\CookieConsent;
use Laravel\Octane\Events\RequestReceived;

Event::listen(fn (RequestReceived $event) => CookieConsent::refresh());
```

Closures passed to `registerScript()` are only resolved the first time scripts
are rendered, so they can safely read tenant, request or settings state:

```php
CookieConsent::registerScript(fn () => tenant()->ga_measurement_id
    ? CookieConsent::googleAnalytics(tenant()->ga_measurement_id)
    : null);
```

A closure may return a single `ScriptDefinition`, an array of them, or `null` to
register nothing.

## Development

```bash
composer test && composer analyse && composer format
npm install && npm test && npm run build   # rebuild resources/dist
```

## Versioning

This package follows [semantic versioning](https://semver.org). The first tagged
release is `v0.0.1`; while the major version is `0`, minor bumps may contain
breaking changes, so pin it:

```json
"deinte/laravel-cookie-consent": "^0.0.1"
```

See [CHANGELOG.md](CHANGELOG.md) for what changed between releases.

## License

MIT. See [LICENSE.md](LICENSE.md).
