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
        // The maintenance API runs Artisan through the web process. Running the
        // child seeders silently avoids Termwind console rendering on hosts
        // where the mbstring extension is unavailable.
        $this->callSilent([
            PermissionSeeder::class,
            DemoAccountSeeder::class,
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
