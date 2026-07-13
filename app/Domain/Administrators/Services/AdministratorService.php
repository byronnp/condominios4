<?php

namespace App\Domain\Administrators\Services;

use App\Domain\Users\Services\UserInvitationService;
use App\Models\Condominium;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class AdministratorService
{
    public function __construct(
        private readonly UserInvitationService $invitationService,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function assignUserAsAdministrator(Condominium $condominium, array $data, User $invitedBy): User
    {
        return DB::transaction(function () use ($condominium, $data, $invitedBy): User {
            $administrator = $this->findUser($data);

            if (! $administrator) {
                $administrator = User::create([
                    ...$data,
                    'password' => null,
                    'is_access_enabled' => false,
                ]);
            }

            $role = $this->assignToCondominium($administrator, $condominium);

            if (! $administrator->is_access_enabled) {
                $this->invitationService->invite($administrator, $condominium, $role, $invitedBy);
            }

            return $administrator->fresh('documentType');
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function findUser(array $data): ?User
    {
        return User::query()
            ->where('country', $data['country'])
            ->where('document_type_id', $data['document_type_id'])
            ->where('document_number', $data['document_number'])
            ->first()
            ?? User::query()->where('email', $data['email'])->first();
    }

    public function assignToCondominium(User $administrator, Condominium $condominium): Role
    {
        return DB::transaction(function () use ($administrator, $condominium): Role {
            DB::table('condominium_user')->updateOrInsert([
                'condominium_id' => $condominium->id,
                'user_id' => $administrator->id,
            ], [
                'is_active' => true,
                'joined_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
                'deleted_at' => null,
            ]);

            $condominiumUserId = DB::table('condominium_user')
                ->where('condominium_id', $condominium->id)
                ->where('user_id', $administrator->id)
                ->value('id');

            $role = $this->administratorRole($condominium);

            DB::table('condominium_user_role')->updateOrInsert([
                'condominium_user_id' => $condominiumUserId,
                'role_id' => $role->id,
            ], [
                'created_at' => now(),
                'updated_at' => now(),
                'deleted_at' => null,
            ]);

            return $role;
        });
    }

    public function removeFromCondominium(User $administrator, Condominium $condominium): void
    {
        DB::transaction(function () use ($administrator, $condominium): void {
            $condominiumUser = DB::table('condominium_user')
                ->where('condominium_id', $condominium->id)
                ->where('user_id', $administrator->id)
                ->whereNull('deleted_at')
                ->first();

            abort_if($condominiumUser === null, 404);

            $administratorRoleId = Role::query()
                ->where('condominium_id', $condominium->id)
                ->where('code', 'administrador')
                ->value('id');

            $roleAssignment = DB::table('condominium_user_role')
                ->where('condominium_user_id', $condominiumUser->id)
                ->where('role_id', $administratorRoleId)
                ->whereNull('deleted_at')
                ->first();

            abort_if($roleAssignment === null, 404);

            DB::table('condominium_user_role')
                ->where('id', $roleAssignment->id)
                ->update(['deleted_at' => now(), 'updated_at' => now()]);

            $hasOtherRoles = DB::table('condominium_user_role')
                ->where('condominium_user_id', $condominiumUser->id)
                ->whereNull('deleted_at')
                ->exists();

            if (! $hasOtherRoles) {
                DB::table('condominium_user')
                    ->where('id', $condominiumUser->id)
                    ->update(['is_active' => false, 'deleted_at' => now(), 'updated_at' => now()]);
            }
        });
    }

    public function deactivate(User $administrator): void
    {
        DB::transaction(function () use ($administrator): void {
            $administrator->update(['is_access_enabled' => false]);

            $relations = DB::table('condominium_user')
                ->join('condominium_user_role', 'condominium_user_role.condominium_user_id', '=', 'condominium_user.id')
                ->join('roles', 'roles.id', '=', 'condominium_user_role.role_id')
                ->where('condominium_user.user_id', $administrator->id)
                ->where('roles.code', 'administrador')
                ->whereNull('condominium_user_role.deleted_at')
                ->select('condominium_user.id as condominium_user_id', 'condominium_user_role.id as role_assignment_id')
                ->get();

            foreach ($relations as $relation) {
                DB::table('condominium_user_role')
                    ->where('id', $relation->role_assignment_id)
                    ->update(['deleted_at' => now(), 'updated_at' => now()]);

                $hasOtherRoles = DB::table('condominium_user_role')
                    ->where('condominium_user_id', $relation->condominium_user_id)
                    ->whereNull('deleted_at')
                    ->exists();

                if (! $hasOtherRoles) {
                    DB::table('condominium_user')
                        ->where('id', $relation->condominium_user_id)
                        ->update(['is_active' => false, 'deleted_at' => now(), 'updated_at' => now()]);
                }
            }
        });
    }

    private function administratorRole(Condominium $condominium): Role
    {
        $role = Role::updateOrCreate([
            'condominium_id' => $condominium->id,
            'code' => 'administrador',
        ], [
            'name' => 'Administrador',
            'description' => 'Acceso completo al condominio.',
            'is_system' => true,
            'is_active' => true,
        ]);

        $role->permissions()->sync(Permission::query()->where('is_active', true)->pluck('id')->all());

        return $role;
    }
}
