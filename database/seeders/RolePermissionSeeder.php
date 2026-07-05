<?php

namespace Database\Seeders;

use App\Models\Condominium;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = collect($this->permissions())->mapWithKeys(function (array $permission): array {
            $model = Permission::updateOrCreate([
                'code' => $permission['code'],
            ], [
                'module' => $permission['module'],
                'action' => $permission['action'],
                'name' => $permission['name'],
                'description' => $permission['description'] ?? null,
                'is_system' => true,
                'is_active' => true,
            ]);

            return [$model->code => $model];
        });

        Condominium::query()->each(function (Condominium $condominium) use ($permissions): void {
            $roles = collect($this->roles())->mapWithKeys(function (array $role) use ($condominium): array {
                $model = Role::updateOrCreate([
                    'condominium_id' => $condominium->id,
                    'code' => $role['code'],
                ], [
                    'name' => $role['name'],
                    'description' => $role['description'] ?? null,
                    'is_system' => true,
                    'is_active' => true,
                ]);

                return [$model->code => $model];
            });

            $administrator = $roles->get('administrador');
            $administratorPermissions = $permissions
                ->reject(fn (Permission $permission): bool => $permission->module === 'condominiums')
                ->pluck('id')
                ->all();

            $administrator?->permissions()->sync($administratorPermissions);

            $viewPermissions = $permissions
                ->filter(fn (Permission $permission): bool => $permission->module !== 'condominiums'
                    && str_ends_with($permission->code, '.view'))
                ->pluck('id')
                ->all();

            foreach (['presidente', 'tesorero', 'secretario'] as $code) {
                $roles->get($code)?->permissions()->sync($viewPermissions);
            }

            User::query()
                ->where(function ($query): void {
                    $query->whereIn('email', ['byronnp@gmail.com', 'swagger.admin@example.com'])
                        ->orWhere('email', 'like', 'admin.%@condominios.test');
                })
                ->get()
                ->each(function (User $adminUser) use ($condominium, $administrator): void {
                    $condominiumUser = DB::table('condominium_user')
                        ->where('condominium_id', $condominium->id)
                        ->where('user_id', $adminUser->id)
                        ->first();

                    if ($condominiumUser && $administrator) {
                        DB::table('condominium_user_role')->updateOrInsert([
                            'condominium_user_id' => $condominiumUser->id,
                            'role_id' => $administrator->id,
                        ], [
                            'created_at' => now(),
                            'updated_at' => now(),
                            'deleted_at' => null,
                        ]);
                    }
                });
        });

        $this->removeSeniorAdminFromCondominiums();
    }

    private function removeSeniorAdminFromCondominiums(): void
    {
        $seniorAdminId = User::where('email', 'byron_np@hotmail.com')->value('id');

        if ($seniorAdminId === null) {
            return;
        }

        $condominiumUserIds = DB::table('condominium_user')
            ->where('user_id', $seniorAdminId)
            ->pluck('id');

        if ($condominiumUserIds->isEmpty()) {
            return;
        }

        DB::table('condominium_user_role')
            ->whereIn('condominium_user_id', $condominiumUserIds)
            ->delete();

        DB::table('condominium_user')
            ->whereIn('id', $condominiumUserIds)
            ->delete();
    }

    private function roles(): array
    {
        return [
            ['code' => 'administrador', 'name' => 'Administrador', 'description' => 'Acceso completo al condominio.'],
            ['code' => 'directiva', 'name' => 'Directiva', 'description' => 'Miembro de la directiva del condominio.'],
            ['code' => 'contabilidad', 'name' => 'Contabilidad', 'description' => 'Gestión contable y financiera del condominio.'],
            ['code' => 'presidente', 'name' => 'Presidente', 'description' => 'Miembro principal de la directiva.'],
            ['code' => 'tesorero', 'name' => 'Tesorero', 'description' => 'Responsable de seguimiento financiero.'],
            ['code' => 'secretario', 'name' => 'Secretario', 'description' => 'Responsable de actas y comunicaciones.'],
            ['code' => 'guardia', 'name' => 'Guardia', 'description' => 'Control operativo de accesos.'],
            ['code' => 'propietario', 'name' => 'Propietario', 'description' => 'Propietario de una unidad.'],
            ['code' => 'residente', 'name' => 'Residente', 'description' => 'Residente del condominio.'],
        ];
    }

    private function permissions(): array
    {
        return [
            ['module' => 'condominiums', 'action' => 'view', 'name' => 'Ver condominios', 'code' => 'condominiums.view'],
            ['module' => 'condominiums', 'action' => 'create', 'name' => 'Crear condominios', 'code' => 'condominiums.create'],
            ['module' => 'condominiums', 'action' => 'update', 'name' => 'Actualizar condominios', 'code' => 'condominiums.update'],
            ['module' => 'users', 'action' => 'view', 'name' => 'Ver usuarios', 'code' => 'users.view'],
            ['module' => 'users', 'action' => 'create', 'name' => 'Crear usuarios', 'code' => 'users.create'],
            ['module' => 'users', 'action' => 'assign', 'name' => 'Asignar usuarios', 'code' => 'users.assign'],
            ['module' => 'users', 'action' => 'update', 'name' => 'Actualizar usuarios', 'code' => 'users.update'],
            ['module' => 'users', 'action' => 'status', 'name' => 'Cambiar estado de usuarios', 'code' => 'users.status'],
            ['module' => 'users', 'action' => 'invite', 'name' => 'Invitar usuarios', 'code' => 'users.invite'],
            ['module' => 'users', 'action' => 'resend_invitation', 'name' => 'Reenviar invitaciones', 'code' => 'users.resend_invitation'],
            ['module' => 'administrators', 'action' => 'view', 'name' => 'Ver administradores', 'code' => 'administrators.view'],
            ['module' => 'administrators', 'action' => 'create', 'name' => 'Crear administradores', 'code' => 'administrators.create'],
            ['module' => 'administrators', 'action' => 'update', 'name' => 'Actualizar administradores', 'code' => 'administrators.update'],
            ['module' => 'administrators', 'action' => 'assign', 'name' => 'Asignar administradores', 'code' => 'administrators.assign'],
            ['module' => 'administrators', 'action' => 'delete', 'name' => 'Eliminar administradores', 'code' => 'administrators.delete'],
            ['module' => 'roles', 'action' => 'view', 'name' => 'Ver roles', 'code' => 'roles.view'],
            ['module' => 'roles', 'action' => 'manage', 'name' => 'Administrar roles', 'code' => 'roles.manage'],
            ['module' => 'permissions', 'action' => 'view', 'name' => 'Ver permisos', 'code' => 'permissions.view'],
            ['module' => 'permissions', 'action' => 'manage', 'name' => 'Administrar permisos', 'code' => 'permissions.manage'],
            ['module' => 'menus', 'action' => 'view', 'name' => 'Ver menús', 'code' => 'menus.view'],
            ['module' => 'menus', 'action' => 'manage', 'name' => 'Administrar menús', 'code' => 'menus.manage'],
            ['module' => 'boards', 'action' => 'view', 'name' => 'Ver directivas', 'code' => 'boards.view'],
            ['module' => 'boards', 'action' => 'manage', 'name' => 'Administrar directivas', 'code' => 'boards.manage'],
            ['module' => 'payment_methods', 'action' => 'view', 'name' => 'Ver métodos de pago', 'code' => 'payment_methods.view'],
            ['module' => 'payment_methods', 'action' => 'manage', 'name' => 'Administrar métodos de pago', 'code' => 'payment_methods.manage'],
            ['module' => 'units', 'action' => 'view', 'name' => 'Ver unidades', 'code' => 'units.view'],
            ['module' => 'units', 'action' => 'manage', 'name' => 'Administrar unidades', 'code' => 'units.manage'],
            ['module' => 'unit_users', 'action' => 'view', 'name' => 'Ver personas por unidad', 'code' => 'unit_users.view'],
            ['module' => 'unit_users', 'action' => 'create', 'name' => 'Agregar personas por unidad', 'code' => 'unit_users.create'],
            ['module' => 'unit_users', 'action' => 'update', 'name' => 'Actualizar personas por unidad', 'code' => 'unit_users.update'],
            ['module' => 'unit_users', 'action' => 'deactivate', 'name' => 'Inactivar personas por unidad', 'code' => 'unit_users.deactivate'],
            ['module' => 'unit_users', 'action' => 'manage_access', 'name' => 'Gestionar acceso de personas por unidad', 'code' => 'unit_users.manage_access'],
            ['module' => 'unit_users', 'action' => 'manage_all', 'name' => 'Gestionar personas de todas las unidades', 'code' => 'unit_users.manage_all'],
            ['module' => 'unit_users', 'action' => 'manage_own', 'name' => 'Gestionar personas de unidades propias', 'code' => 'unit_users.manage_own'],
            ['module' => 'user_billing_profiles', 'action' => 'manage', 'name' => 'Administrar perfiles de facturación', 'code' => 'user_billing_profiles.manage'],
            ['module' => 'visitors', 'action' => 'view', 'name' => 'Ver visitantes', 'code' => 'visitors.view'],
            ['module' => 'visitors', 'action' => 'manage', 'name' => 'Administrar visitantes', 'code' => 'visitors.manage'],
            ['module' => 'visits', 'action' => 'view', 'name' => 'Ver visitas', 'code' => 'visits.view'],
            ['module' => 'visits', 'action' => 'manage', 'name' => 'Administrar visitas', 'code' => 'visits.manage'],
            ['module' => 'visit_logs', 'action' => 'manage', 'name' => 'Registrar accesos de visitas', 'code' => 'visit_logs.manage'],
            ['module' => 'common_areas', 'action' => 'view', 'name' => 'Ver áreas comunes', 'code' => 'common_areas.view'],
            ['module' => 'common_areas', 'action' => 'manage', 'name' => 'Administrar áreas comunes', 'code' => 'common_areas.manage'],
            ['module' => 'reservations', 'action' => 'view', 'name' => 'Ver reservas', 'code' => 'reservations.view'],
            ['module' => 'reservations', 'action' => 'manage', 'name' => 'Administrar reservas', 'code' => 'reservations.manage'],
            ['module' => 'incidents', 'action' => 'view', 'name' => 'Ver incidentes', 'code' => 'incidents.view'],
            ['module' => 'incidents', 'action' => 'manage', 'name' => 'Administrar incidentes', 'code' => 'incidents.manage'],
            ['module' => 'maintenances', 'action' => 'view', 'name' => 'Ver mantenimientos', 'code' => 'maintenances.view'],
            ['module' => 'maintenances', 'action' => 'manage', 'name' => 'Administrar mantenimientos', 'code' => 'maintenances.manage'],
        ];
    }
}
