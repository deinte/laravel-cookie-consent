<?php

declare(strict_types=1);

namespace Deinte\CookieConsent\Http\Controllers;

use Deinte\CookieConsent\Enums\ConsentCategory;
use Deinte\CookieConsent\Events\ConsentLogged;
use Deinte\CookieConsent\Http\Requests\LogConsentRequest;
use Deinte\CookieConsent\Models\ConsentLog;
use Illuminate\Http\Response;
use Illuminate\Support\Str;

class LogConsentController
{
    public function __invoke(LogConsentRequest $request): Response
    {
        if (! config('cookie-consent.logging.enabled', true)) {
            return response()->noContent();
        }

        /** @var class-string<ConsentLog> $model */
        $model = config('cookie-consent.models.log', ConsentLog::class);

        $categories = collect($request->validated('categories'))
            ->push(ConsentCategory::Necessary->value)
            ->unique()
            ->values()
            ->all();

        $log = $model::query()->create([
            'consent_id' => $request->validated('id'),
            'categories' => $categories,
            'policy_version' => $request->validated('version'),
            'policy_hash' => $request->validated('policyHash'),
            'ip_hash' => $this->hashIp($request->ip()),
            'user_agent' => Str::limit((string) $request->userAgent(), 250, ''),
            'url' => $request->validated('url'),
        ]);

        ConsentLogged::dispatch($log);

        return response()->noContent();
    }

    protected function hashIp(?string $ip): ?string
    {
        if ($ip === null) {
            return null;
        }

        $salt = (string) (config('cookie-consent.logging.ip_salt') ?: config('app.key'));

        return hash('sha256', "{$salt}|{$ip}");
    }
}
