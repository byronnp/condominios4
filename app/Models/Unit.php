<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Unit extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'condominium_id',
        'condominium_block_id',
        'parent_unit_id',
        'unit_type_id',
        'code',
        'number',
        'floor',
        'area_m2',
        'current_aliquot_percentage',
        'is_assignable',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'area_m2' => 'decimal:2',
            'current_aliquot_percentage' => 'decimal:4',
            'is_assignable' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function condominium(): BelongsTo
    {
        return $this->belongsTo(Condominium::class);
    }

    public function block(): BelongsTo
    {
        return $this->belongsTo(CondominiumBlock::class, 'condominium_block_id');
    }

    public function parentUnit(): BelongsTo
    {
        return $this->belongsTo(Unit::class, 'parent_unit_id');
    }

    public function childUnits(): HasMany
    {
        return $this->hasMany(Unit::class, 'parent_unit_id');
    }

    public function unitType(): BelongsTo
    {
        return $this->belongsTo(CatalogItem::class, 'unit_type_id');
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'unit_user')
            ->withPivot([
                'id',
                'relationship_type_id',
                'started_at',
                'ended_at',
                'is_primary',
                'is_billing_responsible',
                'is_active',
                'deleted_at',
            ])
            ->withTimestamps();
    }

    public function aliquots(): HasMany
    {
        return $this->hasMany(UnitAliquot::class);
    }
}
