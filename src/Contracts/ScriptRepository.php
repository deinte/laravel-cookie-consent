<?php

declare(strict_types=1);

namespace Deinte\CookieConsent\Contracts;

use Deinte\CookieConsent\Data\ScriptDefinition;
use Illuminate\Support\Collection;

interface ScriptRepository
{
    /** @return Collection<int, ScriptDefinition> */
    public function all(): Collection;
}
