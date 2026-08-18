<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('users')->updateOrInsert(
            ['email' => 'admin@sena.edu.co'],
            [
                'name'       => 'Administrador SENA',
                'email'      => 'admin@sena.edu.co',
                'password'   => Hash::make('Sena2026*'),
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
    }
}
