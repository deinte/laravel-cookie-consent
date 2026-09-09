<?php

declare(strict_types=1);

namespace Deinte\CookieConsent\Commands;

use Deinte\CookieConsent\Models\ConsentLog;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class PruneConsentLogsCommand extends Command
{
    protected $signature = 'cookie-consent:prune-logs {--days= : Override the configured retention in days}';

    protected $description = 'Delete consent logs older than the configured retention period';

    public function handle(): int
    {
        $days = (int) ($this->option('days') ?: config('cookie-consent.logging.retention_days', 400));

        /** @var class-string<ConsentLog> $model */
        $model = config('cookie-consent.models.log', ConsentLog::class);

        $this->info("Pruning consent logs older than {$days} days...");

        $deleted = $model::query()
            ->where('created_at', '<', Carbon::now()->subDays($days))
            ->delete();

        $this->comment("Deleted {$deleted} consent logs.");

        return self::SUCCESS;
    }
}
