<?php

namespace App\Modules\V1\JobForms\Presentation\Http\Requests;

use App\Modules\V1\JobForms\Application\Factories\JobFormValidationFactory;
use App\Modules\V1\ProfessionalFields\Domain\ValueObjects\ProfessionalFormFieldEnum;
use Illuminate\Foundation\Http\FormRequest;

class StorePublicJobSubmissionRequest extends FormRequest
{
    public function rules(): array
    {
        return ['applicant_name' => ['required', 'string', 'max:255'], 'email' => ['required', 'email:rfc', 'max:255'], 'phone_country_code' => ['required', 'string', 'max:8'], 'phone_number' => ['required', 'string', 'max:24'], 'cv' => ['required', 'file', 'mimes:pdf,doc,docx', 'max:10240'], 'consent' => ['accepted'], 'idempotency_key' => ['required', 'uuid'], 'answers' => ['sometimes', 'array']];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            $version = $this->route('job')?->versions()->where('status', 'published')->with(['fields', 'posting'])->latest('version_number')->first();
            if (! $version) {
                return;
            }
            $factory = app(JobFormValidationFactory::class);
            $rules = [];
            foreach ($version->fields as $field) {
                $enum = $field->key instanceof ProfessionalFormFieldEnum ? $field->key : ProfessionalFormFieldEnum::from($field->key);
                $rules += $factory->rulesFor($enum, (int) data_get($version->job_details_snapshot, 'professional_field.id'));
            }
            $nested = validator($this->all(), $rules);
            foreach ($nested->errors()->messages() as $key => $messages) {
                foreach ($messages as $message) {
                    $validator->errors()->add($key, $message);
                }
            }
        });
    }
}
