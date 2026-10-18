<?php

namespace App\Modules\V1\TalentApplications\Presentation\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TalentApplicationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'status' => $this->status,
            'submitted_at' => $this->submitted_at,
            'profile_completion_at_submission' => $this->profile_completion_at_submission,
            'snapshot_version' => $this->snapshot_version,
            'snapshot_captured_at' => $this->snapshot_captured_at,
            'submitted_cv' => $this->when(
                $this->getFirstMedia('submitted_cv') !== null,
                fn () => [
                    'id' => $this->getFirstMedia('submitted_cv')->id,
                    'file_name' => $this->getFirstMedia('submitted_cv')->file_name,
                    'download_url' => route('talent.profile.media.download', [
                        'media' => $this->getFirstMedia('submitted_cv')->id,
                    ]),
                ],
            ),
        ];
    }
}
