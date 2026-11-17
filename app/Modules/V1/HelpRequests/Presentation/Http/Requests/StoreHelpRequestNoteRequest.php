<?php

namespace App\Modules\V1\HelpRequests\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreHelpRequestNoteRequest extends FormRequest
{
    public function rules(): array
    {
        return ['body' => ['required', 'string', 'max:2000']];
    }
}
