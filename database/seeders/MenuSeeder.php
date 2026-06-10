<?php

namespace Database\Seeders;

use App\Models\Menu;
use App\Models\Permission;
use Illuminate\Database\Seeder;

class MenuSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->menus() as $parentData) {
            $parent = Menu::updateOrCreate([
                'code' => $parentData['code'],
            ], [
                'parent_id' => null,
                'name' => $parentData['name'],
                'path' => null,
                'icon' => $parentData['icon'],
                'sort_order' => $parentData['sort_order'],
                'is_active' => true,
            ]);

            foreach ($parentData['children'] as $childData) {
                $child = Menu::updateOrCreate([
                    'code' => $childData['code'],
                ], [
                    'parent_id' => $parent->id,
                    'name' => $childData['name'],
                    'path' => $childData['path'],
                    'icon' => $childData['icon'],
                    'sort_order' => $childData['sort_order'],
                    'is_active' => true,
                ]);

                $permissionIds = Permission::query()
                    ->whereIn('code', $childData['permissions'])
                    ->pluck('id')
                    ->all();

                $child->permissions()->sync($permissionIds);
            }
        }
    }

    private function menus(): array
    {
        return [
            [
                'name' => 'Administración',
                'code' => 'administracion',
                'icon' => 'settings',
                'sort_order' => 1,
                'children' => [
                    ['name' => 'Condominios', 'code' => 'condominios', 'path' => '/condominios', 'icon' => 'building', 'sort_order' => 1, 'permissions' => ['condominiums.view']],
                    ['name' => 'Usuarios', 'code' => 'usuarios', 'path' => '/usuarios', 'icon' => 'users', 'sort_order' => 2, 'permissions' => ['users.view']],
                    ['name' => 'Roles y permisos', 'code' => 'roles_permisos', 'path' => '/roles-permisos', 'icon' => 'shield', 'sort_order' => 3, 'permissions' => ['roles.view', 'permissions.view']],
                    ['name' => 'Menús', 'code' => 'menus', 'path' => '/menus', 'icon' => 'menu', 'sort_order' => 4, 'permissions' => ['menus.view']],
                ],
            ],
            [
                'name' => 'Directivas',
                'code' => 'directivas',
                'icon' => 'users-round',
                'sort_order' => 2,
                'children' => [
                    ['name' => 'Directiva actual', 'code' => 'directiva_actual', 'path' => '/directivas/actual', 'icon' => 'user-check', 'sort_order' => 1, 'permissions' => ['boards.view']],
                    ['name' => 'Métodos de pago', 'code' => 'metodos_pago', 'path' => '/metodos-pago', 'icon' => 'credit-card', 'sort_order' => 2, 'permissions' => ['payment_methods.view']],
                ],
            ],
        ];
    }
}
