<?php

namespace App\Modules\V1\HelpRequests\Presentation\Http\Requests;

use App\Modules\V1\HelpRequests\Domain\ValueObjects\HelpRequestClosureOutcomeEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CloseHelpRequestRequest extends FormRequest
{
    public function rules(): array
    {
        $createdHiringRequest = $this->input('outcome') === HelpRequestClosureOutcomeEnum::NEW_HIRING_REQUEST_CREATED->value;

        return [
            'outcome' => ['required', Rule::enum(HelpRequestClosureOutcomeEnum::class)],
            'hiring_request_id' => [Rule::requiredIf($createdHiringRequest), 'nullable', 'integer', 'exists:hiring_requests,id'],
            // Additional Details is optional for a new hiring request; Resolution Details / Description are required otherwise.
            'details' => [Rule::requiredIf(! $createdHiringRequest), 'nullable', 'string', 'max:2000'],
        ];
    }
}
