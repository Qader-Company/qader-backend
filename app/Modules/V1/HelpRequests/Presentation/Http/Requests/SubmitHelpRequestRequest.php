<?php

namespace App\Modules\V1\HelpRequests\Presentation\Http\Requests;

use App\Modules\V1\HelpRequests\Domain\ValueObjects\HelpRequestContactMethodEnum;
use App\Modules\V1\HelpRequests\Domain\ValueObjects\HelpRequestTypeEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SubmitHelpRequestRequest extends FormRequest
{
    public function rules(): array
    {
        $isOther = $this->input('help_type') === HelpRequestTypeEnum::OTHER->value;
        $method = $this->input('contact_method');

        return [
            'help_type' => ['required', Rule::enum(HelpRequestTypeEnum::class)],
            'description' => [Rule::requiredIf($isOther), 'nullable', 'string', 'max:2000'],
            'contact_method' => ['required', Rule::enum(HelpRequestContactMethodEnum::class)],
            'contact_email' => [Rule::requiredIf($method === HelpRequestContactMethodEnum::EMAIL->value), 'nullable', 'email:rfc', 'max:255'],
            'contact_phone_country_code' => [Rule::requiredIf($method === HelpRequestContactMethodEnum::WHATSAPP->value), 'nullable', 'string', 'regex:/^\+?[0-9]{1,4}$/'],
            'contact_phone_number' => [Rule::requiredIf($method === HelpRequestContactMethodEnum::WHATSAPP->value), 'nullable', 'string', 'regex:/^[0-9 ]{4,20}$/'],
        ];
    }

    public function messages(): array
    {
        return ['description.required' => __('help_requests.description_required_for_other')];
    }
}
