<?php

declare(strict_types=1);

namespace Deinte\CookieConsent\Tests;

use Deinte\CookieConsent\Data\CookieDeclaration;
use Deinte\CookieConsent\Data\ScriptDefinition;
use Deinte\CookieConsent\Enums\ConsentCategory;
use Deinte\CookieConsent\Enums\ScriptPosition;
use Deinte\CookieConsent\Facades\CookieConsent;
use Illuminate\Support\Facades\Blade;

class BladeComponentsTest extends TestCase
{
    public function test_head_component_emits_config_styles_runtime_template_and_scripts_in_order(): void
    {
        CookieConsent::registerScript(ScriptDefinition::external('Hotjar', 'https://static.hotjar.com/h.js', ConsentCategory::Statistics));
        CookieConsent::registerScript(CookieConsent::googleTagManager('GTM-9'));

        $html = Blade::render('<x-cookie-consent::head />');

        $config = strpos($html, 'window.CookieConsentConfig=');
        $styles = strpos($html, '<style id="cc-css">');
        $runtime = strpos($html, '<script id="cc-runtime">');
        $template = strpos($html, '<template id="cc-template">');
        $hotjar = strpos($html, 'data-cookieconsent="statistics" data-src="https://static.hotjar.com/h.js"');
        $tagManager = strpos($html, "'GTM-9'");

        $this->assertNotFalse($config);
        $this->assertTrue($config < $styles && $styles < $runtime && $runtime < $template && $template < $hotjar);
        $this->assertStringContainsString('type="text/plain" data-cookieconsent="statistics"', $html);
        $this->assertStringNotContainsString('type="text/plain" data-cookieconsent="necessary"', $html);
        $this->assertNotFalse($tagManager);
        $this->assertStringContainsString('role="dialog"', $html);
        $this->assertStringContainsString('data-cc-category="marketing"', $html);
        $this->assertStringContainsString('Alles accepteren', $html);
    }

    public function test_head_component_renders_plain_scripts_when_disabled(): void
    {
        config()->set('cookie-consent.enabled', false);
        CookieConsent::registerScript(ScriptDefinition::external('Hotjar', 'https://static.hotjar.com/h.js', ConsentCategory::Statistics, attributes: ['async' => true]));

        $html = Blade::render('<x-cookie-consent::head />');

        $this->assertStringNotContainsString('cc-template', $html);
        $this->assertStringNotContainsString('CookieConsentConfig', $html);
        $this->assertStringContainsString('<script src="https://static.hotjar.com/h.js" async></script>', $html);
    }

    public function test_body_component_renders_only_body_scripts(): void
    {
        CookieConsent::registerScript(ScriptDefinition::inline('Head', 'h()', ConsentCategory::Marketing, ScriptPosition::Head));
        CookieConsent::registerScript(ScriptDefinition::inline('Body', 'b()', ConsentCategory::Marketing, ScriptPosition::Body));

        $html = Blade::render('<x-cookie-consent::body />');

        $this->assertStringContainsString('b()', $html);
        $this->assertStringNotContainsString('h()', $html);
    }

    public function test_script_component_wraps_external_and_inline_scripts(): void
    {
        $external = Blade::render('<x-cookie-consent::script category="marketing" src="https://x.test/a.js" async />');
        $inline = Blade::render('<x-cookie-consent::script category="statistics">track()</x-cookie-consent::script>');
        $necessary = Blade::render('<x-cookie-consent::script category="necessary" src="https://x.test/n.js" />');

        $this->assertStringContainsString('<script type="text/plain" data-cookieconsent="marketing" data-src="https://x.test/a.js" async></script>', $external);
        $this->assertStringContainsString('<script type="text/plain" data-cookieconsent="statistics">track()</script>', $inline);
        $this->assertStringContainsString('<script src="https://x.test/n.js"', $necessary);
        $this->assertStringNotContainsString('text/plain', $necessary);
    }

    public function test_declaration_lists_cookies_per_category(): void
    {
        CookieConsent::registerScript(ScriptDefinition::external(
            'Google Analytics',
            'https://www.googletagmanager.com/gtag/js',
            ConsentCategory::Statistics,
            cookies: [new CookieDeclaration('_ga', 'Google', 'Bezoekers onderscheiden', '2 jaar')],
        ));

        $html = Blade::render('<x-cookie-consent::declaration />');

        $this->assertStringContainsString('<h3 class="cc-declaration__heading">Statistieken</h3>', $html);
        $this->assertStringContainsString('<td>_ga</td>', $html);
        $this->assertStringContainsString('<td>2 jaar</td>', $html);
        $this->assertStringContainsString('Er worden in deze categorie geen cookies geplaatst.', $html);
        $this->assertStringContainsString('data-cookieconsent="show"', $html);
    }

    public function test_settings_link_uses_translated_label(): void
    {
        $html = Blade::render('<x-cookie-consent::settings-link class="footer-link" />');

        $this->assertStringContainsString('<a href="#" data-cookieconsent="show" class="footer-link">Cookie-instellingen</a>', $html);
    }
}
