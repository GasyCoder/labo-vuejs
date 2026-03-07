<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Nettoyage optionnel si besoin pour éviter les vieux doublons de développement
        // User::where('username', 'secretaire')->delete();

        // Utilisateurs principaux
        $this->createOrUpdateUser(
            name: 'Administrateur Principal',
            username: 'adminlabo',
            email: 'admin@labo.com',
            type: 'superadmin',
            password: 'adminlabo',
            role: 'superadmin'
        );

        $this->createOrUpdateUser(
            name: 'Faniriantsoa RAHARIMALALA',
            username: 'hari',
            email: 'hari@labo.com', // Unique pour éviter conflit avec l'ancien 'secretaire'
            type: 'secretaire',
            password: '12345678',
            role: 'secretaire'
        );

        $this->createOrUpdateUser(
            name: 'Rodrigue RAKOTONDRAMANANA',
            username: 'rodrigue',
            email: 'rodrigue@labo.com',
            type: 'technicien',
            password: '12345678',
            role: 'technicien'
        );

        $this->createOrUpdateUser(
            name: 'Ruffin RAKOTONDRAMANANA',
            username: 'ruffin',
            email: 'ruffin@labo.com',
            type: 'technicien',
            password: '12345678',
            role: 'technicien'
        );

        $this->createOrUpdateUser(
            name: 'RAKOTOMALALA Rivo',
            username: 'rivo',
            email: 'rivo@labo.com',
            type: 'biologiste',
            password: '12345678',
            role: 'biologiste'
        );

        $this->createOrUpdateUser(
            name: 'testsec',
            username: 'testsec',
            email: 'testsec@labo.com',
            type: 'secretaire',
            password: '12345678',
            role: 'secretaire'
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
        // On cherche par username en priorité
        $user = User::where('username', $username)->first();

        if (! $user) {
            // Si le username n'existe pas, on vérifie si l'email est déjà pris par un autre user
            $existingWithEmail = User::where('email', $email)->first();
            if ($existingWithEmail) {
                // On met à jour l'user qui a cet email pour qu'il devienne notre nouvel user
                $user = $existingWithEmail;
            } else {
                // Sinon on crée un nouveau
                $user = new User;
            }
        }

        $user->fill([
            'name' => $name,
            'username' => $username,
            'email' => $email,
            'type' => $type,
            'password' => Hash::make($password),
            'email_verified_at' => now(),
        ])->save();

        // Assigner le rôle Spatie
        if (! $user->hasRole($role)) {
            $user->syncRoles([$role]);
        }
    }
}
