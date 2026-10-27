<?php

namespace App\Modules\V1\Workspaces\Domain\Models;

use App\Modules\V1\AccessControl\Domain\Models\WorkspaceRole;
use App\Modules\V1\Cities\Domain\Models\City;
use App\Modules\V1\Clients\Domain\Models\Client;
use App\Modules\V1\Countries\Domain\Models\Country;
use App\Modules\V1\Industries\Domain\Models\Industry;
use App\Modules\V1\ProfessionalFields\Domain\Models\ProfessionalField;
use App\Modules\V1\Workspaces\Domain\ValueObjects\WorkspaceSizeBandEnum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Workspace extends Model implements HasMedia
{
    use InteractsWithMedia;

    protected $fillable = ['owner_client_id', 'name', 'description', 'is_company', 'country_id', 'city_id', 'size_band', 'website_url'];

    protected function casts(): array
    {
        return ['is_company' => 'boolean', 'size_band' => WorkspaceSizeBandEnum::class];
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(Client::class, 'owner_client_id');
    }

    public function industries(): BelongsToMany
    {
        return $this->belongsToMany(Industry::class, 'workspace_industries')
            ->withTimestamps();
    }

    public function professionalFields(): BelongsToMany
    {
        return $this->belongsToMany(
            ProfessionalField::class,
            'workspace_professional_fields',
        )->withTimestamps();
    }

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }

    public function socialLinks(): HasMany
    {
        return $this->hasMany(WorkspaceSocialLink::class);
    }

    public function members(): HasMany
    {
        return $this->hasMany(WorkspaceMember::class);
    }

    public function roles(): HasMany
    {
        return $this->hasMany(WorkspaceRole::class);
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('logo')->singleFile()->acceptsMimeTypes(['image/png', 'image/jpeg', 'image/webp']);
    }
}
