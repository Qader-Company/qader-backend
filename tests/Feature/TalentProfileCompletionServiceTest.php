<?php

namespace Tests\Feature;

use App\Enums\Gender;
use App\Enums\LanguageProficiency;
use App\Enums\SalaryPeriod;
use App\Enums\SeniorityLevel;
use App\Enums\TalentDocumentType;
use App\Enums\WorkMode;
use App\Enums\WorkType;
use App\Models\City;
use App\Models\Country;
use App\Models\Currency;
use App\Models\JobTitle;
use App\Models\Language;
use App\Models\Skill;
use App\Models\Talent;
use App\Models\TalentDocument;
use App\Models\TalentExperience;
use App\Models\TalentWorkMode;
use App\Models\TalentWorkPreference;
use App\Models\TalentWorkType;
use App\Models\User;
use App\Services\TalentProfile\TalentProfileCompletionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TalentProfileCompletionServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_calculates_and_caches_a_complete_profile(): void
    {
        $talent = $this->completeTalent();
        $service = app(TalentProfileCompletionService::class);

        $result = $service->refresh($talent);
        $talent->refresh();

        $this->assertSame(100, $result['percentage']);
        $this->assertTrue($result['is_submittable']);
        $this->assertTrue($result['sections']['basic_info']['is_complete']);
        $this->assertTrue($result['sections']['skills_experience']['is_complete']);
        $this->assertTrue($result['sections']['work_preferences']['is_complete']);
        $this->assertSame(100, $talent->profile_completion_percentage);
        $this->assertTrue($talent->profile_completion_details['is_submittable']);
        $this->assertNotNull($talent->profile_completion_updated_at);
    }

    public function test_zero_experience_talent_does_not_need_experience_history(): void
    {
        $talent = $this->completeTalent(yearsOfExperience: 0, withExperience: false);

        $result = app(TalentProfileCompletionService::class)->calculate($talent);

        $this->assertSame(100, $result['percentage']);
        $this->assertTrue($result['sections']['skills_experience']['is_complete']);
    }

    public function test_it_reports_missing_requirements_by_section(): void
    {
        $talent = Talent::create(['user_id' => User::factory()->create()->id]);

        $result = app(TalentProfileCompletionService::class)->calculate($talent);

        $this->assertFalse($result['is_submittable']);
        $this->assertContains('phone', $result['sections']['basic_info']['missing']);
        $this->assertContains('skills', $result['sections']['skills_experience']['missing']);
        $this->assertContains('expected_salary_range', $result['sections']['work_preferences']['missing']);
    }

    private function completeTalent(int $yearsOfExperience = 5, bool $withExperience = true): Talent
    {
        $country = Country::create(['code' => 'EG', 'name' => 'Egypt']);
        $city = City::create(['country_id' => $country->id, 'name' => 'Cairo']);
        $jobTitle = JobTitle::create(['name' => 'UI/UX Designer', 'slug' => 'ui-ux-designer']);
        $language = Language::create(['code' => 'en', 'name' => 'English']);
        $skill = Skill::create(['name' => 'User Experience', 'slug' => 'user-experience']);
        $currency = Currency::create(['code' => 'USD', 'name' => 'US Dollar', 'symbol' => '$']);
        $user = User::factory()->create([
            'phone_country_code' => '+20',
            'phone_number' => '1002345678',
        ]);

        $talent = Talent::create([
            'user_id' => $user->id,
            'date_of_birth' => '1995-01-01',
            'gender' => Gender::Female,
            'nationality_country_id' => $country->id,
            'residence_country_id' => $country->id,
            'residence_city_id' => $city->id,
            'job_title_id' => $jobTitle->id,
            'seniority_level' => SeniorityLevel::Senior,
            'years_of_experience' => $yearsOfExperience,
        ]);

        $talent->languages()->attach($language, [
            'proficiency_level' => LanguageProficiency::C1->value,
        ]);
        $talent->skills()->attach($skill, ['is_primary' => true]);

        if ($withExperience) {
            TalentExperience::create([
                'talent_id' => $talent->id,
                'job_title' => 'UI/UX Designer',
                'company_name' => 'Qader',
                'started_at' => '2021-01-01',
                'currently_working' => true,
            ]);
        }

        TalentWorkType::create([
            'talent_id' => $talent->id,
            'work_type' => WorkType::FullTime,
        ]);
        TalentWorkMode::create([
            'talent_id' => $talent->id,
            'work_mode' => WorkMode::Remote,
        ]);
        TalentWorkPreference::create([
            'talent_id' => $talent->id,
            'notice_period_days' => 30,
            'expected_salary_min' => 8000,
            'expected_salary_max' => 12000,
            'expected_currency_id' => $currency->id,
            'salary_period' => SalaryPeriod::Monthly,
        ]);
        TalentDocument::create([
            'talent_id' => $talent->id,
            'type' => TalentDocumentType::Cv,
            'file_name' => 'resume.pdf',
            'disk' => 'private',
            'path' => 'talents/resume.pdf',
            'mime_type' => 'application/pdf',
            'size' => 1024,
            'is_current' => true,
        ]);

        return $talent;
    }
}
