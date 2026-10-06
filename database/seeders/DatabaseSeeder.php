<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            PermissionSeeder::class,
            PlatformOwnerSeeder::class,
            CountrySeeder::class,
            CitySeeder::class,
            LanguageSeeder::class,
            ProfessionalFieldSeeder::class,
            ProfessionalFormFieldSeeder::class,
            JobTitleSeeder::class,
            SkillSeeder::class,
            ToolSeeder::class,
            CurrencySeeder::class,
        ]);
    }
}
