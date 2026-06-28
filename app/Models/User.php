<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

#[Fillable(['name', 'first_name', 'last_name', 'email', 'password', 'country', 'document_type_id', 'document_number', 'phone', 'secondary_phone', 'is_access_enabled'])]
#[Hidden(['password', 'remember_token', 'api_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, SoftDeletes;

    protected static function booted(): void
    {
        static::saving(function (User $user): void {
            if ($user->isDirty(['first_name', 'last_name'])) {
                $user->name = trim(implode(' ', array_filter([
                    $user->first_name,
                    $user->last_name,
                ], fn (?string $value): bool => filled($value))));

                return;
            }

            if ($user->isDirty('name')) {
                $user->first_name = $user->name;
                $user->last_name = null;
            }
        });
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_access_enabled' => 'boolean',
        ];
    }

    public function tenants(): BelongsToMany
    {
        return $this->belongsToMany(Tenant::class, 'tenant_user');
    }

    public function condominiums(): BelongsToMany
    {
        return $this->belongsToMany(Condominium::class, 'condominium_user')
            ->withPivot(['id', 'is_active', 'joined_at', 'deleted_at'])
            ->withTimestamps();
    }

    public function scopeVisibleTo(Builder $query, User $actor): Builder
    {
        if ($actor->isPlatformAdmin()) {
            return $query;
        }

        return $query->whereHas('condominiums', function (Builder $query) use ($actor): void {
            $query->whereIn('condominiums.id', $actor->manageableCondominiumIds('users.view'))
                ->where('condominium_user.is_active', true)
                ->whereNull('condominium_user.deleted_at');
        });
    }

    /** @return array<int, int> */
    public function manageableCondominiumIds(?string $permission = null): array
    {
        $query = DB::table('condominium_user')
            ->join('condominium_user_role', 'condominium_user_role.condominium_user_id', '=', 'condominium_user.id')
            ->join('roles', 'roles.id', '=', 'condominium_user_role.role_id')
            ->where('condominium_user.user_id', $this->id)
            ->where('condominium_user.is_active', true)
            ->where('roles.code', 'administrador')
            ->whereNull('condominium_user.deleted_at')
            ->whereNull('condominium_user_role.deleted_at')
            ->whereNull('roles.deleted_at');

        if ($permission !== null) {
            $query->join('role_permission', 'role_permission.role_id', '=', 'roles.id')
                ->join('permissions', 'permissions.id', '=', 'role_permission.permission_id')
                ->where('permissions.code', $permission)
                ->whereNull('role_permission.deleted_at')
                ->whereNull('permissions.deleted_at');
        }

        return $query->distinct()->pluck('condominium_user.condominium_id')
            ->map(fn ($id): int => (int) $id)->all();
    }

    public function documentType(): BelongsTo
    {
        return $this->belongsTo(CatalogItem::class, 'document_type_id');
    }

    public function authSessions(): HasMany
    {
        return $this->hasMany(AuthSession::class);
    }

    public function refreshTokens(): HasMany
    {
        return $this->hasMany(RefreshToken::class);
    }

    public function billingProfiles(): HasMany
    {
        return $this->hasMany(UserBillingProfile::class);
    }

    public function residentProfile(): HasOne
    {
        return $this->hasOne(ResidentProfile::class);
    }

    public function units(): BelongsToMany
    {
        return $this->belongsToMany(Unit::class, 'unit_user')
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

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'role_user')->withPivot('tenant_id');
    }

    public function permissionsForCondominium(Condominium $condominium): Collection
    {
        $condominiumUser = DB::table('condominium_user')
            ->where('condominium_id', $condominium->id)
            ->where('user_id', $this->id)
            ->where('is_active', true)
            ->whereNull('deleted_at')
            ->first();

        if (! $condominiumUser) {
            return collect();
        }

        return Permission::query()
            ->select('permissions.*')
            ->join('role_permission', 'role_permission.permission_id', '=', 'permissions.id')
            ->join('condominium_user_role', 'condominium_user_role.role_id', '=', 'role_permission.role_id')
            ->where('condominium_user_role.condominium_user_id', $condominiumUser->id)
            ->where('permissions.is_active', true)
            ->whereNull('permissions.deleted_at')
            ->whereNull('role_permission.deleted_at')
            ->whereNull('condominium_user_role.deleted_at')
            ->distinct()
            ->get();
    }

    public function hasPermission(string $permissionCode, Condominium $condominium): bool
    {
        return $this->permissionsForCondominium($condominium)->contains('code', $permissionCode);
    }

    public function belongsToTenant(Tenant $tenant): bool
    {
        return $this->tenants()->where('tenant_id', $tenant->id)->exists();
    }

    public function rolesForTenant(Tenant $tenant): \Illuminate\Database\Eloquent\Collection
    {
        return $this->roles()->wherePivot('tenant_id', $tenant->id)->get();
    }

    public function platformRoles(): \Illuminate\Database\Eloquent\Collection
    {
        return $this->roles()
            ->whereNull('roles.condominium_id')
            ->where('roles.is_active', true)
            ->get();
    }

    public function platformRole(): ?Role
    {
        return $this->platformRoles()
            ->first(fn (Role $role): bool => in_array($role->code, ['administrador_senior', 'admin'], true)
                || in_array($role->name, ['ADMINISTRADOR SENIOR', 'Administrador Senior', 'admin'], true));
    }

    public function isPlatformAdmin(): bool
    {
        return $this->platformRole() !== null;
    }

    public function hasRole(string $role, Tenant $tenant): bool
    {
        return $this->rolesForTenant($tenant)->contains('name', $role);
    }

    public function assignRole(string $role, Tenant $tenant): Role
    {
        $roleModel = Role::firstOrCreate(['name' => $role]);
        $this->roles()->syncWithoutDetaching([$roleModel->id => ['tenant_id' => $tenant->id]]);

        return $roleModel;
    }

    public function createApiToken(): string
    {
        $token = bin2hex(random_bytes(32));
        $this->api_token = hash('sha256', $token);
        $this->save();

        return $token;
    }

    public function revokeApiToken(): void
    {
        $this->api_token = null;
        $this->save();
    }
}
