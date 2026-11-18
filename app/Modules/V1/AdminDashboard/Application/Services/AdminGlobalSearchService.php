<?php

namespace App\Modules\V1\AdminDashboard\Application\Services;

use App\Modules\V1\Clients\Domain\Models\Client;
use App\Modules\V1\HelpRequests\Domain\Models\HelpRequest;
use App\Modules\V1\HiringRequests\Domain\Models\HiringRequest;
use App\Modules\V1\TalentApplications\Domain\Models\TalentApplication;
use App\Modules\V1\Talents\Domain\Models\Talent;
use Illuminate\Database\Eloquent\Builder;

class AdminGlobalSearchService
{
    public function search(string $term, int $limit): array
    {
        $like = '%'.addcslashes(trim($term), '%_\\').'%';
        $requests = collect()
            ->concat($this->hiringRequests($like, $limit))
            ->concat($this->helpRequests($like, $limit))
            ->concat($this->talentApplications($like, $limit))
            ->sortByDesc('created_at')
            ->take($limit)
            ->values();

        return [
            'query' => trim($term),
            'requests' => $requests,
            'talents' => $this->talents($like, $limit),
            'clients' => $this->clients($like, $limit),
        ];
    }

    private function hiringRequests(string $like, int $limit)
    {
        return HiringRequest::query()
            ->with('workspace')
            ->where(function (Builder $query) use ($like): void {
                $query->where('reference', 'like', $like)
                    ->orWhereHas('workspace', fn (Builder $workspace) => $workspace->where('name', 'like', $like));
            })
            ->latest()
            ->limit($limit)
            ->get()
            ->map(fn (HiringRequest $request): array => [
                'type' => 'hiring_request',
                'id' => $request->id,
                'reference' => $request->reference,
                'title' => $request->reference,
                'subtitle' => $request->workspace?->name,
                'created_at' => $request->created_at,
            ]);
    }

    private function helpRequests(string $like, int $limit)
    {
        return HelpRequest::query()
            ->with('workspace')
            ->where(function (Builder $query) use ($like): void {
                $query->where('reference', 'like', $like)
                    ->orWhere('description', 'like', $like)
                    ->orWhereHas('workspace', fn (Builder $workspace) => $workspace->where('name', 'like', $like));
            })
            ->latest()
            ->limit($limit)
            ->get()
            ->map(fn (HelpRequest $request): array => [
                'type' => 'help_request',
                'id' => $request->id,
                'reference' => $request->reference,
                'title' => $request->reference,
                'subtitle' => $request->workspace?->name,
                'created_at' => $request->created_at,
            ]);
    }

    private function talentApplications(string $like, int $limit)
    {
        return TalentApplication::query()
            ->with('talent.user')
            ->where(function (Builder $query) use ($like): void {
                $query->whereHas('talent', fn (Builder $talent) => $talent->where('talent_code', 'like', $like))
                    ->orWhereHas('talent.user', fn (Builder $user) => $this->userMatches($user, $like));
            })
            ->latest()
            ->limit($limit)
            ->get()
            ->map(function (TalentApplication $application): array {
                $user = $application->talent?->user;

                return [
                    'type' => 'talent_application',
                    'id' => $application->id,
                    'reference' => null,
                    'title' => $user === null ? null : trim($user->first_name.' '.$user->last_name),
                    'subtitle' => $application->talent?->talent_code,
                    'created_at' => $application->created_at,
                ];
            });
    }

    private function talents(string $like, int $limit)
    {
        return Talent::query()
            ->with(['user', 'jobTitle'])
            ->where(function (Builder $query) use ($like): void {
                $query->where('talent_code', 'like', $like)
                    ->orWhereHas('user', fn (Builder $user) => $this->userMatches($user, $like))
                    ->orWhereHas('jobTitle', fn (Builder $jobTitle) => $jobTitle->where('name', 'like', $like));
            })
            ->latest()
            ->limit($limit)
            ->get()
            ->map(fn (Talent $talent): array => [
                'id' => $talent->id,
                'talent_code' => $talent->talent_code,
                'full_name' => trim($talent->user->first_name.' '.$talent->user->last_name),
                'job_title' => $talent->jobTitle?->name,
            ])->values();
    }

    private function clients(string $like, int $limit)
    {
        return Client::query()
            ->with(['user', 'ownedWorkspaces'])
            ->where(function (Builder $query) use ($like): void {
                $query->whereHas('user', fn (Builder $user) => $this->userMatches($user, $like))
                    ->orWhereHas('ownedWorkspaces', fn (Builder $workspace) => $workspace->where('name', 'like', $like));
            })
            ->latest()
            ->limit($limit)
            ->get()
            ->map(fn (Client $client): array => [
                'id' => $client->id,
                'full_name' => trim($client->user->first_name.' '.$client->user->last_name),
                'email' => $client->user->email,
                'workspaces' => $client->ownedWorkspaces->map(fn ($workspace): array => [
                    'id' => $workspace->id,
                    'name' => $workspace->name,
                ])->values(),
            ])->values();
    }

    private function userMatches(Builder $query, string $like): void
    {
        $query->where(function (Builder $query) use ($like): void {
            $query->where('first_name', 'like', $like)
                ->orWhere('last_name', 'like', $like)
                ->orWhere('email', 'like', $like);
        });
    }
}
