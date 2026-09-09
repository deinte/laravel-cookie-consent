<?php

declare(strict_types=1);

namespace Deinte\CookieConsent\View\Components;

use Deinte\CookieConsent\CookieConsent;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class SettingsLink extends Component
{
    public function __construct(
        protected CookieConsent $manager,
        public ?string $label = null,
        public ?string $locale = null,
    ) {}

    public function render(): View
    {
        $texts = $this->manager->texts($this->locale ?? app()->getLocale());

        return view('cookie-consent::components.settings-link', [
            'enabled' => $this->manager->isEnabled(),
            'linkLabel' => $this->label ?? ($texts['settings_link'] ?? 'Cookie settings'),
        ]);
    }
}
