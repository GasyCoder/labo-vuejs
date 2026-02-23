<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run()
    {
        $admin = User::create([
            'name' => 'Administrateur Principal',
            'username' => 'adminlabo',
            'email' => 'admin@labo.com',
            'type' => 'superadmin',
            'password' => Hash::make('adminlabo'),
        ]);
        $admin->assignRole('superadmin');

        $secretaire = User::create([
            'name' => 'Secretaire Test',
            'username' => 'secretaire',
            'email' => 'secretaire@labo.com',
            'type' => 'secretaire',
            'password' => Hash::make('password'),
        ]);
        $secretaire->assignRole('secretaire');

        $technicien = User::create([
            'name' => 'Technicien Test',
            'username' => 'technicien',
            'email' => 'technicien@labo.com',
            'type' => 'technicien',
            'password' => Hash::make('password'),
        ]);
        $technicien->assignRole('technicien');

        $biologiste = User::create([
            'name' => 'Biologiste Test',
            'username' => 'biologiste',
            'email' => 'biologiste@labo.com',
            'type' => 'biologiste',
            'password' => Hash::make('password'),
        ]);
        $biologiste->assignRole('biologiste');
    }
}
