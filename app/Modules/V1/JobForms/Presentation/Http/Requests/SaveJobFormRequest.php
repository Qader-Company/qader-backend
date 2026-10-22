<?php

namespace App\Modules\V1\JobForms\Presentation\Http\Requests;

use App\Modules\V1\JobForms\Domain\ValueObjects\JobPublishingStatusEnum;
use App\Modules\V1\JobForms\Domain\ValueObjects\JobSectionEnum;
use App\Modules\V1\JobForms\Domain\ValueObjects\JobWorkModeEnum;
use App\Modules\V1\JobForms\Domain\ValueObjects\JobWorkTypeEnum;
use App\Modules\V1\ProfessionalFields\Domain\ValueObjects\ProfessionalFormFieldEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveJobFormRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'professional_field_id' => ['required', 'integer', Rule::exists('professional_fields', 'id')->where('is_active', true)],
            'job_title_id' => ['required', 'integer', Rule::exists('job_titles', 'id')->where('is_active', true)],
            'summary' => ['required', 'string', 'max:5000'],
            'about_role' => ['required', 'string', 'max:10000'],
            'work_type' => ['required', Rule::enum(JobWorkTypeEnum::class)],
            'work_mode' => ['required', Rule::enum(JobWorkModeEnum::class)],
            'publishing_status' => ['required', Rule::enum(JobPublishingStatusEnum::class)],
            'sections' => ['required', 'array', 'min:3'],
            'sections.*.section' => ['required', 'distinct', Rule::enum(JobSectionEnum::class)],
            'sections.*.items' => ['required', 'array', 'min:1'],
            'sections.*.items.*' => ['required', 'string', 'max:5000'],
            'professional_form_field_ids' => ['sometimes', 'array'],
            'professional_form_field_ids.*' => ['integer', 'distinct', 'exists:professional_form_fields,id'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            $fieldId = $this->integer('professional_field_id');
            $titleId = $this->integer('job_title_id');
            if ($fieldId && $titleId && ! \DB::table('job_title_professional_field')->where('professional_field_id', $fieldId)->where('job_title_id', $titleId)->exists()) {
                $validator->errors()->add('job_title_id', 'The selected job title does not belong to the professional field.');
            }
            $selected = $this->input('professional_form_field_ids', []);
            $allowed = \DB::table('professional_field_form_field')->where('professional_field_id', $fieldId)->whereIn('professional_form_field_id', $selected)->count();
            if ($allowed !== count(array_unique($selected))) {
                $validator->errors()->add('professional_form_field_ids', 'One or more fields are not enabled for the selected professional field.');
            }
            $fixedKeys = [ProfessionalFormFieldEnum::FULL_NAME->value, ProfessionalFormFieldEnum::EMAIL->value, ProfessionalFormFieldEnum::PHONE_NUMBER->value, ProfessionalFormFieldEnum::CV->value, ProfessionalFormFieldEnum::PROFESSIONAL_FIELD->value, ProfessionalFormFieldEnum::JOB_TITLE->value];
            if (\DB::table('professional_form_fields')->whereIn('id', $selected)->whereIn('key', $fixedKeys)->exists()) {
                $validator->errors()->add('professional_form_field_ids', 'Fixed identity and job fields cannot be selected as additional questions.');
            }
        });
    }
}
