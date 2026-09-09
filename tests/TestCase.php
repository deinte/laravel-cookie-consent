<?php

declare(strict_types=1);

namespace Deinte\CookieConsent\Tests;

use Deinte\CookieConsent\CookieConsentServiceProvider;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\File;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();

        foreach (File::allFiles(__DIR__.'/../database/migrations') as $migration) {
            /** @var Migration $instance */
            $instance = include $migration->getRealPath();
            $instance->up();
        }
    }

    /** @return array<int, class-string> */
    protected function getPackageProviders($app): array
    {
        return [
            CookieConsentServiceProvider::class,
        ];
    }

    /** @param Application $app */
    protected function getEnvironmentSetUp($app): void
    {
        config()->set('database.default', 'testing');
        config()->set('app.key', 'base64:'.base64_encode(random_bytes(32)));
        config()->set('app.locale', 'nl');
    }
}
