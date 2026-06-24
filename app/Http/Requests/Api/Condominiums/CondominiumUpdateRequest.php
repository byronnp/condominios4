<?php

namespace App\Http\Requests\Api\Condominiums;

use App\Models\CatalogItem;
use App\Models\City;
use App\Models\Condominium;
use App\Models\Province;
use App\Rules\ValidCatalogItem;
use App\Rules\ValidCatalogItemLabel;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class CondominiumUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'slug' => ['sometimes', 'nullable', 'string', 'max:255', Rule::unique('condominiums', 'slug')->ignore($this->condominium()->id)],
            'ruc' => ['sometimes', 'nullable', 'string', 'max:20', Rule::unique('condominiums', 'ruc')->ignore($this->condominium()->id)],
            'type' => ['sometimes', 'nullable', 'string', 'max:255', new ValidCatalogItemLabel('condominium_types')],
            'description' => ['sometimes', 'nullable', 'string'],
            'status' => ['sometimes', 'nullable', 'string', Rule::in(['Activo', 'Inactivo', 'activo', 'inactivo'])],
            'email' => ['sometimes', 'nullable', 'email', 'max:255'],
            'phone' => ['sometimes', 'nullable', 'string', 'max:50'],
            'address' => ['sometimes', 'nullable', 'required_without:direction', 'string', 'max:255'],
            'direction' => ['sometimes', 'nullable', 'required_without:address', 'string', 'max:255'],
            'reference' => ['sometimes', 'nullable', 'string', 'max:255'],
            'country_code' => ['sometimes', 'nullable', 'string', 'size:2', Rule::exists('countries', 'code')->where('is_active', true)],
            'province_id' => ['sometimes', 'nullable', 'integer', Rule::exists('provinces', 'id')->where('is_active', true)],
            'city_id' => ['sometimes', 'nullable', 'integer', Rule::exists('cities', 'id')->where('is_active', true)],
            'latitude' => ['sometimes', 'nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['sometimes', 'nullable', 'numeric', 'between:-180,180'],
            'currency' => ['sometimes', 'nullable', 'string', 'size:3'],
            'towers' => ['sometimes', 'nullable', 'integer', 'min:0'],
            'houses' => ['sometimes', 'nullable', 'integer', 'min:0'],
            'characteristics' => ['sometimes', 'array'],
            'characteristics.*' => ['integer', new ValidCatalogItem('condominium_features')],
            'admin_name' => ['sometimes', 'nullable', 'required_with:admin_last_name,admin_document_type,admin_id_number,admin_email,admin_phone,admin_status', 'string', 'max:255'],
            'admin_last_name' => ['sometimes', 'nullable', 'required_with:admin_name,admin_document_type,admin_id_number,admin_email,admin_phone,admin_status', 'string', 'max:255'],
            'admin_document_type' => ['sometimes', 'nullable', 'required_with:admin_name,admin_last_name,admin_id_number,admin_email,admin_phone,admin_status', 'string', 'max:255', new ValidCatalogItemLabel('document_types')],
            'admin_id_number' => ['sometimes', 'nullable', 'required_with:admin_name,admin_last_name,admin_document_type,admin_email,admin_phone,admin_status', 'string', 'max:30'],
            'admin_email' => ['sometimes', 'nullable', 'required_with:admin_name,admin_last_name,admin_document_type,admin_id_number,admin_phone,admin_status', 'email', 'max:255'],
            'admin_phone' => ['sometimes', 'nullable', 'string', 'max:50'],
            'admin_status' => ['sometimes', 'nullable', 'required_with:admin_name,admin_last_name,admin_document_type,admin_id_number,admin_email,admin_phone', 'string', Rule::in(['Activo', 'Inactivo', 'activo', 'inactivo'])],
            'logo' => ['sometimes', 'nullable', 'image', 'max:5120'],
            'total_units' => ['sometimes', 'nullable', 'integer', 'min:0'],
            'is_active' => ['sometimes', 'nullable', 'boolean'],
        ];
    }

    public function condominiumData(): array
    {
        $data = $this->validated();
        $condominium = $this->condominium();

        $address = array_key_exists('address', $data)
            ? $data['address']
            : (array_key_exists('direction', $data) ? $data['direction'] : $condominium->address);

        $condominiumData = [
            'name' => $data['name'] ?? $condominium->name,
            'slug' => array_key_exists('slug', $data) ? $data['slug'] : $condominium->slug,
            'ruc' => array_key_exists('ruc', $data) ? $data['ruc'] : $condominium->ruc,
            'condominium_type_id' => array_key_exists('type', $data)
                ? $this->catalogItemId('condominium_types', $data['type'])
                : $condominium->condominium_type_id,
            'description' => array_key_exists('description', $data) ? $data['description'] : $condominium->description,
            'email' => array_key_exists('email', $data) ? $data['email'] : $condominium->email,
            'phone' => array_key_exists('phone', $data) ? $data['phone'] : $condominium->phone,
            'address' => $address,
            'address_reference' => array_key_exists('reference', $data) ? $data['reference'] : $condominium->address_reference,
            'country_code' => array_key_exists('country_code', $data) ? ($data['country_code'] ?? $condominium->country_code) : $condominium->country_code,
            'province_id' => array_key_exists('province_id', $data) ? $data['province_id'] : $condominium->province_id,
            'city_id' => array_key_exists('city_id', $data) ? $data['city_id'] : $condominium->city_id,
            'latitude' => array_key_exists('latitude', $data) ? $data['latitude'] : $condominium->latitude,
            'longitude' => array_key_exists('longitude', $data) ? $data['longitude'] : $condominium->longitude,
            'towers_count' => array_key_exists('towers', $data) ? $data['towers'] : $condominium->towers_count,
            'houses_count' => array_key_exists('houses', $data) ? $data['houses'] : $condominium->houses_count,
            'total_units' => array_key_exists('total_units', $data) ? $data['total_units'] : $condominium->total_units,
            'is_active' => array_key_exists('is_active', $data)
                ? $this->statusToBoolean($data['is_active'], $condominium->is_active)
                : $condominium->is_active,
        ];

        return array_filter($condominiumData, fn (mixed $value): bool => $value !== null);
    }

    public function featureIds(): ?array
    {
        if (! array_key_exists('characteristics', $this->validated())) {
            return null;
        }

        return collect($this->validated('characteristics', []))
            ->map(fn (int|string $featureId): int => (int) $featureId)
            ->values()
            ->all();
    }

    public function administratorData(): ?array
    {
        $data = $this->validated();

        if (! isset($data['admin_name'])) {
            return null;
        }

        return [
            'first_name' => $data['admin_name'],
            'last_name' => $data['admin_last_name'],
            'email' => $data['admin_email'],
            'country' => array_key_exists('country_code', $data) ? ($data['country_code'] ?? 'EC') : $this->condominium()->country_code,
            'document_type_id' => $this->catalogItemId('document_types', $data['admin_document_type']),
            'document_number' => $data['admin_id_number'],
            'phone' => $data['admin_phone'] ?? null,
            'is_access_enabled' => $this->statusToBoolean($data['admin_status'] ?? null, true),
        ];
    }

    public function currency(): ?string
    {
        $data = $this->validated();

        return array_key_exists('currency', $data)
            ? $data['currency']
            : $this->condominium()->activeBillingSetting?->currency;
    }

    public function logo()
    {
        return $this->file('logo');
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $condominium = $this->condominium();
            $data = $this->all();

            $countryCode = array_key_exists('country_code', $data)
                ? ($data['country_code'] ?? $condominium->country_code)
                : $condominium->country_code;
            $provinceId = array_key_exists('province_id', $data)
                ? ($data['province_id'] !== null ? (int) $data['province_id'] : null)
                : $condominium->province_id;
            $cityId = array_key_exists('city_id', $data)
                ? ($data['city_id'] !== null ? (int) $data['city_id'] : null)
                : $condominium->city_id;

            if ($provinceId !== null) {
                $province = Province::query()->find($provinceId);

                if ($province !== null && $province->country?->code !== $countryCode) {
                    $validator->errors()->add('province_id', 'La provincia no pertenece al país seleccionado.');
                }
            }

            if ($cityId !== null) {
                $city = City::query()->find($cityId);
                $effectiveProvinceId = $provinceId ?? $condominium->province_id;

                if ($city !== null && $city->province_id !== $effectiveProvinceId) {
                    $validator->errors()->add('city_id', 'La ciudad no pertenece a la provincia seleccionada.');
                }
            }
        });
    }

    private function condominium(): Condominium
    {
        /** @var Condominium $condominium */
        $condominium = $this->route('condominium');

        return $condominium;
    }

    private function catalogItemId(string $catalogCode, ?string $label): ?int
    {
        if ($label === null) {
            return null;
        }

        $label = trim($label);
        $code = Str::slug($label, '_');

        return CatalogItem::query()
            ->whereHas('catalog', fn ($query) => $query
                ->where('code', $catalogCode)
                ->where('is_active', true))
            ->where('is_active', true)
            ->where(function ($query) use ($label, $code): void {
                $query->where('name', $label)
                    ->orWhere('code', $label)
                    ->orWhere('code', $code);
            })
            ->value('id');
    }

    private function statusToBoolean(?string $status, mixed $default): bool
    {
        if ($status === null) {
            return filter_var($default, FILTER_VALIDATE_BOOLEAN);
        }

        return Str::lower($status) === 'activo';
    }
}
