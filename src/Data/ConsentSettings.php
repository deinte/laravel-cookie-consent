<?php

declare(strict_types=1);

namespace Deinte\CookieConsent\Data;

final readonly class ConsentSettings
{
    /**
     * @param  array<string, string>  $theme  CSS custom properties applied to the banner root (e.g. `--cc-primary`).
     * @param  array<int, array{pattern: string, category: string}>  $rules  Domain patterns the auto-blocker classifies.
     */
    public function __construct(
        public bool $enabled = true,
        public string $layout = 'bar',
        public string $position = 'bottom',
        public array $theme = [],
        public ?string $policyUrl = null,
        public string $policyVersion = '1',
        public bool $consentMode = true,
        public bool $logEnabled = true,
        public ?string $logEndpoint = null,
        public bool $blockUnknown = false,
        public bool $reloadOnRevoke = false,
        public string $cookieName = 'cc_consent',
        public int $cookieDays = 180,
        public ?string $cookieDomain = null,
        public array $rules = [],
        public bool $showRejectButton = true,
    ) {}
}
