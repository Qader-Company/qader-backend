<?php

namespace App\Modules\V1\AdminDashboard\Application\Services;

use App\Modules\V1\AdminDashboard\Domain\ValueObjects\DashboardPeriodEnum;
use App\Modules\V1\Clients\Domain\Models\Client;
use App\Modules\V1\HelpRequests\Domain\Models\HelpRequest;
use App\Modules\V1\HelpRequests\Domain\ValueObjects\HelpRequestStatusEnum;
use App\Modules\V1\HiringRequests\Domain\Models\HiringInterview;
use App\Modules\V1\HiringRequests\Domain\Models\HiringOffer;
use App\Modules\V1\HiringRequests\Domain\Models\HiringRequest;
use App\Modules\V1\HiringRequests\Domain\ValueObjects\HiringInterviewStatusEnum;
use App\Modules\V1\HiringRequests\Domain\ValueObjects\HiringOfferStatusEnum;
use App\Modules\V1\HiringRequests\Domain\ValueObjects\HiringRequestItemStatusEnum;
use App\Modules\V1\HiringRequests\Domain\ValueObjects\HiringRequestStatusEnum;
use App\Modules\V1\TalentApplications\Domain\Models\TalentApplication;
use App\Modules\V1\TalentApplications\Domain\ValueObjects\TalentApplicationStatusEnum;
use App\Modules\V1\Talents\Domain\Models\Talent;
use App\Support\ApiMetadata\EnumLabel;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class AdminDashboardService
{
    private const LIST_LIMIT = 4;

    public function get(DashboardPeriodEnum $period): array
    {
        [$from, $to] = $this->range($period);

        return [
            'period' => [
                'key' => $period->value,
                'from' => $from->toIso8601String(),
                'to' => $to->toIso8601String(),
            ],
            'metrics' => $this->metrics($from, $to),
            'applications_and_offers' => $this->applicationsAndOffers($from, $to),
            'upcoming_interviews' => $this->upcomingInterviews(),
            'hiring_requests' => $this->hiringRequests(),
            'latest_clients' => $this->latestClients(),
            'help_requests' => $this->helpRequests($from, $to),
        ];
    }

    private function metrics(CarbonInterface $from, CarbonInterface $to): array
    {
        $today = now();

        return [
            'new_applications' => [
                'value' => TalentApplication::query()->whereBetween('submitted_at', [$from, $to])->count(),
                'awaiting_review' => TalentApplication::query()
                    ->where('status', TalentApplicationStatusEnum::SUBMITTED->value)
                    ->count(),
            ],
            'interviews' => [
                'value' => HiringInterview::query()
                    ->whereBetween('starts_at', [$from, $to])
                    ->where('status', '!=', HiringInterviewStatusEnum::CANCELLED->value)
                    ->count(),
                'today' => HiringInterview::query()
                    ->whereBetween('starts_at', [$today->copy()->startOfDay(), $today->copy()->endOfDay()])
                    ->where('status', '!=', HiringInterviewStatusEnum::CANCELLED->value)
                    ->count(),
            ],
            'talents' => [
                'value' => Talent::query()->count(),
                'added_in_period' => Talent::query()->whereBetween('created_at', [$from, $to])->count(),
            ],
            'active_clients' => [
                'value' => Client::query()->whereHas('user', fn (Builder $query) => $query->where('is_active', true))->count(),
                'joined_in_period' => Client::query()
                    ->whereHas('user', fn (Builder $query) => $query->where('is_active', true))
                    ->whereBetween('created_at', [$from, $to])
                    ->count(),
            ],
        ];
    }

    private function applicationsAndOffers(CarbonInterface $from, CarbonInterface $to): array
    {
        $applications = TalentApplication::query()
            ->whereBetween('submitted_at', [$from, $to])
            ->selectRaw('status, count(*) as aggregate')
            ->whereIn('status', [
                TalentApplicationStatusEnum::SUBMITTED->value,
                TalentApplicationStatusEnum::SCHEDULED->value,
                TalentApplicationStatusEnum::ACCEPTED->value,
            ])
            ->groupBy('status')
            ->pluck('aggregate', 'status');

        $offers = HiringOffer::query()
            ->whereBetween('sent_at', [$from, $to])
            ->selectRaw('status, count(*) as aggregate')
            ->whereIn('status', [
                HiringOfferStatusEnum::PENDING->value,
                HiringOfferStatusEnum::COUNTERED->value,
                HiringOfferStatusEnum::ACCEPTED->value,
                HiringOfferStatusEnum::REJECTED->value,
            ])
            ->groupBy('status')
            ->pluck('aggregate', 'status');

        return [
            'applications' => collect([
                TalentApplicationStatusEnum::SUBMITTED,
                TalentApplicationStatusEnum::SCHEDULED,
                TalentApplicationStatusEnum::ACCEPTED,
            ])->map(fn (TalentApplicationStatusEnum $status): array => [
                'status' => $status->value,
                'label' => EnumLabel::for($status, 'talent_application_statuses'),
                'count' => (int) ($applications[$status->value] ?? 0),
            ])->values(),
            'offers' => collect([
                HiringOfferStatusEnum::PENDING,
                HiringOfferStatusEnum::COUNTERED,
                HiringOfferStatusEnum::ACCEPTED,
                HiringOfferStatusEnum::REJECTED,
            ])->map(fn (HiringOfferStatusEnum $status): array => [
                'status' => $status->value,
                'label' => EnumLabel::for($status, 'hiring_offer_statuses'),
                'count' => (int) ($offers[$status->value] ?? 0),
            ])->values(),
        ];
    }

    private function upcomingInterviews(): array
    {
        return HiringInterview::query()
            ->with([
                'item.talent.user',
                'item.jobTitle',
                'item.hiringRequest.workspace',
            ])
            ->whereBetween('starts_at', [now(), now()->endOfDay()])
            ->whereNotIn('status', [
                HiringInterviewStatusEnum::COMPLETED->value,
                HiringInterviewStatusEnum::CANCELLED->value,
            ])
            ->orderBy('starts_at')
            ->limit(self::LIST_LIMIT)
            ->get()
            ->map(function (HiringInterview $interview): array {
                $item = $interview->item;
                $talentUser = $item?->talent?->user;

                return [
                    'id' => $interview->id,
                    'request_item_id' => $item?->id,
                    'start' => $interview->starts_at,
                    'end' => $interview->ends_at,
                    'status' => $interview->status?->value,
                    'status_label' => EnumLabel::for($interview->status, 'hiring_interview_statuses'),
                    'talent' => $talentUser === null ? null : [
                        'id' => $item->talent->id,
                        'full_name' => trim($talentUser->first_name.' '.$talentUser->last_name),
                    ],
                    'job_title' => $item?->jobTitle === null ? null : [
                        'id' => $item->jobTitle->id,
                        'name' => $item->jobTitle->name,
                    ],
                    'workspace' => $item?->hiringRequest?->workspace === null ? null : [
                        'id' => $item->hiringRequest->workspace->id,
                        'name' => $item->hiringRequest->workspace->name,
                    ],
                ];
            })->values()->all();
    }

    private function hiringRequests(): array
    {
        $open = HiringRequest::query()->whereIn('status', [
            HiringRequestStatusEnum::PENDING->value,
            HiringRequestStatusEnum::ACTIVE->value,
        ]);
        $onHold = $this->onHoldHiringRequests();
        $onHoldCount = (clone $onHold)->count();
        $activeCount = HiringRequest::query()
            ->where('status', HiringRequestStatusEnum::ACTIVE->value)
            ->count() - $onHoldCount;

        $latest = (clone $open)
            ->with(['workspace', 'items.jobTitle'])
            ->latest('created_at')
            ->latest('id')
            ->first();

        return [
            'open_count' => (clone $open)->count(),
            'summary' => [
                'in_review' => HiringRequest::query()->where('status', HiringRequestStatusEnum::PENDING->value)->count(),
                'active' => max(0, $activeCount),
                'on_hold' => $onHoldCount,
            ],
            'latest' => $latest === null ? null : [
                'id' => $latest->id,
                'reference' => $latest->reference,
                'status' => $latest->status?->value,
                'workspace' => $latest->workspace === null ? null : [
                    'id' => $latest->workspace->id,
                    'name' => $latest->workspace->name,
                ],
                'positions' => $latest->items
                    ->groupBy('job_title_id')
                    ->map(fn ($items): array => [
                        'job_title' => $items->first()->jobTitle?->name,
                        'count' => $items->count(),
                    ])->values(),
                'created_at' => $latest->created_at,
            ],
        ];
    }

    private function onHoldHiringRequests(): Builder
    {
        $runningItemStatuses = [
            HiringRequestItemStatusEnum::PENDING->value,
            HiringRequestItemStatusEnum::OFFER->value,
            HiringRequestItemStatusEnum::INTERVIEW->value,
            HiringRequestItemStatusEnum::ACCEPTED->value,
        ];

        return HiringRequest::query()
            ->where('status', HiringRequestStatusEnum::ACTIVE->value)
            ->whereHas('items', fn (Builder $query) => $query->where('status', HiringRequestItemStatusEnum::ON_HOLD->value))
            ->whereDoesntHave('items', fn (Builder $query) => $query->whereIn('status', $runningItemStatuses));
    }

    private function latestClients(): array
    {
        return Client::query()
            ->whereHas('user', fn (Builder $query) => $query->where('is_active', true))
            ->with(['user', 'ownedWorkspaces.industries.translations'])
            ->latest('created_at')
            ->latest('id')
            ->limit(self::LIST_LIMIT)
            ->get()
            ->map(function (Client $client): array {
                $workspace = $client->ownedWorkspaces->sortByDesc('created_at')->first();
                $fullName = trim($client->user->first_name.' '.$client->user->last_name);

                return [
                    'id' => $client->id,
                    'full_name' => $fullName,
                    'initials' => $this->initials($fullName),
                    'workspace' => $workspace === null ? null : [
                        'id' => $workspace->id,
                        'name' => $workspace->name,
                    ],
                    'industry' => $workspace?->industries->first()?->name,
                    'registered_at' => $client->created_at,
                ];
            })->values()->all();
    }

    private function helpRequests(CarbonInterface $from, CarbonInterface $to): array
    {
        $openStatuses = [HelpRequestStatusEnum::NEW->value, HelpRequestStatusEnum::IN_PROGRESS->value];
        $latest = HelpRequest::query()
            ->with(['workspace', 'createdByMember.client.user', 'assignedAdmin.user'])
            ->whereIn('status', $openStatuses)
            ->latest('created_at')
            ->latest('id')
            ->first();

        return [
            'open_count' => HelpRequest::query()->whereIn('status', $openStatuses)->count(),
            'unassigned_count' => HelpRequest::query()
                ->whereIn('status', $openStatuses)
                ->whereNull('assigned_admin_id')
                ->count(),
            'summary' => [
                'new' => HelpRequest::query()->where('status', HelpRequestStatusEnum::NEW->value)->count(),
                'in_progress' => HelpRequest::query()->where('status', HelpRequestStatusEnum::IN_PROGRESS->value)->count(),
                'closed_in_period' => HelpRequest::query()
                    ->where('status', HelpRequestStatusEnum::CLOSED->value)
                    ->whereBetween('closed_at', [$from, $to])
                    ->count(),
            ],
            'latest' => $latest === null ? null : [
                'id' => $latest->id,
                'reference' => $latest->reference,
                'title' => EnumLabel::for($latest->help_type, 'help_request_types'),
                'status' => $latest->status?->value,
                'status_label' => EnumLabel::for($latest->status, 'help_request_statuses'),
                'contact_method' => $latest->contact_method?->value,
                'contact_method_label' => EnumLabel::for($latest->contact_method, 'help_request_contact_methods'),
                'workspace' => $latest->workspace === null ? null : [
                    'id' => $latest->workspace->id,
                    'name' => $latest->workspace->name,
                ],
                'assigned_admin' => $latest->assignedAdmin?->user === null ? null : [
                    'id' => $latest->assignedAdmin->id,
                    'full_name' => trim($latest->assignedAdmin->user->first_name.' '.$latest->assignedAdmin->user->last_name),
                ],
                'created_at' => $latest->created_at,
            ],
        ];
    }

    /** @return array{0: CarbonInterface, 1: CarbonInterface} */
    private function range(DashboardPeriodEnum $period): array
    {
        $now = now();

        return match ($period) {
            DashboardPeriodEnum::DAY => [$now->copy()->startOfDay(), $now->copy()->endOfDay()],
            DashboardPeriodEnum::WEEK => [$now->copy()->startOfWeek(), $now->copy()->endOfWeek()],
            DashboardPeriodEnum::MONTH => [$now->copy()->startOfMonth(), $now->copy()->endOfMonth()],
            DashboardPeriodEnum::YEAR => [$now->copy()->startOfYear(), $now->copy()->endOfYear()],
        };
    }

    private function initials(string $name): string
    {
        return Str::of($name)
            ->explode(' ')
            ->filter()
            ->take(2)
            ->map(fn (string $part): string => Str::upper(Str::substr($part, 0, 1)))
            ->implode('');
    }
}
