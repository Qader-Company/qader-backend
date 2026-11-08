<?php

namespace App\Modules\V1\TalentApplications\Presentation\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TalentReviewFaqResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $isAdmin = $request->is('api/v1/admin/*');

        return [
            'id' => $this->id,
            'screen_states' => $this->screen_states,
            'question' => $isAdmin ? [
                'en' => $this->translate('en', false)?->question,
                'ar' => $this->translate('ar', false)?->question,
            ] : $this->question,
            'answer' => $isAdmin ? [
                'en' => $this->translate('en', false)?->answer,
                'ar' => $this->translate('ar', false)?->answer,
            ] : $this->answer,
            'display_order' => $this->display_order,
            'is_active' => $this->is_active,
        ];
    }
}
