<?php

namespace App\Modules\V1\Workspaces\Application\Services;

use App\Modules\V1\AccessControl\Application\Services\WorkspaceAccessService;
use App\Modules\V1\AccessControl\Domain\Models\WorkspaceRole;
use App\Modules\V1\Clients\Domain\Models\Client;
use App\Modules\V1\Users\Domain\Models\User;
use App\Modules\V1\Workspaces\Domain\Models\Workspace;
use App\Modules\V1\Workspaces\Domain\Models\WorkspaceMember;
use App\Modules\V1\Workspaces\Domain\ValueObjects\WorkspaceMemberStatusEnum;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class WorkspaceService
{
    public function __construct(private readonly WorkspaceAccessService $access) {}

    public function list(User $user)
    {
        $client = $this->client($user);

        return Workspace::query()->whereHas('members', fn ($q) => $q->where('client_id', $client->id)->where('status', 'active'))
            ->with(['industry.translations', 'country.translations', 'city.translations', 'socialLinks', 'media'])->withCount(['members' => fn ($q) => $q->where('status', 'active')])->latest()->get();
    }

    public function create(User $user, array $data): Workspace
    {
        $client = $this->client($user);
        $logo = Arr::pull($data, 'logo');
        $links = Arr::pull($data, 'social_links', []);

        return DB::transaction(function () use ($client, $data, $logo, $links) {
            $workspace = Workspace::query()->create(['owner_client_id' => $client->id, ...$data]);
            $member = WorkspaceMember::query()->create(['workspace_id' => $workspace->id, 'client_id' => $client->id, 'status' => WorkspaceMemberStatusEnum::ACTIVE, 'joined_at' => now()]);
            $owner = WorkspaceRole::query()->create(['workspace_id' => $workspace->id, 'key' => 'owner', 'name_en' => 'Owner', 'name_ar' => 'المالك', 'is_active' => true]);
            $member->roles()->attach($owner->id);
            $workspace->socialLinks()->createMany($links);
            if ($logo) {
                $workspace->addMedia($logo)->toMediaCollection('logo', 'public');
            }

            return $this->load($workspace);
        });
    }

    public function show(User $user, Workspace $workspace): Workspace
    {
        $this->access->authorize($user, $workspace, 'client.workspace.view');

        return $this->load($workspace);
    }

    public function update(User $user, Workspace $workspace, array $data): Workspace
    {
        $this->access->authorize($user, $workspace, 'client.workspace.update');
        $logo = Arr::pull($data, 'logo');
        $remove = (bool) Arr::pull($data, 'remove_logo', false);
        $hasLinks = array_key_exists('social_links', $data);
        $links = Arr::pull($data, 'social_links', []);

        if (array_key_exists('country_id', $data) && ! array_key_exists('city_id', $data)) {
            $data['city_id'] = null;
        }

        return DB::transaction(function () use ($workspace, $data, $logo, $remove, $hasLinks, $links) {
            $workspace->update($data);
            if ($hasLinks) {
                $workspace->socialLinks()->delete();
                $workspace->socialLinks()->createMany($links ?? []);
            }
            if ($remove) {
                $workspace->clearMediaCollection('logo');
            }
            if ($logo) {
                $workspace->addMedia($logo)->toMediaCollection('logo', 'public');
            }

            return $this->load($workspace->refresh());
        });
    }

    public function dashboard(User $user, Workspace $workspace): array
    {
        $this->access->authorize($user, $workspace, 'client.dashboard.view');

        return ['workspace_id' => $workspace->id, 'team_members' => $workspace->members()->where('status', 'active')->count(), 'active_hiring_requests' => 0, 'active_contracts' => 0, 'active_projects' => 0, 'recent_hiring_requests' => [], 'team' => [], 'projects' => []];
    }

    private function client(User $user): Client
    {
        $client = $user->loadMissing('client')->client;
        if (! $client) {
            throw new AuthorizationException(__('workspaces.client_required'));
        }

        return $client;
    }

    private function load(Workspace $workspace): Workspace
    {
        return $workspace->load(['industry.translations', 'country.translations', 'city.translations', 'socialLinks', 'media'])->loadCount(['members' => fn ($q) => $q->where('status', 'active')]);
    }
}
