<?php

namespace Tests\Feature;

use App\Http\Middleware\CheckApiKey;
use App\Modules\V1\AccessControl\Domain\Models\Role;
use App\Modules\V1\Admins\Domain\Models\Admin;
use App\Modules\V1\Countries\Domain\Models\Country;
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
            ->assertJsonPath('data.website.inputs.2.type', 'number');
    }
}
