<?php

namespace App\Modules\V1\Authentication\Presentation\Http\Requests;

use App\Modules\V1\Authentication\Presentation\Http\Requests\Registration\PortalRegistrationRequestFactory;
use App\Modules\V1\Users\Domain\ValueObjects\PortalTypeEnum;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class RegisterRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $portal = PortalTypeEnum::tryFrom($this->route('type'));

        return app(PortalRegistrationRequestFactory::class)->for($portal)->rules();
    }
}
