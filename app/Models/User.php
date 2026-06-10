<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password', 'country', 'document_type_id', 'document_number'])]
#[Hidden(['password', 'remember_token', 'api_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, SoftDeletes;

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
        ];
    }

    public function tenants(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(Tenant::class, 'tenant_user');
    }

    public function condominiums(): BelongsToMany
    {
        return $this->belongsToMany(Condominium::class, 'condominium_user')
            ->withPivot(['id', 'is_active', 'joined_at', 'deleted_at'])
            ->withTimestamps();
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

    public function roles(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'role_user')->withPivot('tenant_id');
    }

    public function permissionsForCondominium(Condominium $condominium): \Illuminate\Support\Collection
    {
        $condominiumUser = \Illuminate\Support\Facades\DB::table('condominium_user')
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
