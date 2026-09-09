<?php

declare(strict_types=1);

namespace Deinte\CookieConsent\View\Components;

use Deinte\CookieConsent\CookieConsent;
use Deinte\CookieConsent\Enums\ScriptPosition;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class Body extends Component
{
    public function __construct(protected CookieConsent $manager) {}

    public function render(): View
    {
        return view('cookie-consent::components.body', [
            'enabled' => $this->manager->isEnabled(),
            'scripts' => $this->manager->scripts(ScriptPosition::Body),
        ]);
    }
}
