<?php

namespace App\Modules\V1\JobForms\Application\Services;

use App\Modules\V1\Admins\Domain\Models\Admin;
use App\Modules\V1\JobForms\Application\Mail\TalentHiringInvitationMail;
use App\Modules\V1\JobForms\Domain\Models\JobFormSubmission;
use App\Modules\V1\JobForms\Domain\Models\JobFormVersion;
use App\Modules\V1\JobForms\Domain\Models\JobPosting;
use App\Modules\V1\JobForms\Domain\ValueObjects\JobFormVersionStatusEnum;
use App\Modules\V1\JobForms\Domain\ValueObjects\JobPublishingStatusEnum;
use App\Modules\V1\JobForms\Domain\ValueObjects\JobSubmissionStatusEnum;
use App\Modules\V1\JobTitles\Domain\Models\JobTitle;
use App\Modules\V1\ProfessionalFields\Domain\Models\ProfessionalField;
use App\Modules\V1\Talents\Application\UseCases\CreateTalentAccountUseCase;
use App\Modules\V1\Users\Domain\Models\User;
use App\Modules\V1\Users\Domain\ValueObjects\PortalTypeEnum;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

class JobFormWorkflow
{
    public function save(array $data, Admin $actor, ?JobPosting $posting = null): JobPosting
    {
        return DB::transaction(function () use ($data, $actor, $posting): JobPosting {
            $posting = $posting?->newQuery()->lockForUpdate()->findOrFail($posting->id);
            $title = JobTitle::query()->findOrFail($data['job_title_id'])->name;
            $isNew = $posting === null;
            $posting ??= new JobPosting(['created_by_admin_id' => $actor->id, 'slug' => $this->uniqueSlug($title)]);
            $wasPublished = ! $isNew && $posting->status === JobPublishingStatusEnum::PUBLISHED;
            $details = $this->detailsFromData($data, $title);

            if (! $wasPublished) {
                $this->applyDetailsToPosting($posting, $details);
            }

            $draft = $posting->versions()->where('status', JobFormVersionStatusEnum::DRAFT->value)->latest('version_number')->first();
            if (! $draft) {
                $draft = $posting->versions()->create(['version_number' => ((int) $posting->versions()->max('version_number')) + 1, 'status' => JobFormVersionStatusEnum::DRAFT]);
            }
            $draft->update([
                'job_details_snapshot' => $details,
                'identity_fields_snapshot' => ['applicant_name', 'email', 'phone_country_code', 'phone_number', 'cv'],
            ]);
            $draft->fields()->sync($data['professional_form_field_ids'] ?? []);
            if ($data['publishing_status'] === JobPublishingStatusEnum::PUBLISHED->value) {
                $this->publishLocked($posting, $draft);
            } elseif ($isNew) {
                $posting->forceFill(['status' => JobPublishingStatusEnum::DRAFT, 'is_active' => false])->save();
            }

            return $posting->fresh($this->relations());
        });
    }

    public function publish(JobPosting $posting): JobPosting
    {
        return DB::transaction(function () use ($posting): JobPosting {
            $posting = JobPosting::query()->lockForUpdate()->findOrFail($posting->id);
            $draft = $posting->versions()->where('status', JobFormVersionStatusEnum::DRAFT->value)->latest('version_number')->first();
            throw_if(! $draft, ConflictHttpException::class, __('job_forms.no_draft'));
            $this->publishLocked($posting, $draft);

            return $posting->fresh($this->relations());
        });
    }

    public function setActivation(JobPosting $posting, bool $active): JobPosting
    {
        throw_if($active && $posting->status !== JobPublishingStatusEnum::PUBLISHED, ConflictHttpException::class, __('job_forms.publish_before_activation'));
        $posting->update(['is_active' => $active]);

        return $posting->fresh($this->relations());
    }

    public function submit(JobPosting $posting, array $data): JobFormSubmission
    {
        return DB::transaction(function () use ($posting, $data): JobFormSubmission {
            $existing = JobFormSubmission::withTrashed()->with('version')->where('idempotency_key', $data['idempotency_key'])->first();
            if ($existing) {
                throw_if(
                    $existing->version->job_posting_id !== $posting->id || $existing->email !== Str::lower($data['email']),
                    ConflictHttpException::class,
                    __('job_forms.idempotency_conflict'),
                );

                return $existing;
            }
            $version = $posting->versions()->where('status', JobFormVersionStatusEnum::PUBLISHED->value)->with('fields')->latest('version_number')->lockForUpdate()->firstOrFail();
            $submission = $version->submissions()->create(['applicant_name' => $data['applicant_name'], 'email' => Str::lower($data['email']), 'phone_country_code' => $data['phone_country_code'], 'phone_number' => $data['phone_number'], 'idempotency_key' => $data['idempotency_key'], 'consent_at' => now(), 'submitted_at' => now()]);
            foreach ($version->fields as $field) {
                $key = $field->key->value;
                $submission->answers()->create(['professional_form_field_id' => $field->id, 'value' => ['answer' => data_get($data, 'answers.'.$key)]]);
            }
            $submission->addMedia($data['cv'])->toMediaCollection('cv', 'local');

            return $submission->load(['version.posting', 'answers.field', 'media']);
        });
    }

    public function startHiring(JobFormSubmission $submission, Admin $actor, CreateTalentAccountUseCase $createTalent): JobFormSubmission
    {
        [$submission, $password, $existing, $sendMail] = DB::transaction(function () use ($submission, $actor, $createTalent): array {
            $submission = JobFormSubmission::query()->lockForUpdate()->findOrFail($submission->id);
            if ($submission->status === JobSubmissionStatusEnum::HIRING_STARTED) {
                return [$submission, null, true, false];
            }
            $version = $submission->version()->with('posting')->firstOrFail();
            $professionalFieldId = (int) data_get($version->job_details_snapshot, 'professional_field.id');
            $jobTitleId = (int) data_get($version->job_details_snapshot, 'job_title.id');
            $user = User::query()->where('email', $submission->email)->where('type', PortalTypeEnum::TALENT)->first();
            $password = null;
            $existing = $user !== null;
            if (! $user) {
                $parts = preg_split('/\s+/', trim($submission->applicant_name), 2);
                $password = Str::password(12);
                $user = $createTalent->execute(['first_name' => $parts[0], 'last_name' => $parts[1] ?? '-', 'email' => $submission->email, 'password' => $password]);
                $user->forceFill(['must_change_password' => true])->save();
            } else {
                $user = $createTalent->ensureProfile($user);
            }
            $user->talent()->update(['phone_country_code' => $submission->phone_country_code, 'phone_number' => $submission->phone_number, 'professional_field_id' => $professionalFieldId, 'job_title_id' => $jobTitleId]);
            $submission->update(['status' => JobSubmissionStatusEnum::HIRING_STARTED, 'converted_talent_id' => $user->talent->id, 'converted_by_admin_id' => $actor->id, 'converted_at' => now()]);

            return [$submission->fresh(['version.posting', 'convertedTalent.user']), $password, $existing, true];
        });
        if ($sendMail) {
            Mail::to($submission->email)->queue(new TalentHiringInvitationMail($submission->applicant_name, $submission->email, $password, $existing));
        }

        return $submission;
    }

    private function publishLocked(JobPosting $posting, JobFormVersion $draft): void
    {
        $this->applyDetailsToPosting($posting, $draft->job_details_snapshot);
        $posting->versions()->where('status', JobFormVersionStatusEnum::PUBLISHED->value)->update(['status' => JobFormVersionStatusEnum::RETIRED->value]);
        $draft->update(['status' => JobFormVersionStatusEnum::PUBLISHED, 'published_at' => now()]);
        $posting->update(['status' => JobPublishingStatusEnum::PUBLISHED, 'is_active' => true, 'published_at' => now()]);
    }

    private function detailsFromData(array $data, string $title): array
    {
        $field = ProfessionalField::query()->with('translations')->findOrFail($data['professional_field_id']);

        return ['title' => $title, 'summary' => $data['summary'], 'about_role' => $data['about_role'], 'work_type' => $data['work_type'], 'work_mode' => $data['work_mode'], 'professional_field' => ['id' => $field->id, 'name' => $field->name], 'job_title' => ['id' => $data['job_title_id'], 'name' => $title], 'sections' => collect($data['sections'])->mapWithKeys(fn (array $section) => [$section['section'] => array_values($section['items'])])->all()];
    }

    private function applyDetailsToPosting(JobPosting $posting, array $details): void
    {
        $posting->fill([
            'professional_field_id' => data_get($details, 'professional_field.id'),
            'job_title_id' => data_get($details, 'job_title.id'),
            'title' => $details['title'],
            'summary' => $details['summary'],
            'about_role' => $details['about_role'],
            'work_type' => $details['work_type'],
            'work_mode' => $details['work_mode'],
        ])->save();

        $posting->sections()->delete();
        foreach ($details['sections'] as $section => $items) {
            foreach ($items as $order => $content) {
                $posting->sections()->create(['section' => $section, 'content' => $content, 'display_order' => $order]);
            }
        }
    }

    private function uniqueSlug(string $title): string
    {
        return (Str::slug($title) ?: 'job').'-'.Str::lower(Str::random(8));
    }

    private function relations(): array
    {
        return ['professionalField.translations', 'jobTitle', 'sections', 'versions.fields'];
    }
}
