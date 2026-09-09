<?php

declare(strict_types=1);

namespace Deinte\CookieConsent\Tests;

use Deinte\CookieConsent\Events\ConsentLogged;
use Deinte\CookieConsent\Facades\CookieConsent;
use Deinte\CookieConsent\Models\ConsentLog;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;

class LogConsentEndpointTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        CookieConsent::routes();
    }

    public function test_it_stores_an_anonymised_consent_record(): void
    {
        Event::fake([ConsentLogged::class]);
        $id = (string) Str::uuid();

        $response = $this->postJson('/cookie-consent/log', [
            'id' => $id,
            'categories' => ['statistics'],
            'version' => '3',
            'policyHash' => 'abc123',
            'url' => 'https://example.test/page',
        ], ['User-Agent' => 'PHPUnit']);

        $response->assertNoContent();

        $log = ConsentLog::query()->firstOrFail();
        $this->assertSame($id, $log->consent_id);
        $this->assertSame(['statistics', 'necessary'], $log->categories);
        $this->assertSame('3', $log->policy_version);
        $this->assertSame('abc123', $log->policy_hash);
        $this->assertSame(64, strlen((string) $log->ip_hash));
        $this->assertStringNotContainsString('127.0.0.1', (string) $log->ip_hash);
        $this->assertSame('PHPUnit', $log->user_agent);
        $this->assertTrue($log->hasConsentFor('statistics'));
        $this->assertFalse($log->hasConsentFor('marketing'));

        Event::assertDispatched(ConsentLogged::class);
    }

    public function test_it_rejects_unknown_categories_and_missing_ids(): void
    {
        $this->postJson('/cookie-consent/log', [
            'id' => (string) Str::uuid(),
            'categories' => ['tracking'],
            'version' => '1',
        ])->assertUnprocessable()->assertJsonValidationErrors(['categories.0']);

        $this->postJson('/cookie-consent/log', [
            'categories' => [],
            'version' => '1',
        ])->assertUnprocessable()->assertJsonValidationErrors(['id']);

        $this->assertSame(0, ConsentLog::query()->count());
    }

    public function test_logging_can_be_disabled(): void
    {
        config()->set('cookie-consent.logging.enabled', false);

        $this->postJson('/cookie-consent/log', [
            'id' => (string) Str::uuid(),
            'categories' => [],
            'version' => '1',
        ])->assertNoContent();

        $this->assertSame(0, ConsentLog::query()->count());
    }

    public function test_prune_command_deletes_old_logs(): void
    {
        ConsentLog::query()->create(['consent_id' => Str::uuid(), 'categories' => ['necessary'], 'policy_version' => '1', 'created_at' => now()->subDays(500)]);
        ConsentLog::query()->create(['consent_id' => Str::uuid(), 'categories' => ['necessary'], 'policy_version' => '1', 'created_at' => now()->subDays(10)]);

        $this->artisan('cookie-consent:prune-logs')
            ->expectsOutputToContain('Deleted 1 consent logs.')
            ->assertSuccessful();

        $this->assertSame(1, ConsentLog::query()->count());
    }
}
