<?php

declare(strict_types=1);

namespace Deinte\CookieConsent\View\Components;

use Deinte\CookieConsent\CookieConsent;
use Deinte\CookieConsent\Enums\ScriptPosition;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class Head extends Component
{
    public function __construct(
        protected CookieConsent $manager,
        public ?string $locale = null,
        public ?string $nonce = null,
    ) {}

    public function render(): View
    {
        $locale = $this->locale ?? app()->getLocale();
        $delivery = (string) config('cookie-consent.script_delivery', 'inline');

        return view('cookie-consent::components.head', [
            'enabled' => $this->manager->isEnabled(),
            'config' => $this->manager->config($locale),
            'texts' => $this->manager->texts($locale),
            'categories' => $this->manager->categories($locale),
            'settings' => $this->manager->settings(),
            'scripts' => $this->manager->scripts(ScriptPosition::Head),
            'runtime' => $delivery === 'inline' ? $this->manager->runtimeScript() : null,
            'runtimeUrl' => $delivery === 'inline' ? null : asset('vendor/cookie-consent/cookie-consent.min.js'),
            'styles' => $this->manager->runtimeStyles(),
        ]);
    }
}
