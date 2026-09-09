<?php

declare(strict_types=1);

namespace Deinte\CookieConsent\Data;

use Deinte\CookieConsent\Enums\ConsentCategory;
use Deinte\CookieConsent\Enums\ScriptKind;
use Deinte\CookieConsent\Enums\ScriptPosition;

final readonly class ScriptDefinition
{
    /**
     * @param  array<string, string|bool>  $attributes  Extra attributes copied onto the activated script tag.
     * @param  array<int, CookieDeclaration>  $cookies  Cookies this script sets, listed in the declaration.
     */
    public function __construct(
        public string $name,
        public ConsentCategory $category,
        public ScriptKind $kind,
        public ScriptPosition $position = ScriptPosition::Head,
        public ?string $src = null,
        public ?string $code = null,
        public array $attributes = [],
        public array $cookies = [],
        public ?string $provider = null,
        public int $sortOrder = 0,
    ) {}

    /**
     * @param  array<string, string|bool>  $attributes
     * @param  array<int, CookieDeclaration>  $cookies
     */
    public static function external(
        string $name,
        string $src,
        ConsentCategory $category,
        ScriptPosition $position = ScriptPosition::Head,
        array $attributes = [],
        array $cookies = [],
        ?string $provider = null,
        int $sortOrder = 0,
    ): self {
        return new self(
            name: $name,
            category: $category,
            kind: ScriptKind::External,
            position: $position,
            src: $src,
            attributes: $attributes,
            cookies: $cookies,
            provider: $provider,
            sortOrder: $sortOrder,
        );
    }

    /**
     * @param  array<string, string|bool>  $attributes
     * @param  array<int, CookieDeclaration>  $cookies
     */
    public static function inline(
        string $name,
        string $code,
        ConsentCategory $category,
        ScriptPosition $position = ScriptPosition::Head,
        array $attributes = [],
        array $cookies = [],
        ?string $provider = null,
        int $sortOrder = 0,
    ): self {
        return new self(
            name: $name,
            category: $category,
            kind: ScriptKind::Inline,
            position: $position,
            code: $code,
            attributes: $attributes,
            cookies: $cookies,
            provider: $provider,
            sortOrder: $sortOrder,
        );
    }

    public function isExternal(): bool
    {
        return $this->kind === ScriptKind::External;
    }
}
