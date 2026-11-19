<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('users')
            ->select('id', 'name')
            ->whereNotExists(function ($query): void {
                $query->selectRaw('1')->from('teams')
                    ->whereColumn('teams.user_id', 'users.id')
                    ->where('personal_team', true);
            })
            ->chunkById(200, function ($users): void {
                $now = now();
                DB::table('teams')->insert($users->map(fn ($user): array => [
                    'user_id' => $user->id,
                    'name' => explode(' ', $user->name, 2)[0]."'s Team",
                    'personal_team' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ])->all());
            });
    }

    public function down(): void
    {
        // Personal teams may contain memberships added after this migration.
    }
};
