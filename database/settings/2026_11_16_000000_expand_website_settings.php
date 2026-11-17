<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('website.company_founded_year', 2022);
        $this->migrator->add('website.referral_commission_percentage', 10);
        $this->migrator->add('website.company_location_ar', '6 أكتوبر، مصر');
        $this->migrator->add('website.company_location_en', '6th of October, Egypt');

        $this->migrator->update(
            'website.footer_copyright_text_en',
            fn (): string => '© {year} Qader. All rights reserved.',
        );
        $this->migrator->update(
            'website.footer_copyright_text_ar',
            fn (): string => '© {year} قادر. جميع الحقوق محفوظة.',
        );
        $this->migrator->update(
            'platform.support_email',
            fn (): string => 'info@qadereg.com',
        );
    }
};
