<?php

declare(strict_types=1);

namespace Deinte\CookieConsent\Tests;

use Deinte\CookieConsent\Data\ScriptDefinition;
use Deinte\CookieConsent\Enums\ConsentCategory;
use Deinte\CookieConsent\Enums\ScriptPosition;
use Deinte\CookieConsent\Facades\CookieConsent;
use Deinte\CookieConsent\Models\ConsentScript;

class CookieConsentManagerTest extends TestCase
{
    public function test_config_contains_everything_the_runtime_needs(): void
    {
        config()->set('cookie-consent.policy.url', '/cookies');
        config()->set('cookie-consent.policy.version', '7');

        $config = CookieConsent::config('nl');

        $this->assertSame('7', $config['version']);
        $this->assertSame('/cookies', $config['policyUrl']);
        $this->assertSame('nl', $config['locale']);
        $this->assertTrue($config['consentMode']);
        $this->assertSame('cc_consent', $config['cookie']['name']);
        $this->assertSame(['necessary', 'preferences', 'statistics', 'marketing'], array_column($config['categories'], 'key'));
        $this->assertTrue($config['categories'][0]['required']);
        $this->assertSame('Noodzakelijk', $config['categories'][0]['label']);
        $this->assertSame('Alles accepteren', $config['texts']['accept_all']);
        $this->assertArrayNotHasKey('categories', $config['texts']);
        $this->assertNotEmpty($config['rules']);
        $this->assertNull($config['logEndpoint']);
    }

    public function test_log_endpoint_is_exposed_once_routes_are_registered(): void
    {
        CookieConsent::routes();

        $this->assertSame('/cookie-consent/log', CookieConsent::config()['logEndpoint']);
    }

    public function test_policy_hash_is_stable_until_version_or_scripts_change(): void
    {
        $initial = CookieConsent::policyHash();

        $this->assertSame($initial, CookieConsent::policyHash());

        config()->set('cookie-consent.policy.version', '2');
        CookieConsent::refresh();

        $afterVersionBump = CookieConsent::policyHash();
        $this->assertNotSame($initial, $afterVersionBump);

        CookieConsent::registerScript(ScriptDefinition::external('Pixel', 'https://x.test/p.js', ConsentCategory::Marketing));

        $this->assertNotSame($afterVersionBump, CookieConsent::policyHash());
    }

    public function test_scripts_merge_database_rows_with_registered_definitions_in_sort_order(): void
    {
        ConsentScript::query()->create([
            'name' => 'Hotjar',
            'category' => ConsentCategory::Statistics,
            'position' => ScriptPosition::Head,
            'kind' => 'external',
            'src' => 'https://static.hotjar.com/c/hotjar-1.js',
            'sort_order' => 5,
        ]);
        ConsentScript::query()->create([
            'name' => 'Disabled',
            'category' => ConsentCategory::Marketing,
            'position' => ScriptPosition::Body,
            'kind' => 'inline',
            'code' => 'x()',
            'is_active' => false,
        ]);

        CookieConsent::registerScript(fn (): ScriptDefinition => CookieConsent::googleAnalytics('G-123'));
        CookieConsent::registerScript(ScriptDefinition::inline('Chat', 'chat()', ConsentCategory::Preferences, ScriptPosition::Body, sortOrder: 10));

        $names = CookieConsent::scripts()->map(fn (ScriptDefinition $script): string => $script->name)->all();
        $this->assertSame(['Google Analytics', 'Hotjar', 'Chat'], $names);

        $head = CookieConsent::scripts(ScriptPosition::Head)->map(fn (ScriptDefinition $script): string => $script->name)->all();
        $this->assertSame(['Google Analytics', 'Hotjar'], $head);
    }

    public function test_google_helpers_produce_gated_definitions(): void
    {
        $analytics = CookieConsent::googleAnalytics("G-1'");
        $tagManager = CookieConsent::googleTagManager('GTM-1');

        $this->assertSame(ConsentCategory::Statistics, $analytics->category);
        $this->assertStringContainsString("gtag('config','G-1\\'')", $analytics->code);
        $this->assertNotEmpty($analytics->cookies);
        $this->assertSame(ConsentCategory::Necessary, $tagManager->category);
        $this->assertStringContainsString('gtm.js?id=', $tagManager->code);
    }

    public function test_block_html_is_a_passthrough_when_disabled(): void
    {
        config()->set('cookie-consent.enabled', false);
        CookieConsent::refresh();

        $html = '<script src="https://connect.facebook.net/x.js"></script>';

        $this->assertSame($html, CookieConsent::blockHtml($html)->toHtml());
        $this->assertSame('', CookieConsent::blockHtml(null)->toHtml());
    }
}
