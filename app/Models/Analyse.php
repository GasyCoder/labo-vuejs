<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Analyse extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'code',
        'level',
        'parent_id',
        'designation',
        'description',
        'prix',
        'is_bold',
        'examen_id',
        'type_id',
        'valeur_ref', // valeur de référence temporaire entente de mise à jour

        // nouveaux champs pour valeurs de référence spécifiques
        'valeur_ref_homme',
        'valeur_ref_femme',
        'valeur_ref_enfant_garcon',
        'valeur_ref_enfant_fille',

        'unite',
        'suffixe',
        'valeurs_predefinies',
        'ordre',
        'status',
    ];

    protected $casts = [
        'prix' => 'decimal:2',
        'is_bold' => 'boolean',
        'status' => 'boolean',
        'valeurs_predefinies' => 'array',
        'ordre' => 'integer',
    ];

    // Relations hiérarchie
    public function parent()
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function enfants()
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('ordre')->orderBy('id');
    }

    // Récursion profonde
    public function enfantsRecursive()
    {
        return $this->enfants()->with(['type', 'examen', 'enfantsRecursive']);
    }

    // Relations annexes
    public function examen()
    {
        return $this->belongsTo(Examen::class, 'examen_id');
    }

    public function type()
    {
        return $this->belongsTo(Type::class, 'type_id');
    }

    public function resultats()
    {
        return $this->hasMany(Resultat::class);
    }

    public function ranges(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(AnalyseRange::class);
    }

    /**
     * Valide une valeur par rapport aux bornes configurées.
     */
    public function validateValue($value, $patient = null): array
    {
        if ($value === null || $value === '') {
            return ['status' => 'OK'];
        }

        $cleanValue = str_replace([' ', ','], ['', '.'], $value);
        if (! is_numeric($cleanValue)) {
            return ['status' => 'OK'];
        }

        $numValue = (float) $cleanValue;
        $context = null;

        if ($patient && $patient->civilite) {
            $civilite = strtolower(trim($patient->civilite));
            if (in_array($civilite, ['monsieur', 'mr', 'm.', 'homme'])) { $context = 'HOMME'; }
            elseif (in_array($civilite, ['madame', 'mme', 'mme.', 'femme'])) { $context = 'FEMME'; }
            elseif (str_contains($civilite, 'garçon') || str_contains($civilite, 'garcon')) { $context = 'ENFANT_GARCON'; }
            elseif (str_contains($civilite, 'fille')) { $context = 'ENFANT_FILLE'; }
        }

        if (! $context) return ['status' => 'OK'];

        $range = $this->ranges()->where('context', $context)->first();
        if (! $range) return ['status' => 'OK'];

        // 1. EXTRACTION DES SEUILS
        $nMin = $range->normal_min !== null ? (float)$range->normal_min : null;
        $nMax = $range->normal_max !== null ? (float)$range->normal_max : null;
        $cMin = $range->critical_min !== null ? (float)$range->critical_min : null;
        $cMax = $range->critical_max !== null ? (float)$range->critical_max : null;

        // 2. PRIORITÉ ABSOLUE : CAS NORMAL (Inclusif)
        // Si la valeur est dans la norme, on arrête tout : c'est OK.
        $isAboveNormalMin = ($nMin === null || $numValue >= $nMin);
        $isBelowNormalMax = ($nMax === null || $numValue <= $nMax);

        if ($isAboveNormalMin && $isBelowNormalMax) {
            return ['status' => 'OK', 'message' => 'Valeur normale'];
        }

        // 3. CAS CRITIQUE / IMPOSSIBLE
        // On ne vérifie le critique QUE si on n'est pas dans la norme.
        if (($cMin !== null && $numValue < $cMin) || ($cMax !== null && $numValue > $cMax)) {
            return [
                'status' => 'BLOCK',
                'message' => "VALEUR BIOLOGIQUEMENT INCOHÉRENTE",
                'expected' => "Limites vitales: " . ($cMin ?? '—') . " à " . ($cMax ?? '—'),
                'entered' => $value
            ];
        }

        // 4. CAS HORS NORME MAIS ACCEPTABLE (WARNING)
        return [
            'status' => 'WARNING',
            'message' => "VALEUR HORS PLAGE NORMALE",
            'expected' => "Plage normale: " . ($nMin ?? '—') . " à " . ($nMax ?? '—'),
            'entered' => $value
        ];
    }

    // Scopes utiles
    public function scopeActives($q)
    {
        return $q->where('status', true);
    }

    public function scopeParents($q)
    {
        return $q->where('level', 'PARENT');
    }

    public function scopeNormales($q)
    {
        return $q->where('level', 'NORMAL');
    }

    public function scopeEnfants($q)
    {
        return $q->where('level', 'CHILD');
    }

    public function scopeRacines($q)
    {
        return $q->whereNull('parent_id')->orWhere('level', 'PARENT');
    }

    // Accessors
    public function getValeurCompleteAttribute()
    {
        $valeur = $this->getValeurReferenceByPatient();

        if ($valeur && $this->unite) {
            return $valeur.' '.$this->unite;
        }

        return $valeur;
    }

    public function getValeurHommeCompleteAttribute()
    {
        if ($this->valeur_ref_homme && $this->unite) {
            return $this->valeur_ref_homme.' '.$this->unite;
        }

        return $this->valeur_ref_homme;
    }

    public function getValeurFemmeCompleteAttribute()
    {
        if ($this->valeur_ref_femme && $this->unite) {
            return $this->valeur_ref_femme.' '.$this->unite;
        }

        return $this->valeur_ref_femme;
    }

    public function getValeurEnfantGarconCompleteAttribute()
    {
        if ($this->valeur_ref_enfant_garcon && $this->unite) {
            return $this->valeur_ref_enfant_garcon.' '.$this->unite;
        }

        return $this->valeur_ref_enfant_garcon;
    }

    public function getValeurEnfantFilleCompleteAttribute()
    {
        if ($this->valeur_ref_enfant_fille && $this->unite) {
            return $this->valeur_ref_enfant_fille.' '.$this->unite;
        }

        return $this->valeur_ref_enfant_fille;
    }

    public function getEstParentAttribute()
    {
        return $this->level === 'PARENT';
    }

    public function getADesEnfantsAttribute()
    {
        return $this->enfants()->exists();
    }

    // Accesseur pour formatted_results
    public function getFormattedResultsAttribute()
    {
        if (! $this->valeurs_predefinies || ! is_array($this->valeurs_predefinies)) {
            return [];
        }

        return $this->valeurs_predefinies;
    }

    // Accesseur pour result_disponible (compatibilité ancien code)
    public function getResultDisponibleAttribute()
    {
        return [
            'val_ref_homme' => $this->valeur_ref_homme,
            'val_ref_femme' => $this->valeur_ref_femme,
            'unite' => $this->unite,
            'suffixe' => $this->suffixe,
        ];
    }

    // Méthodes utilitaires
    public function getPrixFormate()
    {
        return number_format($this->prix, 0, ',', ' ').' Ar';
    }

    public function getPrixTotalAttribute()
    {
        if ($this->level === 'PARENT' && $this->enfants->count() > 0) {
            return $this->enfants->sum('prix');
        }

        return $this->prix;
    }

    public function descendantsIds(): array
    {
        $ids = [];
        $stack = [$this->loadMissing('enfants')];
        while ($node = array_pop($stack)) {
            foreach ($node->enfants as $child) {
                $ids[] = $child->id;
                $stack[] = $child->loadMissing('enfants');
            }
        }

        return $ids;
    }

    public function children()
    {
        return $this->enfantsRecursive();
    }

    // Nouvelle méthode pour calcul récursif du prix
    public function getPrixRecursifAttribute()
    {
        if ($this->level !== 'PARENT') {
            return $this->prix;
        }

        $total = 0;
        foreach ($this->enfants as $enfant) {
            $total += $enfant->prix_recursif;
        }

        return $total;
    }

    public function getFullRangesByPatient($patient = null)
    {
        if (! $patient) return null;

        $civilite = strtolower(trim($patient->civilite ?? ''));
        $context = null;

        if (in_array($civilite, ['monsieur', 'mr', 'm.', 'homme'])) {
            $context = 'HOMME';
        } elseif (in_array($civilite, ['madame', 'mme', 'mme.', 'femme'])) {
            $context = 'FEMME';
        } elseif (str_contains($civilite, 'garçon') || str_contains($civilite, 'garcon')) {
            $context = 'ENFANT_GARCON';
        } elseif (str_contains($civilite, 'fille')) {
            $context = 'ENFANT_FILLE';
        }

        if (! $context) return null;

        $range = $this->ranges()->where('context', $context)->first();
        
        if ($range) {
            // On convertit en float pour nettoyer les 4.000 en 4
            return [
                'normal_min' => $range->normal_min !== null ? (float)$range->normal_min : null,
                'normal_max' => $range->normal_max !== null ? (float)$range->normal_max : null,
                'critical_min' => $range->critical_min !== null ? (float)$range->critical_min : null,
                'critical_max' => $range->critical_max !== null ? (float)$range->critical_max : null,
            ];
        }

        return null;
    }

    /**
     * Obtenir la valeur de référence selon le genre/civilité du patient (via AnalyseRange)
     */
    public function getValeurReferenceByPatient($patient = null)
    {
        $civilite = strtolower(trim($patient->civilite ?? ''));
        $context = null;

        if (in_array($civilite, ['monsieur', 'mr', 'm.', 'homme'])) {
            $context = 'HOMME';
        } elseif (in_array($civilite, ['madame', 'mme', 'mme.', 'femme'])) {
            $context = 'FEMME';
        } elseif (str_contains($civilite, 'garçon') || str_contains($civilite, 'garcon')) {
            $context = 'ENFANT_GARCON';
        } elseif (str_contains($civilite, 'fille')) {
            $context = 'ENFANT_FILLE';
        }

        // 1. Essayer la nouvelle table AnalyseRange
        if ($context) {
            $range = $this->ranges()->where('context', $context)->first();
            if ($range) {
                if ($range->normal_min !== null && $range->normal_max !== null) {
                    return (float)$range->normal_min . ' - ' . (float)$range->normal_max;
                }
                if ($range->normal_min !== null) return '> ' . (float)$range->normal_min;
                if ($range->normal_max !== null) return '< ' . (float)$range->normal_max;
            }
        }

        // 2. Fallback sur les anciennes colonnes si rien dans la nouvelle table
        $field = match($context) {
            'HOMME' => 'valeur_ref_homme',
            'FEMME' => 'valeur_ref_femme',
            'ENFANT_GARCON' => 'valeur_ref_enfant_garcon',
            'ENFANT_FILLE' => 'valeur_ref_enfant_fille',
            default => 'valeur_ref_homme'
        };

        return $this->$field ?? $this->valeur_ref_homme;
    }

    /**
     * Obtenir le label de la valeur de référence selon le patient
     */
    public function getLabelValeurReferenceByPatient($patient = null)
    {
        return 'Norme';
    }

    /**
     * Obtenir la valeur de référence complète avec l'unité selon le patient
     */
    public function getValeurReferenceCompleteByPatient($patient = null)
    {
        $valeur = $this->getValeurReferenceByPatient($patient);

        if ($valeur && $this->unite) {
            return $valeur.' '.$this->unite;
        }

        return $valeur;
    }
}
