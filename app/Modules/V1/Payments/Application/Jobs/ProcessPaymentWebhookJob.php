<?php

namespace App\Modules\V1\Payments\Application\Jobs;

use App\Modules\V1\Payments\Application\Data\PaymentWebhookData;
use App\Modules\V1\Payments\Application\Services\ProcessPaymentWebhookService;
use App\Modules\V1\Payments\Domain\Models\PaymentWebhookEvent;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessPaymentWebhookJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    /** @var array<int, int> */
    public array $backoff = [10, 30, 90];

    public function __construct(
        public readonly int $eventId,
        public readonly PaymentWebhookData $data,
    ) {}

    public function handle(ProcessPaymentWebhookService $service): void
    {
        $service->process(PaymentWebhookEvent::query()->findOrFail($this->eventId), $this->data);
    }
}
