<?php

namespace App\Domain\PlatformAdministrators\Services;

use App\Domain\Users\Services\UserInvitationService;
use App\Models\AuthSession;
use App\Models\RefreshToken;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use App\Models\UserAccessInvitation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PlatformAdministratorService
{
    public function __construct(
        private readonly UserInvitationService $invitationService,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     * @return array{user: User, invitation: UserAccessInvitation|null, created: bool, already_assigned: bool}
     */
    public function createOrAssign(array $data, User $actor): array
    {
        return DB::transaction(function () use ($data, $actor): array {
            $role = $this->platformRole();
            $tenant = $this->platformTenant();
            $user = $this->findUser($data);
            $created = false;

            if (! $user) {
                $user = User::create([
                    ...$data,
                    'password' => null,
                    'is_access_enabled' => false,
                ]);
                $created = true;
            }

            $alreadyAssigned = DB::table('role_user')
                ->where('user_id', $user->id)
                ->where('role_id', $role->id)
                ->where('tenant_id', $tenant->id)
                ->exists();

            if (! $alreadyAssigned) {
                DB::table('role_user')->insert([
                    'user_id' => $user->id,
                    'role_id' => $role->id,
                    'tenant_id' => $tenant->id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            $invitation = $user->is_access_enabled
                ? null
                : $this->invitationService->invitePlatformAdministrator($user, $role, $actor);

            Log::info('platform_administrator.assigned', [
                'actor_user_id' => $actor->id,
                'target_user_id' => $user->id,
                'created' => $created,
                'already_assigned' => $alreadyAssigned,
                'invitation_id' => $invitation?->id,
            ]);

            return [
                'user' => $user->fresh(['documentType', 'latestAccessInvitation']),
                'invitation' => $invitation,
                'created' => $created,
                'already_assigned' => $alreadyAssigned,
            ];
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

    public function updateStatus(User $administrator, bool $isAccessEnabled, User $actor): User
    {
        return DB::transaction(function () use ($administrator, $isAccessEnabled, $actor): User {
            $administrator->update(['is_access_enabled' => $isAccessEnabled]);

            if (! $isAccessEnabled) {
                $this->revokeSessions($administrator, $actor);
            }

            Log::info('platform_administrator.status_updated', [
                'actor_user_id' => $actor->id,
                'target_user_id' => $administrator->id,
                'is_access_enabled' => $isAccessEnabled,
            ]);

            return $administrator->fresh(['documentType', 'latestAccessInvitation']);
        });
    }

    public function removeRole(User $administrator, User $actor): void
    {
        DB::transaction(function () use ($administrator, $actor): void {
            $role = $this->platformRole();
            $tenant = $this->platformTenant();

            DB::table('role_user')
                ->where('user_id', $administrator->id)
                ->where('role_id', $role->id)
                ->where('tenant_id', $tenant->id)
                ->delete();

            Log::info('platform_administrator.role_removed', [
                'actor_user_id' => $actor->id,
                'target_user_id' => $administrator->id,
            ]);
        });
    }

    public function revokeSessions(User $administrator, User $actor): void
    {
        AuthSession::query()
            ->where('user_id', $administrator->id)
            ->active()
            ->update([
                'ended_at' => now(),
                'last_activity_at' => now(),
                'logout_reason' => 'platform_admin_disabled',
                'is_active' => false,
            ]);

        RefreshToken::query()
            ->where('user_id', $administrator->id)
            ->whereNull('revoked_at')
            ->update([
                'revoked_at' => now(),
                'revoked_by_user_id' => $actor->id,
                'revoke_reason' => 'platform_admin_disabled',
            ]);
    }

    public function platformRole(): Role
    {
        return Role::updateOrCreate([
            'condominium_id' => null,
            'code' => 'administrador_senior',
        ], [
            'name' => 'Administrador Senior',
            'description' => 'Acceso maestro a la plataforma.',
            'is_system' => true,
            'is_active' => true,
        ]);
    }

    private function platformTenant(): Tenant
    {
        return Tenant::firstOrCreate([
            'slug' => 'admin-platform',
        ], [
            'name' => 'Admin Platform',
        ]);
    }
}
