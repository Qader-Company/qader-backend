<?php

namespace App\Modules\V1\Cities\Presentation\Http\Requests;

use Illuminate\Database\Query\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'country_id' => ['required', 'integer', Rule::exists('countries', 'id')],
            'name' => ['required', 'array:en,ar'],
            'name.en' => [
                'required',
                'string',
                'max:255',
                Rule::unique('city_translations', 'name')
                    ->where('locale', 'en')
                    ->where(function (Builder $query): void {
                        $query->whereIn('city_id', function (Builder $cities): void {
                            $cities
                                ->select('id')
                                ->from('cities')
                                ->where('country_id', $this->input('country_id'));
                        });
                    }),
            ],
            'name.ar' => [
                'required',
                'string',
                'max:255',
                Rule::unique('city_translations', 'name')
                    ->where('locale', 'ar')
                    ->where(function (Builder $query): void {
                        $query->whereIn('city_id', function (Builder $cities): void {
                            $cities
                                ->select('id')
                                ->from('cities')
                                ->where('country_id', $this->input('country_id'));
                        });
                    }),
            ],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
