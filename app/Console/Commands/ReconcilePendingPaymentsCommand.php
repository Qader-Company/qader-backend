<?php

namespace App\Console\Commands;

use App\Modules\V1\Payments\Application\Services\ReconcilePendingPaymentsService;
use Illuminate\Console\Command;

class ReconcilePendingPaymentsCommand extends Command
{
    protected $signature = 'payments:reconcile {--limit=100}';

    protected $description = 'Reconcile pending payments with the configured gateways';

    public function handle(ReconcilePendingPaymentsService $service): int
    {
        $count = $service->run(max(1, (int) $this->option('limit')));
        $this->info("Reconciled {$count} payment attempts.");

        return self::SUCCESS;
    }
}
