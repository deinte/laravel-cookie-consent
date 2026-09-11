# Changelog

All notable changes to `laravel-cookie-consent` will be documented in this file.

## v0.0.1 - 2026-09-11

Initial release.

- Consent banner and preferences modal with the `necessary`, `preferences`,
  `statistics` and `marketing` categories, in `bar` or `box` layout, themeable
  through `--cc-*` CSS custom properties.
- Blade components: `<x-cookie-consent::head />`, `<x-cookie-consent::body />`,
  `<x-cookie-consent::script />`, `<x-cookie-consent::declaration />` and
  `<x-cookie-consent::settings-link />`.
- Vanilla-JS runtime, inlined in `<head>` by default (or served from
  `public/vendor/cookie-consent` with `script_delivery => 'asset'`), exposing
  `window.CookieConsent` and `cookieconsent:*` DOM events.
- Server-side blocking of free-form HTML with `CookieConsent::blockHtml()`, plus
  runtime auto-blocking of scripts, iframes and tracking pixels via configurable
  domain rules, with placeholders for blocked embeds.
- Google Consent Mode v2: denied-by-default signals before any Google tag, an
  `update` per decision and a `cookie_consent_update` dataLayer event for GTM.
- Managed scripts from the `consent_scripts` table or registered at runtime
  (eagerly or through a lazily resolved closure), with `googleAnalytics()` and
  `googleTagManager()` helpers.
- Cookie declaration generated from the cookies attached to each script.
- Consent logging to `consent_logs` with a salted IP hash, a `ConsentLogged`
  event and the `cookie-consent:prune-logs` command.
- Policy version and policy hash re-prompt visitors after a material change.
- Swappable `SettingsResolver`, `ScriptRepository` and `TextProvider`
  implementations; English, Dutch and French translations included.
