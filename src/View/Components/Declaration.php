<?php

declare(strict_types=1);

namespace Deinte\CookieConsent\View\Components;

use Deinte\CookieConsent\CookieConsent;
use Deinte\CookieConsent\Data\ScriptDefinition;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class Declaration extends Component
{
    public function __construct(
        protected CookieConsent $manager,
        public ?string $locale = null,
        public bool $showSettingsLink = true,
    ) {}

    public function render(): View
    {
        $locale = $this->locale ?? app()->getLocale();
        $scripts = $this->manager->scripts();

        $groups = collect($this->manager->categories($locale))
            ->map(fn (array $category): array => [
                ...$category,
                'scripts' => $scripts
                    ->filter(fn (ScriptDefinition $script): bool => $script->category->value === $category['key'])
                    ->values(),
            ]);

        return view('cookie-consent::components.declaration', [
            'groups' => $groups,
            'texts' => $this->manager->texts($locale),
            'settings' => $this->manager->settings(),
        ]);
    }
}
