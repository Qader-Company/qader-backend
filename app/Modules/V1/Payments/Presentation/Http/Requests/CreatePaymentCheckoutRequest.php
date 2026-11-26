<?php

namespace App\Modules\V1\Payments\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreatePaymentCheckoutRequest extends FormRequest
{
    public function rules(): array
    {
        return [];
    }

    public function idempotencyKey(): string
    {
        return validator(
            ['idempotency_key' => $this->header('Idempotency-Key')],
            ['idempotency_key' => ['required', 'uuid']],
        )->validate()['idempotency_key'];
    }
}
