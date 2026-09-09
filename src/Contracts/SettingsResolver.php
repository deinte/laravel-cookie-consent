<?php

declare(strict_types=1);

namespace Deinte\CookieConsent\Contracts;

use Deinte\CookieConsent\Data\ConsentSettings;

interface SettingsResolver
{
    public function resolve(): ConsentSettings;
}
