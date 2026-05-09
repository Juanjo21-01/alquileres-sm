<?php

namespace Database\Seeders;

use App\Models\Rol;
use App\Models\User;
use Illuminate\Database\Seeder;

class UserAdminSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => env('ADMIN_EMAIL')],
            [
                'rol_id' => Rol::where('codigo', Rol::COD_ADMIN)->value('id'),
                'name' => env('ADMIN_NAME'),
                'password' => env('ADMIN_PASSWORD'),
                'activo' => true,
                'email_verified_at' => now(),
            ]
        );
    }
}
