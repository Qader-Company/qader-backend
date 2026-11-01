<?php

namespace App\Modules\V1\HiringRequests\Presentation\Http\Controllers;

use App\Facades\ApiResponse;
use App\Http\Controllers\Controller;
use App\Modules\V1\HiringRequests\Domain\ValueObjects\HiringContractTypeEnum;
use App\Modules\V1\HiringRequests\Domain\ValueObjects\HiringOfferStatusEnum;
use App\Modules\V1\HiringRequests\Domain\ValueObjects\HiringRequestItemStatusEnum;
use App\Modules\V1\HiringRequests\Domain\ValueObjects\HiringRequestListScopeEnum;
use App\Modules\V1\HiringRequests\Domain\ValueObjects\HiringRequestStatusEnum;
use App\Modules\V1\HiringRequests\Domain\ValueObjects\WorkDayEnum;
use App\Modules\V1\Talents\Domain\ValueObjects\WorkModeEnum;
use App\Modules\V1\Talents\Domain\ValueObjects\WorkTypeEnum;
use App\Support\ApiMetadata\EnumOptions;
use Illuminate\Http\JsonResponse;

class AdminHiringRequestEnumController extends Controller
{
    public function __invoke(): JsonResponse
    {
        return ApiResponse::success([
            'request_statuses' => EnumOptions::make(HiringRequestStatusEnum::class, 'hiring_request_statuses'),
            'item_statuses' => EnumOptions::make(HiringRequestItemStatusEnum::class, 'hiring_request_item_statuses'),
            'offer_statuses' => EnumOptions::make(HiringOfferStatusEnum::class, 'hiring_offer_statuses'),
            'contract_types' => EnumOptions::make(HiringContractTypeEnum::class, 'hiring_contract_types'),
            'scopes' => EnumOptions::make(HiringRequestListScopeEnum::class, 'hiring_request_scopes'),
            'work_types' => EnumOptions::make(WorkTypeEnum::class, 'work_types'),
            'work_modes' => EnumOptions::make(WorkModeEnum::class, 'work_modes'),
            'work_days' => EnumOptions::make(WorkDayEnum::class, 'work_days'),
        ]);
    }
}
