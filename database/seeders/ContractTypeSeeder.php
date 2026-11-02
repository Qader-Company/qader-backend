<?php

namespace Database\Seeders;

use App\Modules\V1\ContractTypes\Domain\Models\ContractType;
use Illuminate\Database\Seeder;

class ContractTypeSeeder extends Seeder
{
    public function run(): void
    {
        ContractType::query()->updateOrCreate(
            ['type' => 'managed'],
            ['name_en' => 'Managed Contract', 'name_ar' => 'عقد مُدار']
        );
        ContractType::query()->updateOrCreate(
            ['type' => 'one_time'],
            ['name_en' => 'One-Time Commission', 'name_ar' => 'عمولة لمرة واحدة']
        );
    }
}
