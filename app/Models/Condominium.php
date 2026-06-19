<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Condominium extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'condominiums';

    protected $fillable = [
        'name',
        'slug',
        'ruc',
        'condominium_type_id',
        'description',
        'email',
        'phone',
        'address',
        'address_reference',
        'country_code',
        'province_id',
        'city_id',
        'latitude',
        'longitude',
        'towers_count',
        'houses_count',
        'logo_path',
        'total_units',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'total_units' => 'integer',
            'province_id' => 'integer',
            'city_id' => 'integer',
            'condominium_type_id' => 'integer',
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
            'towers_count' => 'integer',
            'houses_count' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function type(): BelongsTo
    {
        return $this->belongsTo(CatalogItem::class, 'condominium_type_id');
    }

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class, 'country_code', 'code');
    }

    public function province(): BelongsTo
    {
        return $this->belongsTo(Province::class);
    }

    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'condominium_user')
            ->withPivot(['id', 'is_active', 'joined_at', 'deleted_at'])
            ->withTimestamps();
    }

    public function roles(): HasMany
    {
        return $this->hasMany(Role::class);
    }

    public function boards(): HasMany
    {
        return $this->hasMany(CondominiumBoard::class);
    }

    public function paymentMethods(): HasMany
    {
        return $this->hasMany(CondominiumPaymentMethod::class);
    }

    public function catalogItems(): HasMany
    {
        return $this->hasMany(CondominiumCatalogItem::class);
    }

    public function features(): BelongsToMany
    {
        return $this->belongsToMany(CatalogItem::class, 'condominium_features')
            ->withTimestamps()
            ->wherePivotNull('deleted_at');
    }

    public function billingSettings(): HasMany
    {
        return $this->hasMany(CondominiumBillingSetting::class);
    }

    public function activeBillingSetting(): HasOne
    {
        return $this->hasOne(CondominiumBillingSetting::class)->where('is_active', true);
    }

    public function blocks(): HasMany
    {
        return $this->hasMany(CondominiumBlock::class);
    }

    public function units(): HasMany
    {
        return $this->hasMany(Unit::class);
    }
}
