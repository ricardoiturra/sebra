<?php

namespace Database\Seeders;

use App\Actions\Fortify\CreateNewUser;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Spatie\Permission\Models\Role;

class FullAdminSeeder extends Seeder
{
    public function run(CreateNewUser $createUser): void
    {
        DB::transaction(function () use ($createUser): void {
            $email = 'admin@er28-0652.com';
            $user = User::where('email', $email)->first();

            if ($user === null) {
                $password = config('auth.full_admin_seed_password');
                if (blank($password)) {
                    throw new RuntimeException('Configurá FULL_ADMIN_PASSWORD para crear la cuenta de administrador.');
                }

                $user = $createUser->create([
                    'name' => 'Administrador',
                    'email' => $email,
                    'password' => $password,
                    'password_confirmation' => $password,
                ]);
            }

            $user->assignRole(Role::findOrCreate('full admin', 'web'));
        });
    }
}
