<?php

namespace App\Support;

class PermissionMap
{
    /**
     * Mapping centralisé : clé permission => métadonnées
     * Format: 'ressource.action' => [label, module, description]
     *
     * POUR AJOUTER UN NOUVEAU MODULE / PERMISSION :
     * 1. Ajouter l'entrée ici dans PERMISSIONS
     * 2. Lancer: php artisan db:seed --class=PermissionSeeder
     * 3. Assigner aux rôles dans PermissionSeeder
     */
    public const PERMISSIONS = [
        // ── Tableau de Bord ───────────────────────────────────
        'dashboard.voir' => [
            'label' => 'Voir le tableau de bord',
            'module' => 'Tableau de bord',
            'description' => 'Consulter les statistiques et indicateurs clés de performance',
        ],

        // ── Prescriptions ──────────────────────────────────────
        'prescriptions.voir' => [
            'label' => 'Voir les prescriptions',
            'module' => 'Prescriptions',
            'description' => 'Consulter la liste et les détails des prescriptions',
        ],
        'prescriptions.creer' => [
            'label' => 'Créer des prescriptions',
            'module' => 'Prescriptions',
            'description' => 'Enregistrer de nouvelles prescriptions dans le système',
        ],
        'prescriptions.modifier' => [
            'label' => 'Modifier des prescriptions',
            'module' => 'Prescriptions',
            'description' => 'Éditer les prescriptions existantes',
        ],
        'prescriptions.supprimer' => [
            'label' => 'Supprimer des prescriptions',
            'module' => 'Prescriptions',
            'description' => 'Mettre en corbeille ou supprimer des prescriptions',
        ],

        // ── Analyses ───────────────────────────────────────────
        'analyses.voir' => [
            'label' => 'Voir les analyses',
            'module' => 'Analyses',
            'description' => 'Consulter les analyses et leurs résultats',
        ],
        'analyses.effectuer' => [
            'label' => 'Effectuer des analyses',
            'module' => 'Analyses',
            'description' => 'Réaliser les analyses et saisir les résultats',
        ],
        'analyses.valider' => [
            'label' => 'Valider les résultats',
            'module' => 'Analyses',
            'description' => 'Valider biologiquement les résultats d\'analyses',
        ],
        'analyses.conclusion' => [
            'label' => 'Gérer les conclusions',
            'module' => 'Analyses',
            'description' => 'Ajouter, modifier ou supprimer des conclusions d\'analyses',
        ],

        // ── Patients ───────────────────────────────────────────
        'patients.voir' => [
            'label' => 'Voir les patients',
            'module' => 'Patients',
            'description' => 'Consulter la liste et les dossiers patients',
        ],
        'patients.gerer' => [
            'label' => 'Gérer les patients',
            'module' => 'Patients',
            'description' => 'Créer et modifier des patients',
        ],
        'patients.supprimer' => [
            'label' => 'Supprimer des patients',
            'module' => 'Patients',
            'description' => 'Mettre en corbeille ou supprimer définitivement des patients',
        ],

        // ── Prescripteurs ──────────────────────────────────────
        'prescripteurs.voir' => [
            'label' => 'Voir les prescripteurs',
            'module' => 'Prescripteurs',
            'description' => 'Consulter la liste des médecins prescripteurs',
        ],
        'prescripteurs.gerer' => [
            'label' => 'Gérer les prescripteurs',
            'module' => 'Prescripteurs',
            'description' => 'Créer et modifier des prescripteurs',
        ],
        'prescripteurs.supprimer' => [
            'label' => 'Supprimer des prescripteurs',
            'module' => 'Prescripteurs',
            'description' => 'Mettre en corbeille ou supprimer définitivement des prescripteurs',
        ],

        // ── Laboratoire ────────────────────────────────────────
        'laboratoire.gerer' => [
            'label' => 'Gérer le laboratoire',
            'module' => 'Laboratoire',
            'description' => 'Administrer les examens, types d\'analyses et prélèvements',
        ],

        // ── Administration ─────────────────────────────────────
        'utilisateurs.gerer' => [
            'label' => 'Gérer les utilisateurs',
            'module' => 'Administration',
            'description' => 'Créer, modifier et supprimer des comptes utilisateurs',
        ],
        'parametres.gerer' => [
            'label' => 'Gérer les paramètres',
            'module' => 'Administration',
            'description' => 'Modifier la configuration générale du système',
        ],
        'branding.gerer' => [
            'label' => 'Gérer l\'identité visuelle (PDF)',
            'module' => 'Administration',
            'description' => 'Personnaliser les logos et signatures sur les documents PDF',
        ],
        'corbeille.acceder' => [
            'label' => 'Accéder à la corbeille',
            'module' => 'Administration',
            'description' => 'Restaurer ou supprimer définitivement des éléments',
        ],

        // ── Archives ───────────────────────────────────────────
        'archives.acceder' => [
            'label' => 'Accéder aux archives',
            'module' => 'Archives',
            'description' => 'Consulter et gérer les prescriptions archivées',
        ],
    ];

    /**
     * Mapping ancien (anglais) → nouveau (français)
     */
    public const MIGRATION_MAP = [
        'dashboard.view' => 'dashboard.voir',
        'prescriptions.view' => 'prescriptions.voir',
        'prescriptions.create' => 'prescriptions.creer',
        'prescriptions.edit' => 'prescriptions.modifier',
        'prescriptions.delete' => 'prescriptions.supprimer',
        'analyses.view' => 'analyses.voir',
        'analyses.perform' => 'analyses.effectuer',
        'analyses.validate' => 'analyses.valider',
        'patients.view' => 'patients.voir',
        'patients.manage' => 'patients.gerer',
        'patients.delete' => 'patients.supprimer',
        'prescripteurs.view' => 'prescripteurs.voir',
        'prescripteurs.manage' => 'prescripteurs.gerer',
        'prescripteurs.delete' => 'prescripteurs.supprimer',
        'laboratory.manage' => 'laboratoire.gerer',
        'users.manage' => 'utilisateurs.gerer',
        'settings.manage' => 'parametres.gerer',
        'trash.access' => 'corbeille.acceder',
        'archives.access' => 'archives.acceder',
    ];

    /**
     * Retourne toutes les permissions groupées par module
     */
    public static function grouped(): array
    {
        $grouped = [];
        foreach (self::PERMISSIONS as $key => $meta) {
            $grouped[$meta['module']][$key] = $meta;
        }

        return $grouped;
    }

    /**
     * Retourne les clés de permissions uniquement
     */
    public static function keys(): array
    {
        return array_keys(self::PERMISSIONS);
    }

    /**
     * Retourne le label d'une permission
     */
    public static function label(string $key): string
    {
        return self::PERMISSIONS[$key]['label'] ?? $key;
    }

    /**
     * Retourne le module d'une permission
     */
    public static function module(string $key): string
    {
        return self::PERMISSIONS[$key]['module'] ?? 'Autre';
    }

    /**
     * Retourne la description d'une permission
     */
    public static function description(string $key): string
    {
        return self::PERMISSIONS[$key]['description'] ?? '';
    }
}
