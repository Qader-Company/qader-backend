<?php

namespace App\Modules\V1\Website\Application\Services;

use App\Modules\V1\ProfessionalFields\Domain\Models\ProfessionalField;
use App\Modules\V1\TalentApplications\Domain\Models\TalentApplication;
use App\Modules\V1\TalentApplications\Domain\ValueObjects\TalentApplicationStatusEnum;
use App\Modules\V1\TalentMarketplace\Application\Services\TalentMarketplaceService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class PublicTalentShowcaseService
{
    private const TALENTS_PER_FIELD = 4;

    public function __construct(
        private readonly TalentMarketplaceService $marketplace,
    ) {}

    /** @return Collection<int, array{id: ?int, slug: string, name: string, talents: Collection}> */
    public function groups(): Collection
    {
        $groups = collect([
            [
                'id' => null,
                'slug' => 'all',
                'name' => __('website.all_specializations'),
                'talents' => $this->talents(),
            ],
        ]);

        ProfessionalField::query()
            ->where('is_active', true)
            ->with('translations')
            ->orderBy('id')
            ->get()
            ->each(function (ProfessionalField $field) use ($groups): void {
                $talents = $this->talents($field->id);

                if ($talents->isEmpty()) {
                    return;
                }

                $groups->push([
                    'id' => $field->id,
                    'slug' => $field->slug,
                    'name' => $field->name,
                    'talents' => $talents,
                ]);
            });

        return $groups;
    }

    private function talents(?int $professionalFieldId = null): Collection
    {
        $latestAcceptance = TalentApplication::query()
            ->selectRaw('MAX(review_decided_at)')
            ->whereColumn('talent_applications.talent_id', 'talents.id')
            ->where('status', TalentApplicationStatusEnum::ACCEPTED->value);

        return $this->marketplace
            ->eligibleQuery()
            ->whereHas('professionalField', fn (Builder $query) => $query->where('is_active', true))
            ->when(
                $professionalFieldId !== null,
                fn (Builder $query) => $query->where('professional_field_id', $professionalFieldId),
            )
            ->with([
                'jobTitle',
                'residenceCountry.translations',
                'expectedCurrency.translations',
                'skills' => fn ($query) => $query
                    ->where('skills.is_active', true)
                    ->orderBy('skills.name'),
            ])
            ->orderByDesc($latestAcceptance)
            ->orderBy('talents.id')
            ->limit(self::TALENTS_PER_FIELD)
            ->get();
    }
}
