<?php

namespace Database\Seeders;

use App\Support\PermissionMap;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // Créer toutes les permissions depuis le mapping centralisé
        foreach (PermissionMap::keys() as $key) {
            Permission::firstOrCreate(['name' => $key, 'guard_name' => 'web']);
        }

        // ── Rôles et leurs permissions ──────────────────────────

        $superadmin = Role::firstOrCreate(['name' => 'superadmin', 'guard_name' => 'web']);
        $superadmin->syncPermissions(Permission::all());

        $admin = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $adminPermissions = Permission::where('name', '!=', 'parametres.gerer')->get();
        $admin->syncPermissions($adminPermissions);

        $secretaire = Role::firstOrCreate(['name' => 'secretaire', 'guard_name' => 'web']);
        $secretaire->syncPermissions([
            'dashboard.voir',
            'prescriptions.voir',
            'prescriptions.creer',
            'prescriptions.modifier',
            'patients.voir',
            'patients.gerer',
            'patients.supprimer',
            'prescripteurs.voir',
            'prescripteurs.gerer',
            'prescripteurs.supprimer',
            'archives.acceder',
        ]);

        $technicien = Role::firstOrCreate(['name' => 'technicien', 'guard_name' => 'web']);
        $technicien->syncPermissions([
            'dashboard.voir',
            'analyses.voir',
            'analyses.effectuer',
            'analyses.conclusion',
            'laboratoire.gerer',
            'archives.acceder',
        ]);

        $biologiste = Role::firstOrCreate(['name' => 'biologiste', 'guard_name' => 'web']);
        $biologiste->syncPermissions([
            'dashboard.voir',
            'analyses.voir',
            'analyses.valider',
            'laboratoire.gerer',
            'archives.acceder',
        ]);
    }
}
