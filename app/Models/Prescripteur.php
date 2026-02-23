<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Prescripteur extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'nom',
        'prenom',
        'grade',
        'status',
        'telephone',
        'is_active',
        'notes',
        'commission_quota',
        'commission_pourcentage',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'commission_quota' => 'decimal:2',
        'commission_pourcentage' => 'decimal:2',
    ];

    protected static function boot()
    {
        parent::boot();

        static::updated(function ($prescripteur) {
            // Si le quota ou le pourcentage change, recalculer les commissions du mois en cours
            if ($prescripteur->isDirty('commission_quota') || $prescripteur->isDirty('commission_pourcentage')) {
                Paiement::recalculerPourPrescripteur($prescripteur, now());
            }
        });
    }

    // Scopes
    public function scopeActifs($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeCommissionnables($query)
    {
        return $query->whereIn('status', ['Medecin', 'Professeur']);
    }

    // Relations
    public function prescriptions()
    {
        return $this->hasMany(Prescription::class, 'prescripteur_id');
    }

    /**
     * Calculer le total des analyses brutes du prescripteur pour un mois donné
     */
    public function getBruteAnalysesMensuel($date = null)
    {
        $date = $date ? \Carbon\Carbon::parse($date) : now();

        return $this->prescriptions()
            ->whereYear('created_at', $date->year)
            ->whereMonth('created_at', $date->month)
            ->get()
            ->sum(function ($p) {
                return $p->getMontantAnalysesCalcule();
            });
    }

    /**
     * Vérifier si le quota mensuel est atteint pour une date donnée
     */
    public function isQuotaAtteint($date = null)
    {
        return $this->getBruteAnalysesMensuel($date) >= $this->commission_quota;
    }

    public function getStatistiquesCommissions($dateDebut = null, $dateFin = null)
    {
        $query = $this->prescriptions()->whereHas('paiements');

        if ($dateDebut && $dateFin && $dateDebut !== '' && $dateFin !== '') {
            $query->whereBetween('created_at', [
                \Carbon\Carbon::parse($dateDebut)->startOfDay(),
                \Carbon\Carbon::parse($dateFin)->endOfDay(),
            ]);
        }

        $prescriptions = $query->with('paiements')->get();

        return [
            'total_prescriptions' => $prescriptions->count(),
            'montant_total_analyses' => $prescriptions->sum(function ($p) {
                return $p->getMontantAnalysesCalcule();
            }),
            'montant_total_paye' => $prescriptions->sum(function ($p) {
                return $p->paiements->sum('montant');
            }),
            'total_commission' => $prescriptions->sum(function ($p) {
                return $p->paiements->sum('commission_prescripteur');
            }),
            'commission_moyenne' => $prescriptions->count() > 0 ? $prescriptions->sum(function ($p) {
                return $p->paiements->sum('commission_prescripteur');
            }) / $prescriptions->count() : 0,
        ];
    }

    public function getCommissionsParMois($annee = null, $dateDebut = null, $dateFin = null)
    {
        $query = $this->prescriptions()->whereHas('paiements');

        if ($dateDebut && $dateFin && $dateDebut !== '' && $dateFin !== '') {
            $query->whereBetween('created_at', [
                \Carbon\Carbon::parse($dateDebut)->startOfDay(),
                \Carbon\Carbon::parse($dateFin)->endOfDay(),
            ]);
        } elseif ($annee) {
            $query->whereYear('created_at', $annee);
        }

        // Charger la relation avec le patient
        $prescriptions = $query->with(['paiements', 'patient'])->get();

        if ($prescriptions->isEmpty()) {
            return collect([]);
        }

        $prescriptionsParMois = $prescriptions->groupBy(function ($prescription) {
            return $prescription->created_at->month;
        });

        $results = collect();
        foreach ($prescriptionsParMois as $mois => $prescriptionsDuMois) {
            $bruteTotal = $prescriptionsDuMois->sum(function ($p) {
                return $p->getMontantAnalysesCalcule();
            });
            $isQuotaAtteint = $bruteTotal >= $this->commission_quota;

            $results->push((object) [
                'mois' => $mois,
                'nombre_prescriptions' => $prescriptionsDuMois->count(),
                'montant_analyses' => $bruteTotal,
                'montant_paye' => $prescriptionsDuMois->sum(function ($p) {
                    return $p->paiements->sum('montant');
                }),
                'commission' => $prescriptionsDuMois->sum(function ($p) {
                    return $p->paiements->sum('commission_prescripteur');
                }),
                'quota_atteint' => $isQuotaAtteint,
                'quota_montant' => $this->commission_quota,
                // Ajouter les détails des prescriptions avec les informations du patient
                'prescriptions' => $prescriptionsDuMois->map(function ($prescription) {
                    return (object) [
                        'id' => $prescription->id,
                        'patient_nom_complet' => $prescription->patient ? $prescription->patient->nom_complet : 'Patient inconnu',
                        'patient_numero_dossier' => $prescription->patient ? $prescription->patient->numero_dossier : 'N/A',
                        'montant_analyses' => $prescription->getMontantAnalysesCalcule(),
                        'montant_paye' => $prescription->paiements->sum('montant'),
                        'commission' => $prescription->paiements->sum('commission_prescripteur'),
                        'date' => $prescription->created_at->format('d/m/Y'),
                    ];
                }),
            ]);
        }

        return $results->sortBy('mois')->values();
    }

    // Accesseurs
    public function getNomCompletAttribute()
    {
        $grade = $this->grade ? $this->grade.' ' : '';
        $prenom = $this->prenom ? $this->prenom.' ' : '';

        return trim($grade.$prenom.$this->nom);
    }

    public function getNomSimpleAttribute()
    {
        return trim(($this->prenom ? $this->prenom.' ' : '').$this->nom);
    }

    public function getEstCommissionnableAttribute()
    {
        return in_array($this->status, ['Medecin', 'Professeur'], true);
    }

    // Méthodes statiques
    public static function getGradesDisponibles()
    {
        return [
            'Dr' => 'Docteur',
            'Pr' => 'Professeur',
            'Infirmier(e)' => 'Infirmier(e)',
            'Sage-femme' => 'Sage-femme',
        ];
    }

    public static function getStatusDisponibles()
    {
        return [
            'Medecin' => 'Médecin',
            'Professeur' => 'Professeur',
        ];
    }
}
