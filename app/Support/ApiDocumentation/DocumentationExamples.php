<?php

namespace App\Support\ApiDocumentation;

class DocumentationExamples
{
    public static function shouldKeepGeneratedBodyExample(string $name, ?string $type): bool
    {
        $normalizedName = str_replace('.*.', '.', $name);
        $normalizedName = preg_replace('/^(personal-information|skills-and-experiences|work-preferences)\./', '', $normalizedName);

        return str_contains((string) $type, 'file')
            || in_array($normalizedName, ['languages', 'experiences', 'projects'], true);
    }

    public static function for(string $name, string $uri, ?string $type = null): mixed
    {
        $name = str_replace('.*.', '.', $name);
        $name = preg_replace('/^(personal-information|skills-and-experiences|work-preferences)\./', '', $name);

        $contextual = self::contextual($name, $uri);
        if ($contextual !== null) {
            return $contextual;
        }

        $name = str_contains($name, '.') ? substr($name, strrpos($name, '.') + 1) : $name;

        return match (true) {
            $name === 'search' => 'software',
            $name === 'job_slug', $name === 'jobForm_slug' => 'backend-engineer-a1b2c3d4',
            $name === 'per_page' => 15,
            $name === 'is_active' => true,
            $name === 'provider' => 'google',
            $name === 'purpose' => 'email-verification',
            $name === 'first_name' => 'Ahmed',
            $name === 'last_name' => 'Hassan',
            $name === 'full_name' => 'Ahmed Hassan',
            $name === 'email' => 'example@qader.test',
            $name === 'password', $name === 'password_confirmation' => config('demo_accounts.password'),
            $name === 'phone_country_code', $name === 'whatsapp_country_code' => '+20',
            $name === 'phone_number' => '1012345678',
            $name === 'whatsapp_number' => '1098765432',
            $name === 'date_of_birth' => '1995-05-20',
            $name === 'gender' => 'male',
            $name === 'religion' => 'islam',
            $name === 'proficiency_level' => 'native',
            $name === 'language_proficiency_level' => 'native',
            $name === 'professional_summary' => 'Backend engineer experienced in building scalable APIs.',
            $name === 'seniority_level' => 'mid_level',
            $name === 'years_of_experience_min' => 5,
            $name === 'salary_min' => 20000,
            $name === 'salary_max' => 30000,
            $name === 'years_of_experience' => 5,
            $name === 'linkedin_url' => 'https://www.linkedin.com/in/ahmed-hassan',
            $name === 'github_url' => 'https://github.com/ahmed-hassan',
            $name === 'behance_url' => 'https://www.behance.net/ahmed-hassan',
            $name === 'portfolio_url' => 'https://ahmed-hassan.example.com',
            $name === 'company_url' => 'https://qader.example.com',
            $name === 'url' => 'https://project.example.com',
            $name === 'job_title' => 'Backend Engineer',
            $name === 'company_name' => 'Qader',
            $name === 'started_at' => '2022-01-01',
            $name === 'ended_at' => '2024-01-01',
            $name === 'currently_working' => 0,
            $name === 'responsibilities' => 'Designed APIs and maintained backend services.',
            $name === 'description' => 'Example description for this record.',
            $name === 'size_band' => '11-50',
            $name === 'is_company' => true,
            $name === 'website_url' => 'https://example-company.com',
            $name === 'platform' => 'linkedin',
            $name === 'display_order' => 1,
            $name === 'scope' => 'all',
            $name === 'sort' => 'newest',
            $name === 'submitted_from' => '2026-10-01',
            $name === 'submitted_to' => '2026-10-31',
            $name === 'scheduled_from' => '2026-11-01',
            $name === 'scheduled_to' => '2026-11-30',
            $name === 'status' && str_contains($uri, '/talent-applications') => ['submitted'],
            $name === 'status' && str_contains($uri, '/interviews') => ['confirmed'],
            $name === 'type' && str_contains($uri, '/interviews') => ['technical'],
            $name === 'state' => ['accepted'],
            $name === 'rejection_stage' => 'after_interview',
            $name === 'application_ids' => [1, 2],
            $name === 'reviewer_admin_id' => 2,
            $name === 'interviewer_admin_ids' => [2, 3],
            str_ends_with($name, '_rating') => 4,
            $name === 'recommended_salary_min' => 20000,
            $name === 'recommended_salary_max' => 30000,
            $name === 'reapply_policy' => 'allowed_after_period',
            $name === 'reapply_after_months' => 6,
            $name === 'blacklist_reason' => 'Repeated submission of invalid information.',
            $name === 'final_feedback' => 'The candidate meets the requirements and is recommended to continue.',
            $name === 'hr_report' => 'Strong communication and ownership.',
            $name === 'technical_report' => 'Meets the expected technical level.',
            $name === 'psychology_report' => 'Shows good adaptability and teamwork.',
            $name === 'reason' && str_contains($uri, '/interview/slots/reject') => 'I am unavailable on all offered dates. Please provide times next week.',
            $name === 'meeting_url' => 'https://meet.google.com/abc-defg-hij',
            $name === 'priority' => 'high',
            $name === 'type' && str_contains($uri, '/schedule-interview') => 'technical',
            $name === 'mode' => 'online',
            $name === 'start' => '2026-11-01T12:00:00+02:00',
            $name === 'location' => 'Qader Office, Giza',
            $name === 'latitude' => 30.0444200,
            $name === 'longitude' => 31.2357120,
            $name === 'work_types' => ['full_time'],
            $name === 'work_modes' => ['remote'],
            $name === 'willing_to_relocate' => true,
            $name === 'notice_period_days' => 30,
            $name === 'notice_period_days_max' => 30,
            $name === 'current_salary' => 15000,
            $name === 'expected_salary_min' => 20000,
            $name === 'expected_salary_max' => 30000,
            $name === 'permissions' => ['admin.countries.view'],
            $name === 'role_ids' => [2, 3],
            $name === 'form_fields' => ['github_url', 'portfolio_url'],
            $name === 'professional_form_field_ids' => [12, 23, 38],
            $name === 'screen_states' => ['under_review', 'rejected'],
            $name === 'professional_field_ids', $name === 'industry_ids', $name === 'skill_ids', $name === 'tool_ids',
            $name === 'removed_image_ids' => [1],
            $name === 'role_ids' => [2],
            $name === 'role', $name === 'role_id' => 2,
            $name === 'admin' => 2,
            $name === 'cv', $name === 'images' => null,
            str_ends_with($name, '_id') || in_array($name, [
                'id', 'record', 'country', 'media', 'application', 'interview', 'talent',
            ], true) => 1,
            $type === 'boolean' => true,
            $type === 'integer' => 1,
            $type === 'number', $type === 'float' => 100,
            $type === 'array' => ['example'],
            $type === 'file' => null,
            default => 'example',
        };
    }

    private static function contextual(string $name, string $uri): mixed
    {
        if ($name === 'seniority_level' && str_contains($uri, '/talent-marketplace')) {
            return ['mid_level'];
        }

        if ($name === 'sort' && str_contains($uri, '/talent-marketplace')) {
            return 'highest_match';
        }

        if (str_ends_with($name, 'name') && str_contains($name, 'projects')) {
            return 'Qader Platform';
        }

        if ($name === 'code') {
            return match (true) {
                str_contains($uri, '/countries') => 'XZ',
                str_contains($uri, '/currencies') => 'XQZ',
                str_contains($uri, '/languages') => 'x-demo',
                default => 'DEMO',
            };
        }

        if ($name === 'symbol') {
            return '¤';
        }

        if (in_array($name, ['name.en', 'name.ar'], true)) {
            $arabic = str_ends_with($name, '.ar');

            return match (true) {
                str_contains($uri, '/countries') => $arabic ? 'دولة تجريبية' : 'Example Country',
                str_contains($uri, '/cities') => $arabic ? 'مدينة تجريبية' : 'Example City',
                str_contains($uri, '/languages') => $arabic ? 'لغة تجريبية' : 'Example Language',
                str_contains($uri, '/currencies') => $arabic ? 'عملة تجريبية' : 'Example Currency',
                str_contains($uri, '/industries') => $arabic ? 'التكنولوجيا' : 'Technology',
                str_contains($uri, '/fields') => $arabic ? 'مجال تجريبي' : 'Example Professional Field',
                default => $arabic ? 'اسم تجريبي' : 'Example Name',
            };
        }

        if (in_array($name, ['description.en', 'description.ar'], true)) {
            return str_ends_with($name, '.ar')
                ? 'وصف تجريبي للمجال المهني.'
                : 'Example description for the professional field.';
        }

        if (in_array($name, ['question.en', 'question.ar'], true)) {
            return str_ends_with($name, '.ar')
                ? 'كم تستغرق عملية المراجعة؟'
                : 'How long does the review process take?';
        }

        if (in_array($name, ['answer.en', 'answer.ar'], true)) {
            return str_ends_with($name, '.ar')
                ? 'سيتم إشعارك بمجرد انتقال طلبك إلى المرحلة التالية.'
                : 'You will be notified as soon as your application moves to the next stage.';
        }

        if ($name === 'name') {
            return match (true) {
                str_contains($uri, '/access-control/roles') => 'Content Manager',
                str_contains($uri, '/job-titles') => 'Backend Engineer',
                str_contains($uri, '/skills') => 'Laravel',
                str_contains($uri, '/tools') => 'Docker',
                str_contains($uri, '/projects') => 'Qader Platform',
                str_contains($uri, '/workspaces') => 'Qader Technology',
                default => 'Example Name',
            };
        }

        if ($name === 'email' && str_contains($uri, '/admin/admins')) {
            return 'new.admin@qader.test';
        }

        return null;
    }
}
