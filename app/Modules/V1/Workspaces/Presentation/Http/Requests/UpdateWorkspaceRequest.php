<?php

namespace App\Modules\V1\Workspaces\Presentation\Http\Requests;

use App\Modules\V1\Workspaces\Domain\Models\Workspace;
use App\Modules\V1\Workspaces\Domain\ValueObjects\WorkspaceSizeBandEnum;
use App\Modules\V1\Workspaces\Domain\ValueObjects\WorkspaceSocialPlatformEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateWorkspaceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $routeWorkspace = $this->route('workspace');
        $countryId = $this->input('country_id');
        if ($countryId === null && $routeWorkspace instanceof Workspace) {
            $countryId = $routeWorkspace->country_id;
        }

        return [
            'name' => ['sometimes', 'string', 'max:255'], 'industry_id' => ['sometimes', Rule::exists('industries', 'id')->where('is_active', true)],
            'description' => ['sometimes', 'string', 'max:5000'], 'is_company' => ['sometimes', 'boolean'],
            'country_id' => ['sometimes', Rule::exists('countries', 'id')->where('is_active', true)],
            'city_id' => ['sometimes', 'nullable', Rule::exists('cities', 'id')->where(fn ($q) => $q->where('country_id', $countryId)->where('is_active', true))],
            'size_band' => ['sometimes', Rule::enum(WorkspaceSizeBandEnum::class)], 'website_url' => ['sometimes', 'nullable', 'url', 'max:2048'],
            'logo' => ['sometimes', 'nullable', 'file', 'mimetypes:image/png,image/jpeg,image/webp', 'max:5120'],
            'remove_logo' => ['sometimes', 'boolean'], 'social_links' => ['sometimes', 'nullable', 'array', 'max:30'],
            'social_links.*.platform' => ['required', Rule::enum(WorkspaceSocialPlatformEnum::class)], 'social_links.*.url' => ['required', 'url', 'max:2048'],
        ];
    }
}
