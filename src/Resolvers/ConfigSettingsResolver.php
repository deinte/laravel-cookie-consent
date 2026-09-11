<?php

declare(strict_types=1);

namespace Deinte\CookieConsent\Resolvers;

use Deinte\CookieConsent\Contracts\SettingsResolver;
use Deinte\CookieConsent\Data\ConsentSettings;

class ConfigSettingsResolver implements SettingsResolver
{
    public function resolve(): ConsentSettings
    {
        $config = config('cookie-consent');

        return new ConsentSettings(
            enabled: (bool) ($config['enabled'] ?? true),
            layout: (string) ($config['banner']['layout'] ?? 'bar'),
            position: (string) ($config['banner']['position'] ?? 'bottom'),
            theme: (array) ($config['banner']['theme'] ?? []),
            policyUrl: $config['policy']['url'] ?? null,
            policyVersion: (string) ($config['policy']['version'] ?? '1'),
            consentMode: (bool) ($config['google_consent_mode'] ?? true),
            logEnabled: (bool) ($config['logging']['enabled'] ?? true),
            logEndpoint: $config['logging']['endpoint'] ?? null,
            blockUnknown: (bool) ($config['blocker']['block_unknown'] ?? false),
            reloadOnRevoke: (bool) ($config['banner']['reload_on_revoke'] ?? false),
            cookieName: (string) ($config['cookie']['name'] ?? 'cc_consent'),
            cookieDays: (int) ($config['cookie']['days'] ?? 180),
            cookieDomain: $config['cookie']['domain'] ?? null,
            rules: (array) ($config['blocker']['rules'] ?? []),
            showRejectButton: (bool) ($config['banner']['show_reject_button'] ?? true),
        );
    }
}
