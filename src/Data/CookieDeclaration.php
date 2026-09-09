<?php

declare(strict_types=1);

namespace Deinte\CookieConsent\Data;

final readonly class CookieDeclaration
{
    public function __construct(
        public string $name,
        public ?string $provider = null,
        public ?string $purpose = null,
        public ?string $expiry = null,
    ) {}

    /** @param array{name?: string, provider?: ?string, purpose?: ?string, expiry?: ?string} $data */
    public static function fromArray(array $data): self
    {
        return new self(
            name: (string) ($data['name'] ?? ''),
            provider: $data['provider'] ?? null,
            purpose: $data['purpose'] ?? null,
            expiry: $data['expiry'] ?? null,
        );
    }

    /** @return array{name: string, provider: ?string, purpose: ?string, expiry: ?string} */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'provider' => $this->provider,
            'purpose' => $this->purpose,
            'expiry' => $this->expiry,
        ];
    }
}
