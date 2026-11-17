<?php

namespace App\Modules\V1\Trash\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ListTrashRequest extends FormRequest
{
    private const SEARCHABLE_RESOURCES = [
        'job-forms', 'form-submissions', 'talents', 'admins', 'roles',
        'help-requests', 'clients', 'countries', 'currencies', 'languages',
        'industries', 'professional-fields', 'job-titles', 'skills', 'tools',
        'workspaces', 'workspace-roles',
    ];

    private const ACTIVE_RESOURCES = [
        'job-forms', 'roles', 'countries', 'cities', 'currencies', 'languages',
        'industries', 'professional-fields', 'job-titles', 'skills', 'tools',
        'workspace-roles',
    ];

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $resource = (string) $this->route('trashResource');
        $rules = [
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];

        if (in_array($resource, self::SEARCHABLE_RESOURCES, true)) {
            $rules['search'] = ['nullable', 'string', 'max:255'];
        }

        if (in_array($resource, self::ACTIVE_RESOURCES, true)) {
            $rules['is_active'] = ['nullable', 'boolean'];
        }

        return $rules;
    }
}
