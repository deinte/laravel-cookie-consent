<?php

declare(strict_types=1);

namespace Deinte\CookieConsent\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $consent_id
 * @property array<int, string> $categories
 * @property string $policy_version
 * @property ?string $policy_hash
 * @property ?string $ip_hash
 * @property ?string $user_agent
 * @property ?string $url
 * @property Carbon $created_at
 */
class ConsentLog extends Model
{
    public const UPDATED_AT = null;

    protected $guarded = [];

    public function getTable(): string
    {
        return config('cookie-consent.tables.logs', 'consent_logs');
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'categories' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function hasConsentFor(string $category): bool
    {
        return in_array($category, $this->categories, true);
    }
}
