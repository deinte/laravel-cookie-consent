<?php

declare(strict_types=1);

namespace Deinte\CookieConsent\View\Components;

use Deinte\CookieConsent\CookieConsent;
use Deinte\CookieConsent\Enums\ConsentCategory;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

/**
 * Wrap a single script so it only runs after consent:
 * <x-cookie-consent::script category="statistics" src="https://..." />
 * <x-cookie-consent::script category="marketing">fbq('init', '…');</x-cookie-consent::script>
 */
class Script extends Component
{
    public ConsentCategory $consentCategory;

    public function __construct(
        protected CookieConsent $manager,
        string $category = 'marketing',
        public ?string $src = null,
        public bool $async = false,
        public bool $defer = false,
    ) {
        $this->consentCategory = ConsentCategory::from($category);
    }

    public function render(): View
    {
        return view('cookie-consent::components.script', [
            'enabled' => $this->manager->isEnabled(),
            'category' => $this->consentCategory,
        ]);
    }
}
