<?php

namespace App\Modules\V1\Talents\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ParseTalentResumeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'cv' => ['required', 'file', 'mimes:pdf,doc,docx', 'max:10240'],
        ];
    }

    public function bodyParameters(): array
    {
        return [
            'cv' => [
                'description' => 'Temporary PDF, DOC, or DOCX resume to parse. The file is not stored by this endpoint.',
                'type' => 'file',
            ],
        ];
    }
}
