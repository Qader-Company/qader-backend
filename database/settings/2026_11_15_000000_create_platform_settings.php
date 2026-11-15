<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('hiring.offer_response_duration_hours', 48);
        $this->migrator->add('hiring.post_interview_decision_duration_hours', 24);
        $this->migrator->add('hiring.talent_offer_no_response_limit', 2);

        $this->migrator->add('talent_evaluation.interview_slot_rejection_limit', 2);
        $this->migrator->add('talent_evaluation.confirmed_interview_reschedule_limit', 1);

        $this->migrator->add('trash.retention_days', 30);

        $this->migrator->add('platform.support_response_business_days', 1);
        $this->migrator->add('platform.support_email', null);
        $this->migrator->add('platform.support_phone', null);
        $this->migrator->add('platform.support_whatsapp', null);
        $this->migrator->add('platform.privacy_policy_url', null);
        $this->migrator->add('platform.terms_and_conditions_url', null);

        $this->migrator->add('website.footer_copyright_text_ar', null);
        $this->migrator->add('website.footer_copyright_text_en', '© {year} Qader. All rights reserved.');

        $this->migrator->add('marketplace.anonymous_talent_image', null);

        $this->migrator->add('paid_services.qader_expert_opinion_price', 250.0);
        $this->migrator->add('paid_services.qader_expert_opinion_currency', 'EGP');
        $this->migrator->add('paid_services.detailed_rejection_feedback_price', 100.0);
        $this->migrator->add('paid_services.detailed_rejection_feedback_currency', 'EGP');
        $this->migrator->add('paid_services.profile_expert_review_price', 10.0);
        $this->migrator->add('paid_services.profile_expert_review_currency', 'USD');
    }
};
