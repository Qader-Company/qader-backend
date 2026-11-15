<?php

namespace App\Modules\V1\PlatformSettings\Application\Services;

use App\Modules\V1\PlatformSettings\Domain\Models\PlatformSettingEvent;
use App\Modules\V1\Users\Domain\Models\User;
use App\Settings\HiringSettings;
use App\Settings\MarketplaceSettings;
use App\Settings\PaidServicesSettings;
use App\Settings\PlatformSettings;
use App\Settings\TalentEvaluationSettings;
use App\Settings\TrashSettings;
use App\Settings\WebsiteSettings;
use Illuminate\Support\Facades\DB;
use Spatie\LaravelSettings\Settings;

class PlatformSettingsService
{
    public function __construct(
        private readonly HiringSettings $hiring,
        private readonly TalentEvaluationSettings $talentEvaluation,
        private readonly TrashSettings $trash,
        private readonly PlatformSettings $platform,
        private readonly WebsiteSettings $website,
        private readonly MarketplaceSettings $marketplace,
        private readonly PaidServicesSettings $paidServices,
    ) {}

    public function all(): array
    {
        $website = $this->website->toArray();
        $year = (string) now()->year;
        foreach (['footer_copyright_text_ar', 'footer_copyright_text_en'] as $key) {
            if (is_string($website[$key] ?? null)) {
                $website[$key] = str_replace('{year}', $year, $website[$key]);
            }
        }

        return [
            'hiring' => $this->hiring->toArray(),
            'talent_evaluation' => $this->talentEvaluation->toArray(),
            'trash' => $this->trash->toArray(),
            'platform' => $this->platform->toArray(),
            'website' => $website,
            'marketplace' => $this->marketplace->toArray(),
            'paid_services' => $this->paidServices->toArray(),
        ];
    }

    public function update(string $group, array $values, User $actor): array
    {
        $settings = $this->forGroup($group);
        $oldValues = $settings->toArray();

        return DB::transaction(function () use ($settings, $group, $values, $oldValues, $actor): array {
            $settings->fill($values)->save();
            $newValues = $settings->toArray();

            PlatformSettingEvent::query()->create([
                'actor_admin_id' => $actor->admin()->value('id'),
                'group' => $group,
                'old_values' => $oldValues,
                'new_values' => $newValues,
                'created_at' => now(),
            ]);

            return $newValues;
        });
    }

    private function forGroup(string $group): Settings
    {
        return match ($group) {
            'hiring' => $this->hiring,
            'talent-evaluation' => $this->talentEvaluation,
            'trash' => $this->trash,
            'platform' => $this->platform,
            'website' => $this->website,
            'marketplace' => $this->marketplace,
            'paid-services' => $this->paidServices,
            default => throw new \InvalidArgumentException(__('settings.invalid_group')),
        };
    }
}
