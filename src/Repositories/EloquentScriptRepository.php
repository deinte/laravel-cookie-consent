<?php

declare(strict_types=1);

namespace Deinte\CookieConsent\Repositories;

use Deinte\CookieConsent\Contracts\ScriptRepository;
use Deinte\CookieConsent\Data\ScriptDefinition;
use Deinte\CookieConsent\Models\ConsentScript;
use Illuminate\Support\Collection;

class EloquentScriptRepository implements ScriptRepository
{
    public function all(): Collection
    {
        /** @var class-string<ConsentScript> $model */
        $model = config('cookie-consent.models.script', ConsentScript::class);

        /** @var Collection<int, ConsentScript> $scripts */
        $scripts = $model::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        return $scripts
            ->map(fn (ConsentScript $script): ScriptDefinition => $script->toDefinition())
            ->values();
    }
}
