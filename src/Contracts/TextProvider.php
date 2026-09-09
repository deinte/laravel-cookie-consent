<?php

declare(strict_types=1);

namespace Deinte\CookieConsent\Contracts;

interface TextProvider
{
    /**
     * Banner copy for a locale. Expected keys: title, body, accept_all, reject_all,
     * manage, save, close, policy_link, powered_by, placeholder_title,
     * placeholder_body, placeholder_button, declaration.*, categories.{key}.label,
     * categories.{key}.description.
     *
     * @return array<string, mixed>
     */
    public function texts(string $locale): array;
}
