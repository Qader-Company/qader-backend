<?php

namespace App\Modules\V1\Cities\Presentation\Http\Requests;

use App\Modules\V1\Cities\Domain\Models\City;
use Illuminate\Database\Query\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        /** @var City $city */
        $city = $this->route('city');
        $countryId = $this->input('country_id', $city->country_id);

        return [
            'country_id' => ['sometimes', 'integer', Rule::exists('countries', 'id')],
            'name' => ['sometimes', 'array:en,ar'],
            'name.en' => [
                'required_with:name',
                'string',
                'max:255',
                Rule::unique('city_translations', 'name')
                    ->ignore($city->id, 'city_id')
                    ->where('locale', 'en')
                    ->where(function (Builder $query) use ($countryId): void {
                        $query->whereIn('city_id', function (Builder $cities) use ($countryId): void {
                            $cities
                                ->select('id')
                                ->from('cities')
                                ->where('country_id', $countryId);
                        });
                    }),
            ],
            'name.ar' => [
                'required_with:name',
                'string',
                'max:255',
                Rule::unique('city_translations', 'name')
                    ->ignore($city->id, 'city_id')
                    ->where('locale', 'ar')
                    ->where(function (Builder $query) use ($countryId): void {
                        $query->whereIn('city_id', function (Builder $cities) use ($countryId): void {
                            $cities
                                ->select('id')
                                ->from('cities')
                                ->where('country_id', $countryId);
                        });
                    }),
            ],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
