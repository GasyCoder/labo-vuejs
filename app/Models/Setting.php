<?php

namespace App\Models;

use App\Events\CommissionPourcentageChanged;
use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $table = 'settings';

    protected $fillable = [
        'nom_entreprise',
        'nif',
        'statut',
        'remise_pourcentage',
        'activer_remise',
        'format_unite_argent',
        'commission_prescripteur',
        'commission_prescripteur_pourcentage',
        'commission_prescripteur_quota',
        'tarif_urgence_nuit',
        'tarif_urgence_jour',
        'logo',        // AJOUTER CETTE LIGNE
        'favicon',     // AJOUTER CETTE LIGNE
    ];

    protected $casts = [
        'remise_pourcentage' => 'float',
        'activer_remise' => 'boolean',
        'commission_prescripteur' => 'boolean',
        'commission_prescripteur_pourcentage' => 'float',
        'commission_prescripteur_quota' => 'float',
        'tarif_urgence_nuit' => 'float',
        'tarif_urgence_jour' => 'float',
    ];

    public function defaultPaymentMethod()
    {
        return $this->belongsTo(PaymentMethod::class, 'default_payment_method_id');
    }

    // DÉCLENCHEMENT AUTOMATIQUE DU RECALCUL ET DU VIDAGE DE CACHE
    protected static function boot()
    {
        parent::boot();

        static::saved(function ($setting) {
            // Vider le cache des paramètres globaux
            cache()->forget('app_settings');
        });

        static::updating(function ($setting) {
            // Vérifier si le pourcentage de commission a changé
            if ($setting->isDirty('commission_prescripteur_pourcentage')) {
                $ancienPourcentage = $setting->getOriginal('commission_prescripteur_pourcentage');
                $nouveauPourcentage = $setting->commission_prescripteur_pourcentage;

                // Déclencher l'event après la sauvegarde
                static::saved(function () use ($ancienPourcentage, $nouveauPourcentage) {
                    event(new CommissionPourcentageChanged($ancienPourcentage, $nouveauPourcentage));
                });
            }
        });
    }

    /**
     * Récupérer le pourcentage de commission actuel
     */
    public static function getCommissionPourcentage()
    {
        $setting = static::first();

        if (! $setting || ! $setting->commission_prescripteur) {
            return 0;
        }

        return (float) $setting->commission_prescripteur_pourcentage;
    }

    /**
     * Récupérer le quota de commission actuel par défaut
     */
    public static function getCommissionQuota()
    {
        $setting = static::first();

        if (! $setting || ! $setting->commission_prescripteur) {
            return 0;
        }

        return (float) $setting->commission_prescripteur_quota;
    }

    public static function getSettings()
    {
        return cache()->remember('app_settings', 3600, function () {
            return self::first();
        });
    }

    public static function getLogo()
    {
        $settings = self::getSettings();

        return $settings && $settings->logo
            ? asset('storage/'.$settings->logo)
            : asset('assets/images/logo_facture.jpg');
    }

    public static function getNomEntreprise()
    {
        $settings = self::getSettings();

        return $settings ? $settings->nom_entreprise : 'CTB NOSY BE';
    }

    public static function getFavicon()
    {
        $settings = self::getSettings();

        return $settings && $settings->favicon
            ? asset('storage/'.$settings->favicon)
            : asset('favicon.ico');
    }

    /**
     * Récupérer le tarif d'urgence nuit
     */
    public static function getTarifUrgenceNuit()
    {
        $setting = static::first();

        return $setting ? (float) $setting->tarif_urgence_nuit : 20000;
    }

    /**
     * Récupérer le tarif d'urgence jour
     */
    public static function getTarifUrgenceJour()
    {
        $setting = static::first();

        return $setting ? (float) $setting->tarif_urgence_jour : 15000;
    }
}
