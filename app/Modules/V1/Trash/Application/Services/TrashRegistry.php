<?php

namespace App\Modules\V1\Trash\Application\Services;

use App\Modules\V1\AccessControl\Domain\Models\Role;
use App\Modules\V1\AccessControl\Domain\Models\WorkspaceRole;
use App\Modules\V1\Admins\Domain\Models\Admin;
use App\Modules\V1\Cities\Domain\Models\City;
use App\Modules\V1\Clients\Domain\Models\Client;
use App\Modules\V1\Countries\Domain\Models\Country;
use App\Modules\V1\Currencies\Domain\Models\Currency;
use App\Modules\V1\HelpRequests\Domain\Models\HelpRequest;
use App\Modules\V1\Industries\Domain\Models\Industry;
use App\Modules\V1\JobForms\Domain\Models\JobFormSubmission;
use App\Modules\V1\JobForms\Domain\Models\JobPosting;
use App\Modules\V1\JobTitles\Domain\Models\JobTitle;
use App\Modules\V1\Languages\Domain\Models\Language;
use App\Modules\V1\ProfessionalFields\Domain\Models\ProfessionalField;
use App\Modules\V1\Skills\Domain\Models\Skill;
use App\Modules\V1\Talents\Domain\Models\Talent;
use App\Modules\V1\Tools\Domain\Models\Tool;
use App\Modules\V1\Workspaces\Domain\Models\Workspace;
use App\Modules\V1\Workspaces\Domain\Models\WorkspaceMember;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

class TrashRegistry
{
    /** @return array<string, array{model: class-string<Model>, relations: array<int, string>, search: array<int, string>}> */
    public function definitions(): array
    {
        return [
            'job-forms' => ['model' => JobPosting::class, 'relations' => ['professionalField.translations', 'jobTitle'], 'search' => ['title', 'slug']],
            'form-submissions' => ['model' => JobFormSubmission::class, 'relations' => ['version.posting'], 'search' => ['applicant_name', 'email', 'phone_number']],
            'talents' => ['model' => Talent::class, 'relations' => ['user', 'professionalField.translations', 'jobTitle', 'applications'], 'search' => ['talent_code', 'phone_number']],
            'admins' => ['model' => Admin::class, 'relations' => ['user.roles'], 'search' => ['phone_number', 'display_name_ar']],
            'roles' => ['model' => Role::class, 'relations' => [], 'search' => ['name', 'name_en', 'name_ar']],
            'help-requests' => ['model' => HelpRequest::class, 'relations' => ['workspace'], 'search' => ['reference', 'description', 'contact_email', 'contact_phone_number']],
            'clients' => ['model' => Client::class, 'relations' => ['user'], 'search' => ['phone_number']],
            'countries' => ['model' => Country::class, 'relations' => ['translations'], 'search' => ['code']],
            'cities' => ['model' => City::class, 'relations' => ['translations', 'country.translations'], 'search' => []],
            'currencies' => ['model' => Currency::class, 'relations' => ['translations'], 'search' => ['code', 'symbol']],
            'languages' => ['model' => Language::class, 'relations' => ['translations'], 'search' => ['code']],
            'industries' => ['model' => Industry::class, 'relations' => ['translations'], 'search' => ['slug']],
            'professional-fields' => ['model' => ProfessionalField::class, 'relations' => ['translations'], 'search' => ['slug']],
            'job-titles' => ['model' => JobTitle::class, 'relations' => [], 'search' => ['name', 'slug']],
            'skills' => ['model' => Skill::class, 'relations' => [], 'search' => ['name', 'slug']],
            'tools' => ['model' => Tool::class, 'relations' => [], 'search' => ['name', 'slug']],
            'workspaces' => ['model' => Workspace::class, 'relations' => ['industries.translations', 'professionalFields.translations'], 'search' => ['name']],
            'team' => ['model' => WorkspaceMember::class, 'relations' => ['client.user', 'roles'], 'search' => []],
            'workspace-roles' => ['model' => WorkspaceRole::class, 'relations' => [], 'search' => ['key', 'name_en', 'name_ar']],
        ];
    }

    public function definition(string $key): array
    {
        return $this->definitions()[$key]
            ?? throw new InvalidArgumentException("Unknown trash resource [$key].");
    }

    public function query(string $key): Builder
    {
        $definition = $this->definition($key);
        $model = $definition['model'];
        $query = $model::query()->visibleInTrash()->with($definition['relations']);

        if ($key === 'roles') {
            $query->where('portal', 'admin');
        }

        return $query;
    }

    public function findVisible(string $key, int $id, bool $lock = false): Model
    {
        $query = $this->query($key);
        if ($lock) {
            $query->lockForUpdate();
        }

        return $query->findOrFail($id);
    }
}
