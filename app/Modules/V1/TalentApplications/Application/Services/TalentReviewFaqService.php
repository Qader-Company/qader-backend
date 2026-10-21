<?php

namespace App\Modules\V1\TalentApplications\Application\Services;

use App\Modules\V1\TalentApplications\Domain\Models\TalentReviewFaq;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class TalentReviewFaqService
{
    /** @return Collection<int, TalentReviewFaq> */
    public function activeForScreen(string $screenState): Collection
    {
        return TalentReviewFaq::query()
            ->with('translations')
            ->where('is_active', true)
            ->whereJsonContains('screen_states', $screenState)
            ->orderBy('display_order')
            ->orderBy('id')
            ->get();
    }

    /** @return Collection<int, TalentReviewFaq> */
    public function all(): Collection
    {
        return TalentReviewFaq::query()
            ->with('translations')
            ->orderBy('display_order')
            ->orderBy('id')
            ->get();
    }

    public function create(array $data): TalentReviewFaq
    {
        return DB::transaction(function () use ($data): TalentReviewFaq {
            $faq = TalentReviewFaq::query()->create(Arr::only($data, [
                'screen_states', 'display_order', 'is_active',
            ]));
            $this->saveTranslations($faq, $data);

            return $faq->load('translations');
        });
    }

    public function update(TalentReviewFaq $faq, array $data): TalentReviewFaq
    {
        return DB::transaction(function () use ($faq, $data): TalentReviewFaq {
            $faq->update(Arr::only($data, [
                'screen_states', 'display_order', 'is_active',
            ]));
            $this->saveTranslations($faq, $data);

            return $faq->refresh()->load('translations');
        });
    }

    private function saveTranslations(TalentReviewFaq $faq, array $data): void
    {
        foreach (['en', 'ar'] as $locale) {
            if (! isset($data['question'][$locale]) && ! isset($data['answer'][$locale])) {
                continue;
            }

            $translation = $faq->translateOrNew($locale);
            if (isset($data['question'][$locale])) {
                $translation->question = $data['question'][$locale];
            }
            if (isset($data['answer'][$locale])) {
                $translation->answer = $data['answer'][$locale];
            }
        }

        $faq->save();
    }
}
