<?php

namespace App\Modules\V1\Trash\Application\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

class TrashRelationshipGuard
{
    /** @var array<string, array<int, array{0: string, 1: string}>> */
    private array $dependencies = [
        'admins' => [
            ['talent_applications', 'assigned_admin_id'], ['talent_applications', 'review_decided_by_admin_id'],
            ['talent_application_reviewer_assignments', 'reviewer_admin_id'], ['talent_application_reviewer_assignments', 'assigned_by_admin_id'],
            ['talent_evaluations', 'evaluator_admin_id'], ['interview_evaluations', 'evaluator_admin_id'],
            ['talent_blacklist_entries', 'blocked_by_admin_id'], ['talent_blacklist_entries', 'removed_by_admin_id'],
            ['interviews', 'created_by_admin_id'], ['interview_admin', 'admin_id'],
            ['talent_application_events', 'admin_id'], ['hiring_requests', 'assigned_admin_id'],
            ['hiring_interviews', 'proposed_by_admin_id'], ['hiring_interviews', 'confirmed_by_admin_id'],
            ['help_requests', 'assigned_admin_id'], ['help_requests', 'closed_by_admin_id'],
            ['help_request_notes', 'admin_id'], ['client_management_events', 'actor_admin_id'],
            ['platform_setting_events', 'actor_admin_id'],
            ['job_postings', 'created_by_admin_id'], ['job_form_submissions', 'converted_by_admin_id'],
        ],
        'talents' => [
            ['talent_applications', 'talent_id'], ['workspace_talent_selections', 'talent_id'],
            ['hiring_request_items', 'talent_id'], ['job_form_submissions', 'converted_talent_id'],
            ['talent_languages', 'talent_id'], ['talent_skills', 'talent_id'],
            ['talent_tools', 'talent_id'], ['talent_experiences', 'talent_id'],
            ['talent_projects', 'talent_id'], ['talent_work_types', 'talent_id'],
            ['talent_work_modes', 'talent_id'], ['talent_industries', 'talent_id'],
        ],
        'clients' => [['workspaces', 'owner_client_id'], ['workspace_members', 'client_id']],
        'countries' => [
            ['cities', 'country_id'], ['workspaces', 'country_id'], ['talents', 'nationality_country_id'],
            ['talents', 'residence_country_id'],
        ],
        'cities' => [['workspaces', 'city_id'], ['talents', 'residence_city_id']],
        'currencies' => [
            ['talents', 'current_currency_id'], ['talents', 'expected_currency_id'],
            ['talent_evaluations', 'currency_id'], ['hiring_offers', 'currency_id'],
        ],
        'languages' => [['talent_languages', 'language_id']],
        'industries' => [['talent_industries', 'industry_id'], ['workspace_industries', 'industry_id']],
        'professional-fields' => [
            ['talents', 'professional_field_id'], ['job_postings', 'professional_field_id'],
            ['workspace_professional_fields', 'professional_field_id'], ['job_title_professional_field', 'professional_field_id'],
            ['professional_field_skill', 'professional_field_id'], ['professional_field_tool', 'professional_field_id'],
        ],
        'job-titles' => [['talents', 'job_title_id'], ['job_postings', 'job_title_id'], ['job_title_professional_field', 'job_title_id']],
        // Skills and tools remain linked to historical records after soft deletion.
        'skills' => [],
        'tools' => [],
        'form-submissions' => [],
        'help-requests' => [['hiring_requests', 'source_help_request_id']],
    ];

    public function ensureCanRemove(string $key, Model $model): void
    {
        foreach ($this->dependencies[$key] ?? [] as [$table, $column]) {
            if (Schema::hasTable($table) && Schema::hasColumn($table, $column)
                && DB::table($table)->where($column, $model->getKey())->exists()) {
                throw new ConflictHttpException(__('trash.has_dependencies'));
            }
        }

        match ($key) {
            'roles' => $this->guardAdminRole($model),
            'workspace-roles' => $this->guardWorkspaceRole($model),
            'workspaces' => $this->guardWorkspace($model),
            'team' => $this->guardMember($model),
            'job-forms' => $this->guardJobForm($model),
            'form-submissions' => $this->guardSubmission($model),
            default => null,
        };
    }

    private function guardAdminRole(Model $role): void
    {
        if (DB::table('model_has_roles')->where('role_id', $role->getKey())->exists()) {
            throw new ConflictHttpException(__('trash.role_has_members'));
        }
    }

    private function guardWorkspaceRole(Model $role): void
    {
        if (DB::table('workspace_member_roles')->where('workspace_role_id', $role->getKey())->exists()) {
            throw new ConflictHttpException(__('trash.role_has_members'));
        }
    }

    private function guardWorkspace(Model $workspace): void
    {
        $id = $workspace->getKey();
        $owner = $workspace->getAttribute('owner_client_id');
        $checks = [
            ['workspace_members', 'workspace_id', fn ($query) => $query->where('client_id', '!=', $owner)],
            ['workspace_roles', 'workspace_id', fn ($query) => $query->where('key', '!=', 'owner')],
            ['workspace_talent_selections', 'workspace_id', null], ['hiring_requests', 'workspace_id', null],
            ['help_requests', 'workspace_id', null], ['projects', 'workspace_id', null], ['tasks', 'workspace_id', null],
        ];

        foreach ($checks as [$table, $column, $scope]) {
            if (! Schema::hasTable($table) || ! Schema::hasColumn($table, $column)) {
                continue;
            }
            $query = DB::table($table)->where($column, $id);
            $scope?->__invoke($query);
            if ($query->exists()) {
                throw new ConflictHttpException(__('trash.workspace_not_empty'));
            }
        }
    }

    private function guardMember(Model $member): void
    {
        foreach ([['hiring_requests', 'created_by_member_id'], ['help_requests', 'created_by_member_id'], ['workspace_talent_selections', 'added_by_member_id']] as [$table, $column]) {
            if (Schema::hasTable($table) && Schema::hasColumn($table, $column)
                && DB::table($table)->where($column, $member->getKey())->exists()) {
                throw new ConflictHttpException(__('trash.member_has_dependencies'));
            }
        }
    }

    private function guardJobForm(Model $jobForm): void
    {
        if (Schema::hasTable('job_form_versions') && DB::table('job_form_versions')
            ->join('job_form_submissions', 'job_form_submissions.form_version_id', '=', 'job_form_versions.id')
            ->where('job_form_versions.job_posting_id', $jobForm->getKey())->exists()) {
            throw new ConflictHttpException(__('trash.job_form_has_submissions'));
        }
    }

    private function guardSubmission(Model $submission): void
    {
        if ($submission->getAttribute('converted_talent_id') !== null) {
            throw new ConflictHttpException(__('trash.submission_converted'));
        }
    }
}
