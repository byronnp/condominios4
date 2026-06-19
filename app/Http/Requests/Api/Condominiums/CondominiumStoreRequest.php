<?php

namespace App\Http\Requests\Api\Condominiums;

use App\Models\City;
use App\Models\Province;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class CondominiumStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'unique:condominiums,slug'],
            'ruc' => ['nullable', 'string', 'max:20', 'unique:condominiums,ruc'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'address' => ['required', 'string', 'max:255'],
            'country_code' => ['nullable', 'string', 'size:2', Rule::exists('countries', 'code')->where('is_active', true)],
            'province_id' => ['nullable', 'integer', 'required_with:city_id', Rule::exists('provinces', 'id')->where('is_active', true)],
            'city_id' => ['nullable', 'integer', Rule::exists('cities', 'id')->where('is_active', true)],
            'total_units' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $countryCode = $this->input('country_code', 'EC');
            $provinceId = $this->integer('province_id') ?: null;
            $cityId = $this->integer('city_id') ?: null;

            if ($provinceId !== null) {
                $province = Province::query()->find($provinceId);

                if ($province !== null && $province->country?->code !== $countryCode) {
                    $validator->errors()->add('province_id', 'La provincia no pertenece al país seleccionado.');
                }
            }

            if ($cityId !== null) {
                $city = City::query()->find($cityId);

                if ($city !== null && $city->province_id !== $provinceId) {
                    $validator->errors()->add('city_id', 'La ciudad no pertenece a la provincia seleccionada.');
                }
            }
        });
    }
}
