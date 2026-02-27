<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $this->createOrUpdateUser(
            name: 'Administrateur Principal',
            username: 'adminlabo',
            email: 'admin@labo.com',
            type: 'superadmin',
            password: 'adminlabo',
            role: 'superadmin'
        );

        $this->createOrUpdateUser(
            name: 'Secretaire Test',
            username: 'secretaire',
            email: 'secretaire@labo.com',
            type: 'secretaire',
            password: 'password',
            role: 'secretaire'
        );

        $this->createOrUpdateUser(
            name: 'Technicien Test',
            username: 'technicien',
            email: 'technicien@labo.com',
            type: 'technicien',
            password: 'password',
            role: 'technicien'
        );

        $this->createOrUpdateUser(
            name: 'Biologiste Test',
            username: 'biologiste',
            email: 'biologiste@labo.com',
            type: 'biologiste',
            password: 'password',
            role: 'biologiste'
        );
    }

    private function createOrUpdateUser(
        string $name,
        string $username,
        string $email,
        string $type,
        string $password,
        string $role
    ): void {
        $user = User::updateOrCreate(
            ['username' => $username], // clé unique
            [
                'name' => $name,
                'email' => $email,
                'type' => $type,
                'password' => Hash::make($password),
            ]
        );

        // Évite duplication des rôles
        if (!$user->hasRole($role)) {
            $user->syncRoles([$role]);
        }
    }
}