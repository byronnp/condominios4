<?php

namespace App\Rules;

use App\Models\CatalogItem;
use Closure;
use Illuminate\Contracts\Validation\DataAwareRule;
use Illuminate\Contracts\Validation\ValidationRule;

class ValidDocumentNumber implements DataAwareRule, ValidationRule
{
    /** @var array<string, mixed> */
    private array $data = [];

    public function setData(array $data): static
    {
        $this->data = $data;

        return $this;
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $code = CatalogItem::query()->whereKey($this->data['document_type_id'] ?? null)->value('code');
        $number = (string) $value;

        $valid = match ($code) {
            'cedula' => preg_match('/^\d{10}$/', $number) === 1,
            'ruc' => preg_match('/^\d{13}$/', $number) === 1,
            'pasaporte', 'passport' => preg_match('/^[A-Za-z0-9-]{5,20}$/', $number) === 1,
            default => $number !== '' && mb_strlen($number) <= 30,
        };

        if (! $valid) {
            $fail('El número de documento no tiene el formato esperado para el tipo seleccionado.');
        }
    }
}
