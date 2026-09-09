<?php

declare(strict_types=1);

namespace Deinte\CookieConsent\Enums;

enum ScriptPosition: string
{
    case Head = 'head';
    case Body = 'body';
}
