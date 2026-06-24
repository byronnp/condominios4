<?php

namespace Database\Seeders;

use App\Models\Condominium;
use App\Models\CondominiumBoard;
use App\Models\User;
use Illuminate\Database\Seeder;

class BoardSeeder extends Seeder
{
    public function run(): void
    {
        $condominium = Condominium::where('slug', 'condominio-los-cedros')->first();
        $admin = User::where('email', 'byronnp@gmail.com')->first();

        if (! $condominium || ! $admin) {
            return;
        }

        $board = CondominiumBoard::updateOrCreate([
            'condominium_id' => $condominium->id,
            'name' => 'Directiva 2026-2028',
        ], [
            'start_date' => '2026-01-01',
            'end_date' => '2028-12-31',
            'is_active' => true,
            'notes' => 'Directiva inicial de datos de prueba.',
        ]);

        $board->members()->updateOrCreate([
            'user_id' => $admin->id,
            'role_name' => 'presidente',
        ], [
            'started_at' => '2026-01-01',
            'ended_at' => '2028-12-31',
            'is_active' => true,
        ]);

        $seniorAdminId = User::where('email', 'byron_np@hotmail.com')->value('id');

        if ($seniorAdminId !== null) {
            $board->members()->where('user_id', $seniorAdminId)->delete();
        }
    }
}
