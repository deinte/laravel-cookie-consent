<?php

declare(strict_types=1);

namespace Deinte\CookieConsent\Texts;

use Deinte\CookieConsent\Contracts\TextProvider;
use Illuminate\Contracts\Translation\Translator;

class TranslationTextProvider implements TextProvider
{
    public function __construct(protected Translator $translator) {}

    public function texts(string $locale): array
    {
        $texts = $this->translator->get('cookie-consent::banner', [], $locale);

        if (! is_array($texts)) {
            return [];
        }

        return $texts;
    }
}
