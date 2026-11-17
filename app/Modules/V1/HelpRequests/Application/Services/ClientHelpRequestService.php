<?php

namespace App\Modules\V1\HelpRequests\Application\Services;

use App\Modules\V1\AccessControl\Application\Services\WorkspaceAccessService;
use App\Modules\V1\AccessControl\Domain\ValueObjects\ClientPermissionEnum;
use App\Modules\V1\HelpRequests\Domain\Models\HelpRequest;
use App\Modules\V1\HelpRequests\Domain\ValueObjects\HelpRequestContactMethodEnum;
use App\Modules\V1\HelpRequests\Domain\ValueObjects\HelpRequestStatusEnum;
use App\Modules\V1\HelpRequests\Domain\ValueObjects\HelpRequestTypeEnum;
use App\Modules\V1\Users\Domain\Models\User;
use App\Modules\V1\Workspaces\Domain\Models\Workspace;
use App\Support\ApiMetadata\EnumOptions;
use Illuminate\Support\Facades\DB;

class ClientHelpRequestService
{
    public function __construct(private readonly WorkspaceAccessService $access) {}

    public function options(User $user, Workspace $workspace): array
    {
        $this->access->authorize($user, $workspace, ClientPermissionEnum::HELP_REQUESTS_CREATE->value);

        return [
            'help_types' => EnumOptions::make(HelpRequestTypeEnum::class, 'help_request_types'),
            'contact_methods' => EnumOptions::make(HelpRequestContactMethodEnum::class, 'help_request_contact_methods'),
            'defaults' => [
                'contact_method' => HelpRequestContactMethodEnum::EMAIL->value,
                'contact_email' => $user->email,
            ],
        ];
    }

    public function submit(User $user, Workspace $workspace, array $data): HelpRequest
    {
        $member = $this->access->authorize($user, $workspace, ClientPermissionEnum::HELP_REQUESTS_CREATE->value);
        $method = HelpRequestContactMethodEnum::from($data['contact_method']);

        return DB::transaction(function () use ($user, $workspace, $member, $data, $method): HelpRequest {
            $request = HelpRequest::query()->create([
                'workspace_id' => $workspace->id,
                'created_by_member_id' => $member->id,
                'help_type' => $data['help_type'],
                'description' => filled($data['description'] ?? null) ? trim($data['description']) : null,
                'contact_method' => $method,
                'contact_email' => $method === HelpRequestContactMethodEnum::EMAIL ? $data['contact_email'] : null,
                'contact_phone_country_code' => $method === HelpRequestContactMethodEnum::WHATSAPP ? $data['contact_phone_country_code'] : null,
                'contact_phone_number' => $method === HelpRequestContactMethodEnum::WHATSAPP ? $data['contact_phone_number'] : null,
                'status' => HelpRequestStatusEnum::NEW,
            ]);
            $request->events()->create([
                'actor_user_id' => $user->id,
                'action' => 'submitted',
                'data' => ['help_type' => $request->help_type->value, 'contact_method' => $method->value],
                'created_at' => now(),
            ]);

            return $request->refresh()->load(['workspace', 'createdByMember.client.user']);
        });
    }
}
