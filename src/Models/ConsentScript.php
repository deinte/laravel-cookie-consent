<?php

declare(strict_types=1);

namespace Deinte\CookieConsent\Models;

use Deinte\CookieConsent\Data\CookieDeclaration;
use Deinte\CookieConsent\Data\ScriptDefinition;
use Deinte\CookieConsent\Enums\ConsentCategory;
use Deinte\CookieConsent\Enums\ScriptKind;
use Deinte\CookieConsent\Enums\ScriptPosition;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $name
 * @property ?string $provider
 * @property ConsentCategory $category
 * @property ScriptPosition $position
 * @property ScriptKind $kind
 * @property ?string $src
 * @property ?string $code
 * @property ?array<string, string|bool> $script_attributes
 * @property ?array<int, array{name: string, provider: ?string, purpose: ?string, expiry: ?string}> $cookies
 * @property int $sort_order
 * @property bool $is_active
 */
class ConsentScript extends Model
{
    protected $guarded = [];

    public function getTable(): string
    {
        return config('cookie-consent.tables.scripts', 'consent_scripts');
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'category' => ConsentCategory::class,
            'position' => ScriptPosition::class,
            'kind' => ScriptKind::class,
            'script_attributes' => 'array',
            'cookies' => 'array',
            'sort_order' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function toDefinition(): ScriptDefinition
    {
        return new ScriptDefinition(
            name: $this->name,
            category: $this->category,
            kind: $this->kind,
            position: $this->position,
            src: $this->src,
            code: $this->code,
            attributes: $this->script_attributes ?? [],
            cookies: array_map(
                fn (array $cookie): CookieDeclaration => CookieDeclaration::fromArray($cookie),
                $this->cookies ?? [],
            ),
            provider: $this->provider,
            sortOrder: $this->sort_order,
        );
    }
}
