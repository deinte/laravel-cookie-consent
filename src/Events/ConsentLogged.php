<?php

declare(strict_types=1);

namespace Deinte\CookieConsent\Events;

use Deinte\CookieConsent\Models\ConsentLog;
use Illuminate\Foundation\Events\Dispatchable;

class ConsentLogged
{
    use Dispatchable;

    public function __construct(public ConsentLog $log) {}
}
