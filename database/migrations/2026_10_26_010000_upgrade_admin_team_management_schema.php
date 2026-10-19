<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        $addedRoleIsActive = ! Schema::hasColumn('roles', 'is_active');

        if (! Schema::hasColumn('roles', 'name_en')) {
            Schema::table('roles', function (Blueprint $table): void {
                $table->string('name_en')->nullable()->after('name');
            });
        }

        if (! Schema::hasColumn('roles', 'name_ar')) {
            Schema::table('roles', function (Blueprint $table): void {
                $table->string('name_ar')->nullable()->after('name_en');
            });
        }

        if ($addedRoleIsActive) {
            Schema::table('roles', function (Blueprint $table): void {
                $table->boolean('is_active')->default(true)->index();
            });
        }

        DB::table('roles')
            ->select(['id', 'name', 'name_en', 'name_ar'])
            ->orderBy('id')
            ->each(function (object $role) use ($addedRoleIsActive): void {
                $nameEn = filled($role->name_en)
                    ? $role->name_en
                    : Str::headline($role->name);

                $updates = [
                    'name_en' => $nameEn,
                    'name_ar' => filled($role->name_ar)
                        ? $role->name_ar
                        : $nameEn,
                ];

                if ($addedRoleIsActive) {
                    $updates['is_active'] = true;
                }

                DB::table('roles')
                    ->where('id', $role->id)
                    ->update($updates);
            });

        if (! Schema::hasColumn('admins', 'phone_country_code')) {
            Schema::table('admins', function (Blueprint $table): void {
                $table->string('phone_country_code', 5)->nullable();
            });
        }

        if (! Schema::hasColumn('admins', 'phone_number')) {
            Schema::table('admins', function (Blueprint $table): void {
                $table->string('phone_number', 20)->nullable();
            });
        }

        if (! Schema::hasColumn('admins', 'display_name_ar')) {
            Schema::table('admins', function (Blueprint $table): void {
                $table->string('display_name_ar')->nullable();
            });
        }
    }

    public function down(): void
    {
        // This migration upgrades databases that already contain user data.
        // Removing these columns on rollback would discard that data.
    }
};
