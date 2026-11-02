<?php

namespace App\Modules\V1\HiringRequests\Application\Services;

use App\Modules\V1\HiringRequests\Domain\Models\HiringOffer;
use App\Modules\V1\HiringRequests\Domain\ValueObjects\HiringOfferStatusEnum;
use App\Modules\V1\Users\Domain\Models\User;
use Illuminate\Support\Facades\DB;

class HiringOfferExpirationService
{
    public function __construct(
        private readonly HiringRuntimeSettings $settings,
        private readonly HiringEventRecorder $events,
    ) {}

    public function synchronizeDue(): int
    {
        $count = 0;
        HiringOffer::query()
            ->where('status', HiringOfferStatusEnum::PENDING->value)
            ->whereNotNull('response_due_at')
            ->where('response_due_at', '<=', now())
            ->orderBy('id')
            ->pluck('id')
            ->each(function (int $id) use (&$count): void {
                if ($this->expire($id)) {
                    $count++;
                }
            });

        return $count;
    }

    public function expire(int $offerId): bool
    {
        return DB::transaction(function () use ($offerId): bool {
            $offer = HiringOffer::query()->lockForUpdate()->find($offerId);
            if (
                $offer === null
                || $offer->status !== HiringOfferStatusEnum::PENDING
                || $offer->response_due_at === null
                || $offer->response_due_at->isFuture()
            ) {
                return false;
            }

            $offer->update(['status' => HiringOfferStatusEnum::EXPIRED]);
            $item = $offer->item()->with(['hiringRequest', 'talent.user'])->lockForUpdate()->firstOrFail();
            $talent = $item->talent;
            $talent->increment('offer_no_response_count');
            $talent->refresh();
            $limit = $this->settings->talentNoResponseLimit();
            $disabled = $talent->offer_no_response_count >= $limit;
            if ($disabled) {
                User::query()->whereKey($talent->user_id)->lockForUpdate()->update(['is_active' => false]);
                $talent->user?->tokens()->delete();
            }

            $this->events->record($item, null, 'offer_response_deadline_expired', [
                'offer_id' => $offer->id,
                'offer_no_response_count' => $talent->offer_no_response_count,
                'limit' => $limit,
                'talent_account_disabled' => $disabled,
            ]);

            return true;
        });
    }
}
