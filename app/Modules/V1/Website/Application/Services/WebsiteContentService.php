<?php

namespace App\Modules\V1\Website\Application\Services;

use App\Modules\V1\Website\Domain\Models\MarketingFaq;
use App\Modules\V1\Website\Domain\Models\MarketingPartner;
use App\Modules\V1\Website\Domain\Models\MarketingTestimonial;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class WebsiteContentService
{
    public function createFaq(array $data): MarketingFaq
    {
        return DB::transaction(function () use ($data): MarketingFaq {
            $faq = MarketingFaq::query()->create(Arr::only($data, ['display_order', 'is_active']));
            $this->syncTranslations($faq, $data, ['question', 'answer']);

            return $faq->load('translations');
        });
    }

    public function updateFaq(MarketingFaq $faq, array $data): MarketingFaq
    {
        return DB::transaction(function () use ($faq, $data): MarketingFaq {
            $faq->fill(Arr::only($data, ['display_order', 'is_active']))->save();
            $this->syncTranslations($faq, $data, ['question', 'answer']);

            return $faq->load('translations');
        });
    }

    public function createPartner(array $data): MarketingPartner
    {
        return DB::transaction(function () use ($data): MarketingPartner {
            $partner = MarketingPartner::query()->create(Arr::only($data, [
                'name', 'url', 'display_order', 'is_active',
            ]));
            $this->replaceMedia($partner, $data['logo'] ?? null, 'logo');

            return $partner;
        });
    }

    public function updatePartner(MarketingPartner $partner, array $data): MarketingPartner
    {
        return DB::transaction(function () use ($partner, $data): MarketingPartner {
            $partner->fill(Arr::only($data, ['name', 'url', 'display_order', 'is_active']))->save();
            $this->replaceMedia($partner, $data['logo'] ?? null, 'logo');

            return $partner;
        });
    }

    public function createTestimonial(array $data): MarketingTestimonial
    {
        return DB::transaction(function () use ($data): MarketingTestimonial {
            $testimonial = MarketingTestimonial::query()->create(Arr::only($data, [
                'display_order', 'is_active',
            ]));
            $this->syncTranslations($testimonial, $data, ['name', 'role', 'quote']);
            $this->replaceMedia($testimonial, $data['avatar'] ?? null, 'avatar');

            return $testimonial->load('translations');
        });
    }

    public function updateTestimonial(MarketingTestimonial $testimonial, array $data): MarketingTestimonial
    {
        return DB::transaction(function () use ($testimonial, $data): MarketingTestimonial {
            $testimonial->fill(Arr::only($data, ['display_order', 'is_active']))->save();
            $this->syncTranslations($testimonial, $data, ['name', 'role', 'quote']);
            $this->replaceMedia($testimonial, $data['avatar'] ?? null, 'avatar');

            return $testimonial->load('translations');
        });
    }

    /** @param array<int, string> $attributes */
    private function syncTranslations(Model $model, array $data, array $attributes): void
    {
        foreach (['ar', 'en'] as $locale) {
            $localizedValues = [];
            foreach ($attributes as $attribute) {
                $key = "{$attribute}_{$locale}";
                if (array_key_exists($key, $data)) {
                    $localizedValues[$attribute] = $data[$key];
                }
            }

            if ($localizedValues === []) {
                continue;
            }

            $translation = $model->translateOrNew($locale);
            foreach ($localizedValues as $attribute => $value) {
                $translation->{$attribute} = $value;
            }
        }

        $model->save();
    }

    private function replaceMedia(Model $model, mixed $file, string $collection): void
    {
        if (! $file instanceof UploadedFile) {
            return;
        }

        $model->clearMediaCollection($collection);
        $model->addMedia($file)->toMediaCollection($collection, 'public');
    }
}
