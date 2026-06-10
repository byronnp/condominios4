<?php

namespace App\Rules;

use App\Models\CatalogItem;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class ValidCatalogItem implements ValidationRule
{
    public function __construct(
        private readonly string $catalogCode,
        private readonly bool $onlyActive = true,
    ) {
    }

    /**
     * Validate that the received id belongs to the expected catalog.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $exists = CatalogItem::query()
            ->whereKey($value)
            ->whereHas('catalog', function ($query): void {
                $query->where('code', $this->catalogCode);

                if ($this->onlyActive) {
                    $query->where('is_active', true);
                }
            })
            ->when($this->onlyActive, fn ($query) => $query->where('is_active', true))
            ->exists();

        if (! $exists) {
            $fail('El campo :attribute no contiene un item de catálogo válido.');
        }
    }
}
