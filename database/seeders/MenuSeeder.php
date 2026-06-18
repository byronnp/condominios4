<?php

namespace Database\Seeders;

use App\Models\Menu;
use App\Models\Permission;
use Illuminate\Database\Seeder;

class MenuSeeder extends Seeder
{
    public function run(): void
    {
        $activeCodes = [];

        foreach ($this->menus() as $menuData) {
            $activeCodes[] = $menuData['code'];

            $menu = Menu::updateOrCreate([
                'code' => $menuData['code'],
            ], [
                'parent_id' => $menuData['parent_code']
                    ? Menu::query()->where('code', $menuData['parent_code'])->value('id')
                    : null,
                'name' => $menuData['name'],
                'path' => $menuData['path'],
                'icon' => $menuData['icon'],
                'category_code' => $menuData['category_code'],
                'category_name' => $menuData['category_name'],
                'category_sort_order' => $menuData['category_sort_order'],
                'sort_order' => $menuData['sort_order'],
                'is_active' => true,
            ]);

            $permissionIds = Permission::query()
                ->whereIn('code', $menuData['permissions'])
                ->pluck('id')
                ->all();

            $menu->permissions()->sync($permissionIds);
        }

        Menu::query()
            ->whereNotIn('code', $activeCodes)
            ->update(['is_active' => false]);
    }

    private function menus(): array
    {
        $principal = [
            'category_code' => 'principal',
            'category_name' => 'Principal',
            'category_sort_order' => 1,
        ];

        $herramientas = [
            'category_code' => 'herramientas',
            'category_name' => 'Herramientas',
            'category_sort_order' => 2,
        ];

        return [
            ['name' => 'Resumen general', 'code' => 'dashboard', 'path' => '/dashboard', 'icon' => 'layout-dashboard', 'sort_order' => 1, 'permissions' => [], 'parent_code' => null, ...$principal],
            ['name' => 'Condominios', 'code' => 'condominios', 'path' => '/condominios', 'icon' => 'building', 'sort_order' => 2, 'permissions' => ['condominiums.view'], 'parent_code' => null, ...$principal],
            ['name' => 'Nuevo condominio', 'code' => 'condominios_nuevo', 'path' => '/condominios/nuevo', 'icon' => 'building-2', 'sort_order' => 3, 'permissions' => ['condominiums.create'], 'parent_code' => null, ...$principal],
            ['name' => 'Administradores', 'code' => 'administradores', 'path' => '/administradores', 'icon' => 'users', 'sort_order' => 4, 'permissions' => ['users.view'], 'parent_code' => null, ...$principal],
            ['name' => 'Nuevo administrador', 'code' => 'administradores_nuevo', 'path' => '/administradores/nuevo', 'icon' => 'user-plus', 'sort_order' => 5, 'permissions' => ['users.create'], 'parent_code' => null, ...$principal],
            ['name' => 'Unidades', 'code' => 'unidades', 'path' => '/unidades', 'icon' => 'home', 'sort_order' => 6, 'permissions' => ['units.view'], 'parent_code' => null, ...$principal],
            ['name' => 'Nueva unidad', 'code' => 'unidades_nueva', 'path' => '/unidades/nueva', 'icon' => 'home-plus', 'sort_order' => 7, 'permissions' => ['units.manage'], 'parent_code' => null, ...$principal],
            ['name' => 'Propietarios', 'code' => 'propietarios', 'path' => '/propietarios', 'icon' => 'user-round-check', 'sort_order' => 8, 'permissions' => ['unit_users.view'], 'parent_code' => null, ...$principal],
            ['name' => 'Residentes', 'code' => 'residentes', 'path' => '/residentes', 'icon' => 'users-round', 'sort_order' => 9, 'permissions' => ['unit_users.view'], 'parent_code' => null, ...$principal],
            ['name' => 'Pagos y cobros', 'code' => 'pagos', 'path' => '/pagos', 'icon' => 'wallet-cards', 'sort_order' => 10, 'permissions' => ['payments.view'], 'parent_code' => null, ...$principal],
            ['name' => 'Reservas', 'code' => 'reservas', 'path' => '/reservas', 'icon' => 'calendar-days', 'sort_order' => 11, 'permissions' => ['reservations.view'], 'parent_code' => null, ...$principal],
            ['name' => 'Mantenimientos', 'code' => 'mantenimiento', 'path' => '/mantenimiento', 'icon' => 'wrench', 'sort_order' => 12, 'permissions' => ['maintenance.view'], 'parent_code' => null, ...$principal],
            ['name' => 'Comunicados', 'code' => 'comunicados', 'path' => '/comunicados', 'icon' => 'megaphone', 'sort_order' => 13, 'permissions' => ['announcements.view'], 'parent_code' => null, ...$principal],
            ['name' => 'Visitantes', 'code' => 'visitantes', 'path' => '/visitantes', 'icon' => 'contact', 'sort_order' => 14, 'permissions' => ['visitors.view'], 'parent_code' => null, ...$principal],
            ['name' => 'Reportes', 'code' => 'reportes', 'path' => '/reportes', 'icon' => 'chart-column', 'sort_order' => 1, 'permissions' => ['reports.view'], 'parent_code' => null, ...$herramientas],
            ['name' => 'Configuración', 'code' => 'configuracion', 'path' => '/configuracion', 'icon' => 'settings', 'sort_order' => 2, 'permissions' => ['settings.view'], 'parent_code' => null, ...$herramientas],
        ];
    }
}
