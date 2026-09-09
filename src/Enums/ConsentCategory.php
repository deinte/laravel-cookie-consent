<?php

declare(strict_types=1);

namespace Deinte\CookieConsent\Enums;

enum ConsentCategory: string
{
    case Necessary = 'necessary';
    case Preferences = 'preferences';
    case Statistics = 'statistics';
    case Marketing = 'marketing';

    public function isRequired(): bool
    {
        return $this === self::Necessary;
    }

    /** @return array<int, string> */
    public static function values(): array
    {
        return array_map(fn (self $category): string => $category->value, self::cases());
    }

    /** @return array<int, string> */
    public static function optionalValues(): array
    {
        return array_values(array_filter(
            self::values(),
            fn (string $value): bool => $value !== self::Necessary->value,
        ));
    }
}
