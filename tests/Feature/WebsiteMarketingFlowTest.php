<?php

namespace Tests\Feature;

use App\Http\Middleware\CheckApiKey;
use App\Modules\V1\AccessControl\Domain\Models\Role;
use App\Modules\V1\Admins\Domain\Models\Admin;
use App\Modules\V1\Countries\Domain\Models\Country;
use App\Modules\V1\Currencies\Domain\Models\Currency;
use App\Modules\V1\JobTitles\Domain\Models\JobTitle;
use App\Modules\V1\ProfessionalFields\Domain\Models\ProfessionalField;
use App\Modules\V1\Skills\Domain\Models\Skill;
use App\Modules\V1\TalentApplications\Domain\Models\TalentApplication;
use App\Modules\V1\TalentApplications\Domain\ValueObjects\TalentApplicationStatusEnum;
use App\Modules\V1\Talents\Domain\Models\Talent;
use App\Modules\V1\Talents\Domain\ValueObjects\SeniorityLevelEnum;
use App\Modules\V1\Users\Domain\Models\User;
use App\Modules\V1\Users\Domain\ValueObjects\PortalTypeEnum;
use App\Modules\V1\Website\Domain\Models\MarketingFaq;
use App\Modules\V1\Website\Domain\Models\MarketingPartner;
use App\Modules\V1\Website\Domain\Models\MarketingTestimonial;
use App\Settings\PlatformSettings;
use App\Settings\WebsiteSettings;
use Database\Seeders\AdminRoleSeeder;
use Database\Seeders\WebsiteMarketingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class WebsiteMarketingFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(CheckApiKey::class);
    }

    public function test_figma_website_settings_are_exposed_by_the_public_api(): void
    {
        $website = app(WebsiteSettings::class);
        $platform = app(PlatformSettings::class);

        $this->assertSame(2022, $website->company_founded_year);
        $this->assertSame(10.0, $website->referral_commission_percentage);
        $this->assertSame('6 أكتوبر، مصر', $website->company_location_ar);
        $this->assertSame('6th of October, Egypt', $website->company_location_en);
        $this->assertSame('info@qadereg.com', $platform->support_email);

        $this->withHeader('Accept-Language', 'ar')
            ->getJson('/api/v1/public/website')
            ->assertOk()
            ->assertJsonPath('data.company.founded_year', 2022)
            ->assertJsonPath('data.company.location', '6 أكتوبر، مصر')
            ->assertJsonPath('data.referral.commission_percentage', 10)
            ->assertJsonPath('data.support.email', 'info@qadereg.com');
    }

    public function test_website_marketing_seeder_loads_the_confirmed_figma_content(): void
    {
        Storage::fake('public');

        $this->seed(WebsiteMarketingSeeder::class);

        $this->assertDatabaseCount('marketing_faqs', 5);
        $this->assertDatabaseCount('marketing_partners', 5);
        $this->assertDatabaseCount('marketing_testimonials', 4);
        $this->assertSame(5, MarketingFaq::query()->where('is_active', true)->count());
        $this->assertSame(5, MarketingPartner::query()->whereHas('media')->count());
        $this->assertDatabaseHas('marketing_testimonial_translations', [
            'locale' => 'en',
            'name' => 'Tamem Mohamed',
        ]);
        $this->assertDatabaseHas('marketing_faq_translations', [
            'locale' => 'en',
            'question' => 'How are cost and start date decided?',
        ]);

        $testimonial = MarketingTestimonial::query()->firstOrFail();
        $this->assertSame('Jr. Back End Developer', $testimonial->translate('en', false)?->role);
        $this->assertSame('تميم محمد', $testimonial->translate('ar', false)?->name);
    }

    public function test_public_hiring_support_request_stores_only_the_approved_fields(): void
    {
        $country = Country::query()->create([
            'code' => 'EG',
            'is_active' => true,
            'en' => ['name' => 'Egypt'],
            'ar' => ['name' => 'مصر'],
        ]);

        $this->postJson('/api/v1/public/hiring-support-requests', [
            'name' => 'Ahmed Hassan',
            'email' => 'ahmed@example.test',
            'phone_country_code' => '+20',
            'phone_number' => '1001234567',
            'country_id' => $country->id,
            'description' => 'We need help building a dedicated engineering team.',
        ])->assertCreated()->assertJsonPath('data.id', 1);

        $this->assertDatabaseHas('hiring_support_requests', [
            'email' => 'ahmed@example.test',
            'country_id' => $country->id,
        ]);
    }

    public function test_public_talent_showcase_returns_four_anonymous_talents_per_field(): void
    {
        $country = Country::query()->create([
            'code' => 'EG',
            'is_active' => true,
            'en' => ['name' => 'Egypt'],
            'ar' => ['name' => 'مصر'],
        ]);
        $currency = Currency::query()->create([
            'code' => 'USD',
            'symbol' => '$',
            'is_active' => true,
            'en' => ['name' => 'US Dollar'],
            'ar' => ['name' => 'دولار أمريكي'],
        ]);
        $field = ProfessionalField::query()->create([
            'is_active' => true,
            'en' => ['name' => 'Software Development'],
            'ar' => ['name' => 'تطوير البرمجيات'],
        ]);
        $jobTitle = JobTitle::query()->create([
            'name' => 'Full Stack Developer',
            'is_active' => true,
        ]);
        $skills = collect(['React', 'Node.js', 'TypeScript', 'Laravel'])
            ->map(fn (string $name) => Skill::query()->create([
                'name' => $name,
                'is_active' => true,
            ]));

        foreach (range(1, 5) as $index) {
            $user = User::query()->create([
                'first_name' => 'Private',
                'last_name' => "Talent {$index}",
                'email' => "showcase-{$index}@example.test",
                'password' => 'Password123!',
                'type' => PortalTypeEnum::TALENT,
                'is_active' => true,
                'email_verified_at' => now(),
            ]);
            $talent = Talent::query()->create([
                'user_id' => $user->id,
                'professional_field_id' => $field->id,
                'job_title_id' => $jobTitle->id,
                'residence_country_id' => $country->id,
                'seniority_level' => SeniorityLevelEnum::SENIOR,
                'years_of_experience' => 7,
                'expected_salary_min' => 4500,
                'expected_salary_max' => 6000,
                'expected_currency_id' => $currency->id,
            ]);
            $talent->skills()->attach($skills->pluck('id'));
            TalentApplication::query()->create([
                'talent_id' => $talent->id,
                'status' => TalentApplicationStatusEnum::ACCEPTED,
                'submitted_at' => now()->subDay(),
                'review_decided_at' => now()->subMinutes($index),
                'profile_completion_at_submission' => 100,
            ]);
        }

        $response = $this->withHeader('Accept-Language', 'ar')
            ->getJson('/api/v1/public/website/talent-showcase');

        $response->assertOk()
            ->assertJsonCount(2, 'data.professional_fields')
            ->assertJsonPath('data.professional_fields.0.slug', 'all')
            ->assertJsonPath('data.professional_fields.0.name', 'جميع التخصصات')
            ->assertJsonCount(4, 'data.professional_fields.0.talents')
            ->assertJsonPath('data.professional_fields.1.name', 'تطوير البرمجيات')
            ->assertJsonCount(4, 'data.professional_fields.1.talents')
            ->assertJsonCount(3, 'data.professional_fields.1.talents.0.top_skills')
            ->assertJsonPath('data.professional_fields.1.talents.0.job_title.name', 'Full Stack Developer')
            ->assertJsonPath('data.professional_fields.1.talents.0.seniority_level.value', 'senior')
            ->assertJsonPath('data.professional_fields.1.talents.0.country.name', 'مصر')
            ->assertJsonPath('data.professional_fields.1.talents.0.indicative_salary.minimum', 4500)
            ->assertJsonMissingPath('data.professional_fields.0.id')
            ->assertJsonMissingPath('data.professional_fields.1.id')
            ->assertJsonMissingPath('data.professional_fields.1.talents.0.id')
            ->assertJsonMissingPath('data.professional_fields.1.talents.0.job_title.id')
            ->assertJsonMissingPath('data.professional_fields.1.talents.0.country.id')
            ->assertJsonMissingPath('data.professional_fields.1.talents.0.top_skills.0.id')
            ->assertJsonMissingPath('data.professional_fields.1.talents.0.indicative_salary.currency.id');

        $this->assertStringNotContainsString('showcase-1@example.test', $response->getContent());
        $this->assertStringNotContainsString('Private', $response->getContent());
    }

    public function test_admin_website_get_endpoints_return_html_like_input_definitions(): void
    {
        $this->seed(AdminRoleSeeder::class);
        $user = User::query()->create([
            'first_name' => 'Website',
            'last_name' => 'Admin',
            'email' => 'website-admin@example.test',
            'password' => 'Password123!',
            'type' => PortalTypeEnum::ADMIN,
            'is_active' => true,
            'email_verified_at' => now(),
        ]);
        Admin::query()->create(['user_id' => $user->id]);
        $user->syncRoles([
            Role::query()
                ->where('name', 'admin')
                ->where('portal', PortalTypeEnum::ADMIN->value)
                ->firstOrFail(),
        ]);
        Sanctum::actingAs($user, ['access', 'admin']);

        $faq = MarketingFaq::query()->create([
            'display_order' => 1,
            'is_active' => true,
            'ar' => [
                'question' => 'ما الفرق بين خدمات قادر الثلاث؟',
                'answer' => 'إجابة معتمدة.',
            ],
        ]);

        $this->getJson('/api/v1/admin/website/faqs')
            ->assertOk()
            ->assertJsonPath('data.faqs.0.id', $faq->id)
            ->assertJsonPath('data.faqs.0.inputs.0.key', 'question_ar')
            ->assertJsonPath('data.faqs.0.inputs.0.type', 'textarea')
            ->assertJsonPath('data.faqs.0.inputs.5.key', 'is_active')
            ->assertJsonPath('data.faqs.0.inputs.5.type', 'checkbox');

        $this->getJson('/api/v1/admin/platform-settings')
            ->assertOk()
            ->assertJsonPath('data.website.inputs.2.key', 'company_founded_year')
            ->assertJsonPath('data.website.inputs.2.label', 'Company Founded Year')
            ->assertJsonPath('data.website.inputs.2.type', 'number');

        $this->withHeader('Accept-Language', 'ar')
            ->getJson('/api/v1/admin/platform-settings')
            ->assertOk()
            ->assertJsonPath('data.website.inputs.2.key', 'company_founded_year')
            ->assertJsonPath('data.website.inputs.2.label', 'سنة تأسيس الشركة')
            ->assertJsonPath('data.website.inputs.2.type', 'number');

        $this->withHeader('Accept-Language', 'en')
            ->patchJson('/api/v1/admin/platform-settings/website', [
                'company_founded_year' => 2022,
            ])
            ->assertOk()
            ->assertJsonPath('data.inputs.2.key', 'company_founded_year')
            ->assertJsonPath('data.inputs.2.label', 'Company Founded Year')
            ->assertJsonPath('data.inputs.2.value', 2022)
            ->assertJsonPath('data.inputs.2.type', 'number');
    }
}
