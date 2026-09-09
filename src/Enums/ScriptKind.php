<?php

declare(strict_types=1);

namespace Deinte\CookieConsent\Enums;

enum ScriptKind: string
{
    case External = 'external';
    case Inline = 'inline';
}
