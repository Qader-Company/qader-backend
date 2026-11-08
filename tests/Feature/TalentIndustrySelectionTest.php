<?php

namespace Tests\Feature;

use App\Modules\V1\Industries\Domain\Models\Industry;
use App\Modules\V1\Talents\Application\Services\TalentProfileWriter;
use App\Modules\V1\Talents\Domain\Models\Talent;
use App\Modules\V1\Users\Domain\Models\User;
use App\Modules\V1\Users\Domain\ValueObjects\PortalTypeEnum;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TalentIndustrySelectionTest extends TestCase
{
    use RefreshDatabase;

    public function test_talent_selects_multiple_industries_with_professional_information(): void
    {
        $user = User::query()->create([
            'first_name' => 'Talent',
            'last_name' => 'User',
            'email' => 'talent.industries@example.test',
            'password' => 'Password123!',
            'type' => PortalTypeEnum::TALENT,
            'is_active' => true,
        ]);
        $talent = Talent::query()->create(['user_id' => $user->id]);
        $firstIndustry = Industry::query()->create([
            'slug' => 'technology',
            'is_active' => true,
            'en' => ['name' => 'Technology'],
            'ar' => ['name' => 'التكنولوجيا'],
        ]);
        $secondIndustry = Industry::query()->create([
            'slug' => 'financial-services',
            'is_active' => true,
            'en' => ['name' => 'Financial Services'],
            'ar' => ['name' => 'الخدمات المالية'],
        ]);

        $profile = app(TalentProfileWriter::class)->save($talent, [
            'skills-and-experiences' => [
                'industry_ids' => [$firstIndustry->id, $secondIndustry->id],
            ],
        ]);

        $this->assertEqualsCanonicalizing(
            [$firstIndustry->id, $secondIndustry->id],
            $profile->industries->pluck('id')->all(),
        );
        $this->assertDatabaseCount('talent_industries', 2);
    }
}
