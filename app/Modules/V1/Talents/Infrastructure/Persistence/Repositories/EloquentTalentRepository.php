<?php

namespace App\Modules\V1\Talents\Infrastructure\Persistence\Repositories;

use App\Modules\V1\Cities\Domain\Models\City;
use App\Modules\V1\Countries\Domain\Models\Country;
use App\Modules\V1\Currencies\Domain\Models\Currency;
use App\Modules\V1\JobTitles\Domain\Models\JobTitle;
use App\Modules\V1\Languages\Domain\Models\Language;
use App\Modules\V1\ProfessionalFields\Domain\Models\ProfessionalField;
use App\Modules\V1\Skills\Domain\Models\Skill;
use App\Modules\V1\Talents\Domain\Models\Talent;
use App\Modules\V1\Talents\Domain\Models\TalentExperience;
use App\Modules\V1\Talents\Domain\Models\TalentProject;
use App\Modules\V1\Talents\Domain\Models\TalentWorkMode;
use App\Modules\V1\Talents\Domain\Models\TalentWorkType;
use App\Modules\V1\Talents\Domain\Repositories\TalentRepositoryInterface;
use App\Modules\V1\Tools\Domain\Models\Tool;
use App\Modules\V1\Users\Domain\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class EloquentTalentRepository implements TalentRepositoryInterface
{
    public function findProfileByUserId(int $userId)
    {
        $locale = app()->getLocale();

        $row = DB::table('talents as talent')
            ->join('users as user', 'user.id', '=', 'talent.user_id')
            ->leftJoin('countries as nationality', 'nationality.id', '=', 'talent.nationality_country_id')
            ->leftJoin(
                'country_translations as nationality_translation',
                function (JoinClause $join) use ($locale): void {
                    $join
                        ->on('nationality_translation.country_id', '=', 'nationality.id')
                        ->where('nationality_translation.locale', $locale);
                },
            )
            ->leftJoin('countries as residence_country', 'residence_country.id', '=', 'talent.residence_country_id')
            ->leftJoin(
                'country_translations as residence_country_translation',
                function (JoinClause $join) use ($locale): void {
                    $join
                        ->on('residence_country_translation.country_id', '=', 'residence_country.id')
                        ->where('residence_country_translation.locale', $locale);
                },
            )
            ->leftJoin('cities as residence_city', 'residence_city.id', '=', 'talent.residence_city_id')
            ->leftJoin(
                'city_translations as residence_city_translation',
                function (JoinClause $join) use ($locale): void {
                    $join
                        ->on('residence_city_translation.city_id', '=', 'residence_city.id')
                        ->where('residence_city_translation.locale', $locale);
                },
            )
            ->leftJoin(
                'professional_fields as professional_field',
                'professional_field.id',
                '=',
                'talent.professional_field_id',
            )
            ->leftJoin(
                'professional_field_translations as professional_field_translation',
                function (JoinClause $join) use ($locale): void {
                    $join
                        ->on(
                            'professional_field_translation.professional_field_id',
                            '=',
                            'professional_field.id',
                        )
                        ->where('professional_field_translation.locale', $locale);
                },
            )
            ->leftJoin('job_titles as job_title', 'job_title.id', '=', 'talent.job_title_id')
            ->leftJoin('currencies as current_currency', 'current_currency.id', '=', 'talent.current_currency_id')
            ->leftJoin(
                'currency_translations as current_currency_translation',
                function (JoinClause $join) use ($locale): void {
                    $join
                        ->on('current_currency_translation.currency_id', '=', 'current_currency.id')
                        ->where('current_currency_translation.locale', $locale);
                },
            )
            ->leftJoin('currencies as expected_currency', 'expected_currency.id', '=', 'talent.expected_currency_id')
            ->leftJoin(
                'currency_translations as expected_currency_translation',
                function (JoinClause $join) use ($locale): void {
                    $join
                        ->on('expected_currency_translation.currency_id', '=', 'expected_currency.id')
                        ->where('expected_currency_translation.locale', $locale);
                },
            )
            ->where('talent.user_id', $userId)
            ->select([
                'talent.*',
                'user.id as user_id_value',
                'user.first_name as user_first_name',
                'user.last_name as user_last_name',
                'user.email as user_email',
                'nationality.id as nationality_id_value',
                'nationality_translation.name as nationality_localized_name',
                'nationality.code as nationality_code',
                'residence_country.id as residence_country_id_value',
                'residence_country_translation.name as residence_country_localized_name',
                'residence_country.code as residence_country_code',
                'residence_city.id as residence_city_id_value',
                'residence_city_translation.name as residence_city_localized_name',
                'professional_field.id as professional_field_id_value',
                'professional_field_translation.name as professional_field_localized_name',
                'job_title.id as job_title_id_value',
                'job_title.name as job_title_name',
                'current_currency.id as current_currency_id_value',
                'current_currency_translation.name as current_currency_localized_name',
                'current_currency.code as current_currency_code',
                'expected_currency.id as expected_currency_id_value',
                'expected_currency_translation.name as expected_currency_localized_name',
                'expected_currency.code as expected_currency_code',
            ])
            ->first();

        if ($row === null) {
            return null;
        }

        $talent = $this->model(Talent::class, $row);
        $talent->setRelation('user', $this->relatedModel(User::class, $row, 'user'));
        $talent->setRelation('nationalityCountry', $this->relatedModel(Country::class, $row, 'nationality'));
        $talent->setRelation('residenceCountry', $this->relatedModel(Country::class, $row, 'residence_country'));
        $talent->setRelation('residenceCity', $this->relatedModel(City::class, $row, 'residence_city'));
        $talent->setRelation(
            'professionalField',
            $this->relatedModel(
                ProfessionalField::class,
                $row,
                'professional_field',
            ),
        );
        $talent->setRelation('jobTitle', $this->relatedModel(JobTitle::class, $row, 'job_title'));
        $talent->setRelation('currentCurrency', $this->relatedModel(Currency::class, $row, 'current_currency'));
        $talent->setRelation('expectedCurrency', $this->relatedModel(Currency::class, $row, 'expected_currency'));
        $talent->setRelation('languages', $this->languages($talent->id));
        $talent->setRelation('skills', $this->skills($talent->id));
        $talent->setRelation('tools', $this->tools($talent->id));
        $talent->setRelation(
            'experiences',
            $this->children(
                TalentExperience::class,
                'talent_experiences',
                $talent->id,
                'display_order',
            ),
        );
        $projects = $this->children(
            TalentProject::class,
            'talent_projects',
            $talent->id,
            'display_order',
        );

        $projectMedia = $this->mediaFor($projects, 'images');

        $projects->each(function (TalentProject $project) use ($projectMedia): void {
            $project->setRelation(
                'media',
                $projectMedia->get($project->id, collect()),
            );
        });

        $talent->setRelation('projects', $projects);
        $talent->setRelation('media', $this->media($talent, 'cv'));
        $talent->setRelation(
            'workTypes',
            $this->children(
                TalentWorkType::class,
                'talent_work_types',
                $talent->id,
                'work_type',
            ),
        );
        $talent->setRelation(
            'workModes',
            $this->children(
                TalentWorkMode::class,
                'talent_work_modes',
                $talent->id,
                'work_mode',
            ),
        );

        return $talent;
    }

    public function update(Talent $talent, array $attributes): Talent
    {
        $talent->update($attributes);

        $profile = $this->findProfileByUserId($talent->user_id);

        return $profile
            ?? throw new \LogicException('Talent profile was not found after update.');
    }

    private function languages(int $talentId): Collection
    {
        $locale = app()->getLocale();

        return DB::table('talent_languages')
            ->join('languages', 'languages.id', '=', 'talent_languages.language_id')
            ->leftJoin(
                'language_translations',
                function (JoinClause $join) use ($locale): void {
                    $join
                        ->on('language_translations.language_id', '=', 'languages.id')
                        ->where('language_translations.locale', $locale);
                },
            )
            ->where('talent_languages.talent_id', $talentId)
            ->select([
                'languages.*',
                'language_translations.name as localized_name',
                'talent_languages.proficiency_level as pivot_proficiency_level',
            ])
            ->orderBy('language_translations.name')
            ->get()
            ->map(function (object $row): Language {
                $language = $this->model(Language::class, $row);
                $language->setRelation('pivot', $this->pivot([
                    'proficiency_level' => $row->pivot_proficiency_level,
                ]));

                return $language;
            });
    }

    private function skills(int $talentId): Collection
    {
        return DB::table('talent_skills')
            ->join('skills', 'skills.id', '=', 'talent_skills.skill_id')
            ->where('talent_skills.talent_id', $talentId)
            ->select('skills.*')
            ->orderBy('skills.name')
            ->get()
            ->map(fn (object $row): Skill => $this->model(Skill::class, $row));
    }

    private function tools(int $talentId): Collection
    {
        return DB::table('talent_tools')
            ->join('tools', 'tools.id', '=', 'talent_tools.tool_id')
            ->where('talent_tools.talent_id', $talentId)
            ->select('tools.*')
            ->orderBy('tools.name')
            ->get()
            ->map(fn (object $row): Tool => $this->model(Tool::class, $row));
    }

    private function children(
        string $modelClass,
        string $table,
        int $talentId,
        string $orderBy,
    ): Collection {
        return DB::table($table)
            ->where('talent_id', $talentId)
            ->orderBy($orderBy)
            ->get()
            ->map(fn (object $row): Model => $this->model($modelClass, $row));
    }

    private function relatedModel(string $modelClass, object $row, string $prefix): ?Model
    {
        $id = "{$prefix}_id_value";
        if ($row->{$id} === null) {
            return null;
        }

        $attributes = ['id' => $row->{$id}];
        foreach (['localized_name', 'name', 'code', 'first_name', 'last_name', 'email'] as $column) {
            $property = "{$prefix}_{$column}";
            if (property_exists($row, $property)) {
                $attributes[$column] = $row->{$property};
            }
        }

        return $this->model($modelClass, (object) $attributes);
    }

    private function media(Model $model, string $collection): Collection
    {
        return DB::table('media')
            ->where('model_type', $model->getMorphClass())
            ->where('model_id', $model->getKey())
            ->where('collection_name', $collection)
            ->orderBy('order_column')
            ->orderBy('id')
            ->get()
            ->map(fn (object $row): Media => $this->model(Media::class, $row));
    }

    private function mediaFor(Collection $models, string $collection): Collection
    {
        if ($models->isEmpty()) {
            return collect();
        }

        $model = $models->first();

        return DB::table('media')
            ->where('model_type', $model->getMorphClass())
            ->whereIn('model_id', $models->pluck('id'))
            ->where('collection_name', $collection)
            ->orderBy('order_column')
            ->orderBy('id')
            ->get()
            ->map(fn (object $row): Media => $this->model(Media::class, $row))
            ->groupBy('model_id');
    }

    private function model(string $modelClass, object $attributes): Model
    {
        $model = new $modelClass;
        $model->setRawAttributes((array) $attributes, true);
        $model->exists = true;

        return $model;
    }

    private function pivot(array $attributes): Pivot
    {
        $pivot = new Pivot;
        $pivot->setRawAttributes($attributes, true);
        $pivot->exists = true;

        return $pivot;
    }
}
