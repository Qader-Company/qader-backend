<?php

namespace App\Modules\V1\Workspaces\Presentation\Http\Requests;

use App\Modules\V1\Workspaces\Domain\ValueObjects\WorkspaceSizeBandEnum;
use App\Modules\V1\Workspaces\Domain\ValueObjects\WorkspaceSocialPlatformEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreWorkspaceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'industry_id' => ['required', Rule::exists('industries', 'id')->where('is_active', true)],
            'description' => ['required', 'string', 'max:5000'], 'is_company' => ['required', 'boolean'],
            'country_id' => ['required', Rule::exists('countries', 'id')->where('is_active', true)],
            'city_id' => ['nullable', Rule::exists('cities', 'id')->where(fn ($q) => $q->where('country_id', $this->input('country_id'))->where('is_active', true))],
            'size_band' => ['required', Rule::enum(WorkspaceSizeBandEnum::class)],
            'website_url' => ['nullable', 'url', 'max:2048'],
            'logo' => ['nullable', 'file', 'mimetypes:image/png,image/jpeg,image/webp', 'max:5120'],
            'social_links' => ['nullable', 'array', 'max:30'],
            'social_links.*.platform' => ['required', Rule::enum(WorkspaceSocialPlatformEnum::class)],
            'social_links.*.url' => ['required', 'url', 'max:2048'],
        ];
    }
}
