<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contract_types', function (Blueprint $table): void {
            $table->id();
            $table->string('type', 32)->unique();
            $table->boolean('is_active')->default(false);
            $table->string('name_en');
            $table->string('name_ar');
            $table->text('description_en')->nullable();
            $table->text('description_ar')->nullable();
            $table->string('fee_type', 32)->default('percentage');
            $table->decimal('fixed_amount', 18, 4)->nullable();
            $table->foreignId('fixed_currency_id')->nullable()->constrained('currencies')->restrictOnDelete();
            $table->decimal('percentage', 7, 4)->nullable();
            $table->string('work_start_day', 16)->nullable();
            $table->string('work_end_day', 16)->nullable();
            $table->time('work_start_time')->nullable();
            $table->time('work_end_time')->nullable();
            $table->string('work_type', 32)->nullable();
            $table->string('work_mode', 32)->nullable();
            $table->json('hybrid_work_days')->nullable();
            $table->string('work_location', 500)->nullable();
            $table->decimal('work_latitude', 10, 7)->nullable();
            $table->decimal('work_longitude', 10, 7)->nullable();
            $table->timestamps();
        });

        Schema::create('contract_type_benefits', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('contract_type_id')->constrained()->cascadeOnDelete();
            $table->string('name_en');
            $table->string('name_ar')->nullable();
            $table->unsignedSmallInteger('display_order');
            $table->timestamps();
            $table->unique(['contract_type_id', 'display_order']);
        });

        Schema::create('contract_type_terms', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('contract_type_id')->constrained()->cascadeOnDelete();
            $table->text('content_en');
            $table->text('content_ar')->nullable();
            $table->unsignedSmallInteger('display_order');
            $table->timestamps();
            $table->unique(['contract_type_id', 'display_order']);
        });

        Schema::create('contract_type_fee_overrides', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('contract_type_id')->constrained()->cascadeOnDelete();
            $table->foreignId('professional_field_id')->constrained()->restrictOnDelete();
            $table->unsignedInteger('min_employees');
            $table->unsignedInteger('max_employees');
            $table->string('fee_type', 32);
            $table->decimal('fixed_amount', 18, 4)->nullable();
            $table->foreignId('fixed_currency_id')->nullable()->constrained('currencies')->restrictOnDelete();
            $table->decimal('percentage', 7, 4)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->index(['contract_type_id', 'professional_field_id', 'is_active'], 'contract_fee_override_lookup');
        });

        DB::table('contract_types')->insert([
            [
                'type' => 'managed',
                'name_en' => 'Managed Contract',
                'name_ar' => 'عقد مُدار',
                'fee_type' => 'percentage',
                'is_active' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'type' => 'one_time',
                'name_en' => 'One-Time Commission',
                'name_ar' => 'عمولة لمرة واحدة',
                'fee_type' => 'percentage',
                'is_active' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        Schema::table('hiring_request_items', function (Blueprint $table): void {
            $table->foreignId('contract_type_id')->nullable()->after('contract_type')->constrained('contract_types')->restrictOnDelete();
            $table->json('contract_configuration_snapshot')->nullable()->after('contract_type_id');
        });

        DB::table('hiring_request_items')->orderBy('id')->chunkById(500, function ($items): void {
            $types = DB::table('contract_types')->pluck('id', 'type');
            foreach ($items as $item) {
                $typeId = $types[$item->contract_type] ?? null;
                if ($typeId !== null) {
                    DB::table('hiring_request_items')->where('id', $item->id)->update(['contract_type_id' => $typeId]);
                }
            }
        });

        Schema::table('hiring_offers', function (Blueprint $table): void {
            $table->decimal('work_latitude', 10, 7)->nullable()->after('work_location');
            $table->decimal('work_longitude', 10, 7)->nullable()->after('work_latitude');
        });
    }

    public function down(): void
    {
        Schema::table('hiring_offers', function (Blueprint $table): void {
            $table->dropColumn(['work_latitude', 'work_longitude']);
        });
        Schema::table('hiring_request_items', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('contract_type_id');
            $table->dropColumn('contract_configuration_snapshot');
        });
        Schema::dropIfExists('contract_type_fee_overrides');
        Schema::dropIfExists('contract_type_terms');
        Schema::dropIfExists('contract_type_benefits');
        Schema::dropIfExists('contract_types');
    }
};
