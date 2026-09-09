<?php

declare(strict_types=1);

namespace Deinte\CookieConsent\Tests;

use Deinte\CookieConsent\Enums\ConsentCategory;
use Deinte\CookieConsent\Support\HtmlBlocker;
use PHPUnit\Framework\TestCase as PhpUnitTestCase;

class HtmlBlockerTest extends PhpUnitTestCase
{
    private HtmlBlocker $blocker;

    protected function setUp(): void
    {
        parent::setUp();

        $this->blocker = new HtmlBlocker([
            ['pattern' => 'googletagmanager.com/gtag/js', 'category' => 'statistics'],
            ['pattern' => 'googletagmanager.com', 'category' => 'necessary'],
            ['pattern' => 'connect.facebook.net', 'category' => 'marketing'],
            ['pattern' => 'facebook.com/tr', 'category' => 'marketing'],
            ['pattern' => 'youtube.com/embed', 'category' => 'marketing'],
        ]);
    }

    public function test_external_script_matching_a_rule_is_neutralised_with_its_category(): void
    {
        $html = '<script async src="https://www.googletagmanager.com/gtag/js?id=G-1"></script>';

        $result = $this->blocker->block($html, ConsentCategory::Marketing);

        $this->assertSame(
            '<script type="text/plain" data-cookieconsent="statistics" async data-src="https://www.googletagmanager.com/gtag/js?id=G-1"></script>',
            $result,
        );
    }

    public function test_inline_script_falls_back_to_the_default_category(): void
    {
        $result = $this->blocker->block('<script>console.log(1)</script>', ConsentCategory::Preferences);

        $this->assertSame('<script type="text/plain" data-cookieconsent="preferences">console.log(1)</script>', $result);
    }

    public function test_inline_script_is_classified_by_its_contents(): void
    {
        $result = $this->blocker->block("<script>fbq('init');s.src='https://connect.facebook.net/en_US/fbevents.js';</script>", ConsentCategory::Statistics);

        $this->assertStringContainsString('data-cookieconsent="marketing"', $result);
    }

    public function test_necessary_scripts_stay_executable(): void
    {
        $html = '<script src="https://www.googletagmanager.com/gtm.js?id=GTM-1"></script>';

        $this->assertSame($html, $this->blocker->block($html, ConsentCategory::Marketing));
    }

    public function test_already_managed_scripts_and_json_scripts_are_untouched(): void
    {
        $managed = '<script type="text/plain" data-cookieconsent="marketing">x()</script>';
        $json = '<script type="application/ld+json">{"a":1}</script>';

        $this->assertSame($managed.$json, $this->blocker->block($managed.$json, ConsentCategory::Marketing));
    }

    public function test_iframes_move_their_source_to_a_data_attribute(): void
    {
        $html = '<iframe src="https://www.youtube.com/embed/abc" width="560" allowfullscreen></iframe>';

        $result = $this->blocker->block($html, ConsentCategory::Statistics);

        $this->assertSame(
            '<iframe data-cookieconsent="marketing" data-cc-src="https://www.youtube.com/embed/abc" width="560" allowfullscreen></iframe>',
            $result,
        );
    }

    public function test_first_party_iframes_use_the_default_category(): void
    {
        $result = $this->blocker->block('<iframe src="/embed"></iframe>', ConsentCategory::Preferences);

        $this->assertStringContainsString('data-cookieconsent="preferences"', $result);
    }

    public function test_only_tracker_images_are_blocked(): void
    {
        $pixel = '<img src="https://www.facebook.com/tr?id=1&ev=PageView" height="1" width="1">';
        $photo = '<img src="/photo.jpg" alt="">';

        $result = $this->blocker->block($pixel.$photo, ConsentCategory::Marketing);

        $this->assertStringContainsString('<img data-cookieconsent="marketing" data-cc-src="https://www.facebook.com/tr?id=1&amp;ev=PageView" height="1" width="1">', $result);
        $this->assertStringContainsString($photo, $result);
    }

    public function test_empty_html_passes_through(): void
    {
        $this->assertSame('', $this->blocker->block('', ConsentCategory::Marketing));
    }
}
