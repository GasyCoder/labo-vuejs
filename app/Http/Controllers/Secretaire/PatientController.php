<?php

namespace App\Http\Controllers\Secretaire;

use App\Http\Controllers\Controller;
use App\Mail\PatientInvoiceMail;
use App\Models\Patient;
use App\Models\Prescription;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Inertia\Inertia;
use Inertia\Response;

class PatientController extends Controller
{
    public function index(Request $request): Response
    {
        $search = $request->input('search', '');
        $civiliteFilter = $request->input('civiliteFilter', '');
        $statutFilter = $request->input('statutFilter', '');
        $perPage = $request->input('perPage', 10);
        $sortField = $request->input('sortField', 'created_at');
        $sortDirection = $request->input('sortDirection', 'desc');

        $allowedSortFields = ['nom', 'created_at', 'numero_dossier'];
        if (! in_array($sortField, $allowedSortFields)) {
            $sortField = 'created_at';
        }

        $patients = Patient::query()
            ->withCount('prescriptions')
            ->when($search, function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('nom', 'like', "%{$search}%")
                        ->orWhere('prenom', 'like', "%{$search}%")
                        ->orWhere('telephone', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('numero_dossier', 'like', "%{$search}%");
                });
            })
            ->when($civiliteFilter, function ($query) use ($civiliteFilter) {
                $query->where('civilite', $civiliteFilter);
            })
            ->when($statutFilter, function ($query) use ($statutFilter) {
                $query->where('statut', $statutFilter);
            })
            ->orderBy($sortField, $sortDirection)
            ->paginate($perPage)
            ->withQueryString();

        $statistiques = [
            'totalPatients' => Patient::count(),
            'patientsNouveaux' => Patient::where('statut', 'NOUVEAU')->count(),
            'patientsFideles' => Patient::where('statut', 'FIDELE')->count(),
            'patientsVip' => Patient::where('statut', 'VIP')->count(),
        ];

        return Inertia::render('Secretaire/Patients/Index', array_merge([
            'patients' => $patients,
            'filters' => [
                'search' => $search,
                'civiliteFilter' => $civiliteFilter,
                'statutFilter' => $statutFilter,
                'perPage' => $perPage,
                'sortField' => $sortField,
                'sortDirection' => $sortDirection,
            ],
        ], $statistiques));
    }

    public function show(Patient $patient): Response
    {
        $patient->load([
            'prescriptions' => function ($query) {
                $query->with([
                    'prescripteur:id,nom,prenom',
                    'analyses' => function ($subQuery) {
                        $subQuery->select('analyses.id', 'analyses.designation', 'analyses.code', 'analyses.prix', 'analyses.parent_id')
                            ->with('parent:id,designation');
                    },
                    'paiements' => function ($subQuery) {
                        $subQuery->select('id', 'prescription_id', 'montant', 'created_at')
                            ->with('paymentMethod:id,code,label');
                    },
                ])->latest();
            },
        ]);

        $prescriptions = $patient->prescriptions;
        $totalAnalyses = $prescriptions->count();
        $totalPaiements = $prescriptions->flatMap->paiements->count();
        $montantTotal = $prescriptions->flatMap->paiements->sum('montant');
        $prescriptionsEnAttente = $prescriptions->where('status', 'EN_ATTENTE')->count();
        $prescriptionsEnCours = $prescriptions->where('status', 'EN_COURS')->count();
        $prescriptionsTerminees = $prescriptions->where('status', 'TERMINE')->count();

        // Transform prescriptions for frontend
        $prescriptionsData = $prescriptions->map(function ($prescription) {
            return [
                'id' => $prescription->id,
                'reference' => $prescription->reference,
                'status' => $prescription->status,
                'status_label' => match ($prescription->status) {
                    'EN_ATTENTE' => 'En attente',
                    'EN_COURS' => 'En cours',
                    'TERMINE' => 'Terminée',
                    'VALIDE' => 'Validée',
                    default => $prescription->status,
                },
                'montant_total' => $prescription->montant_total ?? 0,
                'created_at' => $prescription->created_at->toISOString(),
                'prescripteur' => $prescription->prescripteur ? [
                    'id' => $prescription->prescripteur->id,
                    'nom' => $prescription->prescripteur->nom,
                    'prenom' => $prescription->prescripteur->prenom,
                ] : null,
                'analyses' => $prescription->analyses->map(function ($analyse) {
                    return [
                        'id' => $analyse->id,
                        'designation' => $analyse->designation,
                        'code' => $analyse->code,
                        'prix' => $analyse->prix,
                        'parent' => $analyse->parent ? ['id' => $analyse->parent->id, 'designation' => $analyse->parent->designation] : null,
                    ];
                }),
                'paiements' => $prescription->paiements->map(function ($paiement) use ($prescription) {
                    return [
                        'id' => $paiement->id,
                        'montant' => $paiement->montant,
                        'created_at' => $paiement->created_at->toISOString(),
                        'payment_method' => $paiement->paymentMethod ? [
                            'code' => $paiement->paymentMethod->code,
                            'label' => $paiement->paymentMethod->label,
                        ] : null,
                        'prescription_id' => $paiement->prescription_id,
                        'prescription_reference' => $prescription->reference,
                    ];
                }),
            ];
        });

        // Patient data
        $patientData = [
            'id' => $patient->id,
            'nom' => $patient->nom,
            'prenom' => $patient->prenom,
            'civilite' => $patient->civilite,
            'numero_dossier' => $patient->numero_dossier,
            'telephone' => $patient->telephone,
            'email' => $patient->email,
            'adresse' => $patient->adresse,
            'date_naissance' => $patient->date_naissance?->format('Y-m-d'),
            'date_naissance_formatee' => $patient->date_naissance?->format('d/m/Y'),
            'created_at' => $patient->created_at->toISOString(),
            'created_at_formatted' => $patient->created_at->format('d/m/Y à H:i'),
        ];

        return Inertia::render('Secretaire/Patients/Show', [
            'patient' => $patientData,
            'prescriptions' => $prescriptionsData,
            'totalAnalyses' => $totalAnalyses,
            'totalPaiements' => $totalPaiements,
            'montantTotal' => $montantTotal,
            'prescriptionsEnAttente' => $prescriptionsEnAttente,
            'prescriptionsEnCours' => $prescriptionsEnCours,
            'prescriptionsTerminees' => $prescriptionsTerminees,
        ]);
    }

    public function update(Request $request, Patient $patient): RedirectResponse
    {
        $validated = $request->validate([
            'nom' => 'required|string|max:255',
            'prenom' => 'nullable|string|max:255',
            'civilite' => 'required|string',
            'date_naissance' => 'nullable|date',
            'telephone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'adresse' => 'nullable|string|max:500',
        ]);

        $patient->update([
            'nom' => $validated['nom'],
            'prenom' => $validated['prenom'] ?? null,
            'civilite' => $validated['civilite'],
            'date_naissance' => $validated['date_naissance'] ?: null,
            'telephone' => $validated['telephone'] ?? null,
            'email' => $validated['email'] ?? null,
            'adresse' => $validated['adresse'] ?? null,
        ]);

        return redirect()
            ->route('secretaire.patient.detail', ['patient' => $patient->id])
            ->with('success', 'Patient mis à jour avec succès.');
    }

    public function destroy(Patient $patient): RedirectResponse
    {
        // On supprime les prescriptions associées (qui devraient supprimer leurs analyses/paiements via cascade ou manuellement si nécessaire)
        // Mais ici on utilise le soft delete ou force delete selon la config du modèle
        $patient->delete();

        return redirect()
            ->route('secretaire.patients.index')
            ->with('success', 'Le patient a été supprimé avec succès.');
    }

    public function sendInvoice(Request $request, Prescription $prescription): RedirectResponse
    {
        if (! app(\App\Services\FeatureService::class)->isEnabledForCurrentUser('patient_invoice_email')) {
            return back()->with('error', 'L\'envoi de facture par email n\'est pas activé sur ce compte.');
        }

        $prescription->load(['patient', 'prescripteur', 'analyses', 'prelevements', 'paiements.paymentMethod', 'secretaire']);

        if (! $prescription->patient->email) {
            return back()->with('error', 'Le patient n\'a pas d\'adresse email renseignée.');
        }

        Mail::to($prescription->patient->email)->queue(new PatientInvoiceMail($prescription));

        return back()->with('success', 'La facture a été mise en file d\'attente et sera envoyée au patient par email.');
    }
}
