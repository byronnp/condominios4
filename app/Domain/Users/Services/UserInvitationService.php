<?php

namespace App\Domain\Users\Services;

use App\Exceptions\Auth\InvitationAlreadyUsedException;
use App\Exceptions\Auth\InvitationExpiredException;
use App\Mail\UserAccessInvitationMail;
use App\Models\Condominium;
use App\Models\Role;
use App\Models\User;
use App\Models\UserAccessInvitation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class UserInvitationService
{
    public function invite(User $user, Condominium $condominium, Role $role, User $invitedBy): UserAccessInvitation
    {
        $plainToken = Str::random(64);

        $this->revokePending($user, $condominium);

        $invitation = UserAccessInvitation::create([
            'condominium_id' => $condominium->id,
            'user_id' => $user->id,
            'role_id' => $role->id,
            'invited_by_user_id' => $invitedBy->id,
            'email' => $user->email,
            'token_hash' => hash('sha256', $plainToken),
            'status' => UserAccessInvitation::STATUS_PENDING,
            'expires_at' => now()->addHours((int) config('invitations.expires_hours', 24)),
        ]);

        Mail::to($user->email)->queue(
            (new UserAccessInvitationMail($invitation, $plainToken))->afterCommit()
        );

        return $invitation;
    }

    /** @param array<string, mixed> $data */
    public function accept(array $data): UserAccessInvitation
    {
        $result = DB::transaction(function () use ($data): UserAccessInvitation {
            $invitation = UserAccessInvitation::query()
                ->where('token_hash', hash('sha256', $data['token']))
                ->lockForUpdate()
                ->first();

            if (! $invitation || $invitation->status !== UserAccessInvitation::STATUS_PENDING || $invitation->revoked_at !== null) {
                throw new InvitationAlreadyUsedException();
            }

            if ($invitation->expires_at->isPast()) {
                $invitation->update(['status' => UserAccessInvitation::STATUS_EXPIRED, 'token_hash' => null]);
                throw new InvitationExpiredException();
            }

            $assigned = $invitation->unit_id !== null
                ? DB::table('unit_user')
                    ->where('unit_id', $invitation->unit_id)
                    ->where('user_id', $invitation->user_id)
                    ->where('is_active', true)
                    ->whereNull('deleted_at')
                    ->exists()
                : DB::table('condominium_user')
                    ->where('condominium_id', $invitation->condominium_id)
                    ->where('user_id', $invitation->user_id)
                    ->whereNull('deleted_at')
                    ->exists();

            if (! $invitation->user || ! $assigned) {
                throw new InvitationAlreadyUsedException();
            }

            $invitation->user->update([
                'email' => $invitation->email,
                'password' => $data['password'],
                'is_access_enabled' => true,
                'email_verified_at' => $invitation->user->email_verified_at ?? now(),
            ]);

            $invitation->update([
                'status' => UserAccessInvitation::STATUS_ACCEPTED,
                'accepted_at' => now(),
                'token_hash' => null,
            ]);

            UserAccessInvitation::query()
                ->where('user_id', $invitation->user_id)
                ->where('condominium_id', $invitation->condominium_id)
                ->whereKeyNot($invitation->id)
                ->where('status', UserAccessInvitation::STATUS_PENDING)
                ->update(['status' => UserAccessInvitation::STATUS_REVOKED, 'revoked_at' => now(), 'token_hash' => null]);

            return $invitation;
        });

        return $result;
    }

    public function revokePending(User $user, Condominium $condominium): void
    {
        UserAccessInvitation::query()
            ->where('user_id', $user->id)
            ->where('condominium_id', $condominium->id)
            ->where('status', UserAccessInvitation::STATUS_PENDING)
            ->update(['status' => UserAccessInvitation::STATUS_REVOKED, 'revoked_at' => now(), 'token_hash' => null]);
    }
}
