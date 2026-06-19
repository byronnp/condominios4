<?php

namespace App\Rules;

use App\Models\CatalogItem;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Str;

class ValidCatalogItemLabel implements ValidationRule
{
    public function __construct(
        private readonly string $catalogCode,
        private readonly bool $onlyActive = true,
    ) {}

    /**
     * Validate that the received label matches an item name or code from the expected catalog.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value)) {
            $fail('El campo :attribute no contiene un item de catálogo válido.');

            return;
        }

        $label = trim($value);
        $code = Str::slug($label, '_');

        $exists = CatalogItem::query()
            ->whereHas('catalog', function ($query): void {
                $query->where('code', $this->catalogCode);

                if ($this->onlyActive) {
                    $query->where('is_active', true);
                }
            })
            ->where(function ($query) use ($label, $code): void {
                $query->where('name', $label)
                    ->orWhere('code', $label)
                    ->orWhere('code', $code);
            })
            ->when($this->onlyActive, fn ($query) => $query->where('is_active', true))
            ->exists();

        if (! $exists) {
            $fail('El campo :attribute no contiene un item de catálogo válido.');
        }
    }
}
