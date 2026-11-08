<?php

namespace Tests\Feature;

use App\Http\Middleware\CheckApiKey;
use App\Modules\V1\AccessControl\Domain\Models\Role;
use App\Modules\V1\Admins\Domain\Models\Admin;
use App\Modules\V1\ContractTypes\Domain\Models\ContractType;
use App\Modules\V1\Currencies\Domain\Models\Currency;
use App\Modules\V1\ProfessionalFields\Domain\Models\ProfessionalField;
use App\Modules\V1\Users\Domain\Models\User;
use App\Modules\V1\Users\Domain\ValueObjects\PortalTypeEnum;
use Database\Seeders\AdminRoleSeeder;
use Database\Seeders\ContractTypeSeeder;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ContractTypeConfigurationTest extends TestCase
{
    use RefreshDatabase;

    private Currency $currency;
    private ProfessionalField $field;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(CheckApiKey::class);
        $this->seed([PermissionSeeder::class, AdminRoleSeeder::class]);
        $this->currency = Currency::query()->create([
            'code' => 'USD', 'symbol' => '$', 'is_active' => true,
            'en' => ['name' => 'US Dollar'], 'ar' => ['name' => 'دولار أمريكي'],
        ]);
        $this->field = ProfessionalField::query()->create([
            'is_active' => true,
            'en' => ['name' => 'Software'], 'ar' => ['name' => 'برمجيات'],
        ]);
        Sanctum::actingAs($this->adminUser()->user, ['access', 'admin']);
    }

    public function test_admin_updates_and_activates_managed_contract_configuration(): void
    {
        $id = $this->getJson('/api/v1/admin/contract-types')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->json('data.0.id');

        $this->putJson("/api/v1/admin/contract-types/{$id}", $this->configuration())
            ->assertOk()
            ->assertJsonPath('data.type', 'managed')
            ->assertJsonPath('data.defaults.work_hours', 8)
            ->assertJsonPath('data.defaults.work_latitude', '30.0444000')
            ->assertJsonCount(2, 'data.terms')
            ->assertJsonCount(1, 'data.fee_overrides');

        $this->patchJson("/api/v1/admin/contract-types/{$id}/status", ['is_active' => true])
            ->assertOk()
            ->assertJsonPath('data.is_active', true);

        $this->assertDatabaseHas('contract_type_terms', [
            'contract_type_id' => $id,
            'display_order' => 2,
            'content_en' => 'Second term',
        ]);
    }

    public function test_overlapping_fee_override_ranges_are_rejected(): void
    {
        $id = $this->getJson('/api/v1/admin/contract-types')->json('data.0.id');
        $payload = $this->configuration();
        $payload['fee_overrides'][] = [
            ...$payload['fee_overrides'][0],
            'min_employees' => 40,
            'max_employees' => 100,
        ];

        $this->putJson("/api/v1/admin/contract-types/{$id}", $payload)
            ->assertUnprocessable()
            ->assertJsonValidationErrors('fee_overrides.1.min_employees');
    }

    public function test_contract_type_seeder_populates_figma_base_data_and_preserves_admin_changes(): void
    {
        $this->seed(ContractTypeSeeder::class);

        $managed = ContractType::query()->where('type', 'managed')->firstOrFail();
        $oneTime = ContractType::query()->where('type', 'one_time')->firstOrFail();

        $this->assertFalse($managed->is_active);
        $this->assertSame('fixed_percentage', $managed->fee_type->value);
        $this->assertSame('percentage', $oneTime->fee_type->value);
        $this->assertSame(6, $managed->benefits()->count());
        $this->assertSame(3, $oneTime->benefits()->count());
        $this->assertSame(1, $managed->terms()->count());
        $this->assertSame(1, $oneTime->terms()->count());
        $this->assertNull($managed->fixed_amount);
        $this->assertNull($managed->percentage);
        $this->assertNull($managed->work_start_day);

        $managed->update([
            'description_en' => 'Administrator supplied description.',
            'fee_type' => 'fixed',
            'fixed_amount' => 125,
            'fixed_currency_id' => $this->currency->id,
        ]);
        $managed->benefits()->firstOrFail()->update(['name_en' => 'Administrator supplied benefit']);

        $this->seed(ContractTypeSeeder::class);

        $managed->refresh();
        $this->assertSame('Administrator supplied description.', $managed->description_en);
        $this->assertSame('fixed', $managed->fee_type->value);
        $this->assertSame('125.0000', $managed->fixed_amount);
        $this->assertSame('Administrator supplied benefit', $managed->benefits()->firstOrFail()->name_en);
        $this->assertSame(6, $managed->benefits()->count());
    }

    private function configuration(): array
    {
        return [
            'name' => ['en' => 'Managed Contract', 'ar' => 'عقد مُدار'],
            'description' => ['en' => 'Managed employment services.', 'ar' => 'خدمات توظيف مُدارة.'],
            'fee' => [
                'type' => 'fixed_percentage',
                'fixed_amount' => 50,
                'fixed_currency_id' => $this->currency->id,
                'percentage' => 5,
            ],
            'benefits' => [[
                'name' => ['en' => 'HR Support', 'ar' => 'دعم الموارد البشرية'],
                'display_order' => 1,
            ]],
            'terms' => [
                ['content' => ['en' => 'First term', 'ar' => 'الشرط الأول'], 'display_order' => 1],
                ['content' => ['en' => 'Second term', 'ar' => 'الشرط الثاني'], 'display_order' => 2],
            ],
            'fee_overrides' => [[
                'professional_field_id' => $this->field->id,
                'min_employees' => 11,
                'max_employees' => 50,
                'fee_type' => 'percentage',
                'percentage' => 7.5,
                'is_active' => true,
            ]],
            'defaults' => [
                'work_start_day' => 'sunday',
                'work_end_day' => 'thursday',
                'work_start_time' => '09:00',
                'work_end_time' => '17:00',
                'work_type' => 'full_time',
                'work_mode' => 'hybrid',
                'hybrid_work_days' => ['wednesday', 'thursday'],
                'work_location' => 'Cairo, Egypt',
                'work_latitude' => 30.0444,
                'work_longitude' => 31.2357,
            ],
        ];
    }

    private function adminUser(): Admin
    {
        $user = User::query()->create([
            'first_name' => 'Contract', 'last_name' => 'Admin',
            'email' => 'contracts-admin@example.test', 'password' => 'Password123!',
            'type' => PortalTypeEnum::ADMIN, 'is_active' => true, 'email_verified_at' => now(),
        ]);
        $user->syncRoles([
            Role::query()->where('name', 'admin')->where('portal', PortalTypeEnum::ADMIN->value)->firstOrFail(),
        ]);

        return Admin::query()->create(['user_id' => $user->id])->load('user');
    }
}
