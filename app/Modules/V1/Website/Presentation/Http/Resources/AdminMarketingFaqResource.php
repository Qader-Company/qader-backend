<?php

namespace App\Modules\V1\Website\Presentation\Http\Resources;

use App\Support\Http\Resources\AdminFormInput;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdminMarketingFaqResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'inputs' => [
                AdminFormInput::make('question_ar', $this->translate('ar', false)?->question, 'textarea'),
                AdminFormInput::make('question_en', $this->translate('en', false)?->question, 'textarea'),
                AdminFormInput::make('answer_ar', $this->translate('ar', false)?->answer, 'textarea'),
                AdminFormInput::make('answer_en', $this->translate('en', false)?->answer, 'textarea'),
                AdminFormInput::make('display_order', $this->display_order, 'number'),
                AdminFormInput::make('is_active', $this->is_active, 'checkbox'),
            ],
        ];
    }
}
