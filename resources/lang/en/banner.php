<?php

declare(strict_types=1);

return [
    'title' => 'This website uses cookies',
    'body' => 'We use cookies to make the site work, to analyse how it is used and to tailor content to your preferences. You decide which cookies to allow.',
    'accept_all' => 'Accept all',
    'reject_all' => 'Necessary only',
    'manage' => 'Preferences',
    'manage_title' => 'Cookie preferences',
    'manage_body' => 'Choose per category which cookies you allow. Necessary cookies are always on.',
    'save' => 'Save choice',
    'close' => 'Close',
    'policy_link' => 'Read our cookie policy',
    'settings_link' => 'Cookie settings',
    'placeholder_title' => 'Content blocked',
    'placeholder_body' => 'This content only loads once you allow marketing cookies.',
    'placeholder_button' => 'Allow and load',
    'categories' => [
        'necessary' => [
            'label' => 'Necessary',
            'description' => 'Required for the website to work: navigation, forms and security. Cannot be switched off.',
        ],
        'preferences' => [
            'label' => 'Preferences',
            'description' => 'Remember choices such as language or region so the site adapts to you.',
        ],
        'statistics' => [
            'label' => 'Statistics',
            'description' => 'Help us understand how visitors use the site by collecting anonymous information.',
        ],
        'marketing' => [
            'label' => 'Marketing',
            'description' => 'Track visitors across websites to show relevant ads and embedded content.',
        ],
    ],
    'declaration' => [
        'name' => 'Name',
        'provider' => 'Provider',
        'purpose' => 'Purpose',
        'expiry' => 'Expiry',
        'empty' => 'No cookies are set in this category.',
    ],
];
