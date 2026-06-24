<?php

namespace App\Http\Requests\Api\Condominiums;

use App\Models\CatalogItem;
use App\Models\City;
use App\Models\Province;
use App\Rules\ValidCatalogItem;
use App\Rules\ValidCatalogItemLabel;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
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
            'type' => ['nullable', 'string', 'max:255', new ValidCatalogItemLabel('condominium_types')],
            'description' => ['nullable', 'string'],
            'status' => ['nullable', 'string', Rule::in(['Activo', 'Inactivo', 'activo', 'inactivo'])],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'required_without:direction', 'string', 'max:255'],
            'direction' => ['nullable', 'required_without:address', 'string', 'max:255'],
            'reference' => ['nullable', 'string', 'max:255'],
            'country_code' => ['nullable', 'string', 'size:2', Rule::exists('countries', 'code')->where('is_active', true)],
            'province_id' => ['nullable', 'integer', 'required_with:city_id', Rule::exists('provinces', 'id')->where('is_active', true)],
            'city_id' => ['nullable', 'integer', Rule::exists('cities', 'id')->where('is_active', true)],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'currency' => ['nullable', 'string', 'size:3'],
            'towers' => ['nullable', 'integer', 'min:0'],
            'houses' => ['nullable', 'integer', 'min:0'],
            'characteristics' => ['nullable', 'array'],
            'characteristics.*' => ['integer', new ValidCatalogItem('condominium_features')],
            'admin_name' => ['nullable', 'required_with:admin_last_name,admin_document_type,admin_id_number,admin_email,admin_phone,admin_status', 'string', 'max:255'],
            'admin_last_name' => ['nullable', 'required_with:admin_name,admin_document_type,admin_id_number,admin_email,admin_phone,admin_status', 'string', 'max:255'],
            'admin_document_type' => ['nullable', 'required_with:admin_name,admin_last_name,admin_id_number,admin_email,admin_phone,admin_status', 'string', 'max:255', new ValidCatalogItemLabel('document_types')],
            'admin_id_number' => ['nullable', 'required_with:admin_name,admin_last_name,admin_document_type,admin_email,admin_phone,admin_status', 'string', 'max:30'],
            'admin_email' => ['nullable', 'required_with:admin_name,admin_last_name,admin_document_type,admin_id_number,admin_phone,admin_status', 'email', 'max:255'],
            'admin_phone' => ['nullable', 'string', 'max:50'],
            'admin_status' => ['nullable', 'required_with:admin_name,admin_last_name,admin_document_type,admin_id_number,admin_email,admin_phone', 'string', Rule::in(['Activo', 'Inactivo', 'activo', 'inactivo'])],
            'logo' => ['nullable', 'image', 'max:5120'],
            'total_units' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    /**
     * Return only the normalized attributes that belong to the condominiums table.
     *
     * @return array<string, mixed>
     */
    public function condominiumData(): array
    {
        $data = $this->validated();

        $condominium = [
            'name' => $data['name'],
            'slug' => $data['slug'] ?? Str::slug($data['name']),
            'ruc' => $data['ruc'] ?? null,
            'condominium_type_id' => $this->catalogItemId('condominium_types', $data['type'] ?? null),
            'description' => $data['description'] ?? null,
            'email' => $data['email'] ?? null,
            'phone' => $data['phone'] ?? null,
            'address' => $data['address'] ?? $data['direction'],
            'address_reference' => $data['reference'] ?? null,
            'country_code' => $data['country_code'] ?? 'EC',
            'province_id' => $data['province_id'] ?? null,
            'city_id' => $data['city_id'] ?? null,
            'latitude' => $data['latitude'] ?? null,
            'longitude' => $data['longitude'] ?? null,
            'towers_count' => $data['towers'] ?? 0,
            'houses_count' => $data['houses'] ?? 0,
            'total_units' => $data['total_units'] ?? 0,
            'is_active' => $this->statusToBoolean($data['status'] ?? null, $data['is_active'] ?? true),
        ];

        return array_filter($condominium, fn (mixed $value): bool => $value !== null);
    }

    /**
     * @return array<int, int>
     */
    public function featureIds(): array
    {
        return collect($this->validated('characteristics', []))
            ->map(fn (int|string $featureId): int => (int) $featureId)
            ->values()
            ->all();
    }

    /**
     * @return array<string, mixed>|null
     */
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
            'country' => $data['country_code'] ?? 'EC',
            'document_type_id' => $this->catalogItemId('document_types', $data['admin_document_type']),
            'document_number' => $data['admin_id_number'],
            'phone' => $data['admin_phone'] ?? null,
            'is_access_enabled' => $this->statusToBoolean($data['admin_status'] ?? null, true),
        ];
    }

    public function currency(): ?string
    {
        return $this->validated('currency');
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
