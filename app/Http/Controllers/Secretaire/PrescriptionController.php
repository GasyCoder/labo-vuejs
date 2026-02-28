<?php

namespace App\Http\Controllers\Secretaire;

use App\Http\Controllers\Controller;
use App\Http\Requests\Secretaire\StorePrescriptionRequest;
use App\Models\Analyse;
use App\Models\Paiement;
use App\Models\Patient;
use App\Models\PaymentMethod;
use App\Models\Prelevement;
use App\Models\Prescripteur;
use App\Models\Prescription;
use App\Models\Setting;
use App\Models\Tube;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;

class PrescriptionController extends Controller
{
    public function index(Request $request): Response
    {
        $search = $request->input('search', '');
        $tab = $request->input('tab', 'actives');
        $perPage = $request->integer('perPage', 10);
        $paymentFilter = $request->input('payment', '');

        $allowedTabs = ['actives', 'valide', 'archive', 'deleted'];
        if (! in_array($tab, $allowedTabs, true)) {
            $tab = 'actives';
        }

        $prescriptionsQuery = Prescription::query()
            ->with([
                'patient:id,nom,prenom,telephone,email',
                'prescripteur:id,nom',
                'paiements:id,prescription_id,status,date_paiement',
                'analyses:id,code,designation',
            ])
            ->withCount('analyses')
            ->whereHas('patient', fn ($q) => $q->whereNull('deleted_at'));

        if ($search !== '') {
            $prescriptionsQuery->where(function ($query) use ($search): void {
                $query->where('reference', 'like', "%{$search}%")
                    ->orWhereHas('patient', function ($patientQuery) use ($search): void {
                        $patientQuery->where('nom', 'like', "%{$search}%")
                            ->orWhere('prenom', 'like', "%{$search}%")
                            ->orWhere('telephone', 'like', "%{$search}%");
                    });
            });
        }

        // Payment filter overrides tab/status filters (legacy behavior)
        if ($paymentFilter) {
            match ($paymentFilter) {
                'paye' => $prescriptionsQuery->whereHas('paiements', fn ($q) => $q->where('status', true)),
                'non_paye' => $prescriptionsQuery->whereHas('paiements', fn ($q) => $q->where('status', false)),
                'sans_paiement' => $prescriptionsQuery->doesntHave('paiements'),
                default => null,
            };
        } elseif ($tab === 'deleted') {
            $prescriptionsQuery->onlyTrashed();
        } else {
            $statusMap = [
                'actives' => ['EN_ATTENTE', 'EN_COURS', 'TERMINE', 'A_REFAIRE'],
                'valide' => ['VALIDE'],
                'archive' => ['ARCHIVE'],
            ];

            $prescriptionsQuery->whereIn('status', $statusMap[$tab]);
        }

        $prescriptions = $prescriptionsQuery
            ->latest()
            ->paginate($perPage)
            ->withQueryString()
            ->through(function (Prescription $prescription): array {
                return [
                    'id' => $prescription->id,
                    'reference' => $prescription->reference,
                    'status' => $prescription->status,
                    'status_label' => match ($prescription->status) {
                        'EN_ATTENTE' => 'En attente',
                        'EN_COURS' => 'En cours',
                        'TERMINE' => 'Termine',
                        'VALIDE' => 'Valide',
                        'A_REFAIRE' => 'A refaire',
                        'ARCHIVE' => 'Archive',
                        default => $prescription->status,
                    },
                    'analyses_count' => $prescription->analyses_count,
                    'analyses' => $prescription->analyses->map(fn ($a) => [
                        'code' => $a->code,
                        'designation' => $a->designation,
                    ]),
                    'created_at' => $prescription->created_at?->format('d/m/Y H:i'),
                    'created_at_relative' => $prescription->created_at?->diffForHumans(),
                    'deleted_at' => $prescription->deleted_at?->format('d/m/Y H:i'),
                    'notified_at' => $prescription->notified_at?->format('d/m/Y'),
                    'patient' => $prescription->patient ? [
                        'nom_complet' => trim(sprintf('%s %s', $prescription->patient->nom, $prescription->patient->prenom ?? '')),
                        'telephone' => $prescription->patient->telephone,
                        'email' => $prescription->patient->email,
                    ] : null,
                    'prescripteur' => $prescription->prescripteur ? [
                        'nom' => $prescription->prescripteur->nom,
                    ] : null,
                    'paiement' => $prescription->paiements->first() ? [
                        'status' => (bool) $prescription->paiements->first()->status,
                        'date_paiement' => $prescription->paiements->first()->date_paiement?->format('d/m/Y'),
                    ] : null,
                ];
            });

        $countEnAttente = Prescription::where('status', 'EN_ATTENTE')->count();
        $countEnCours = Prescription::where('status', 'EN_COURS')->count();
        $countTermine = Prescription::where('status', 'TERMINE')->count();
        $countValide = Prescription::where('status', 'VALIDE')->count();
        $countArchive = Prescription::where('status', 'ARCHIVE')->count();
        $countDeleted = Prescription::onlyTrashed()->count();
        $countPaye = Paiement::query()
            ->whereHas('prescription', fn ($q) => $q->whereNull('deleted_at'))
            ->where('status', true)
            ->count();
        $countNonPaye = Paiement::query()
            ->whereHas('prescription', fn ($q) => $q->whereNull('deleted_at'))
            ->where('status', false)
            ->count();

        $user = Auth::user();

        return Inertia::render('Secretaire/Prescriptions/Index', [
            'prescriptions' => $prescriptions,
            'filters' => [
                'search' => $search,
                'tab' => $tab,
                'perPage' => $perPage,
                'payment' => $paymentFilter,
            ],
            'counts' => [
                'actives' => Prescription::whereIn('status', ['EN_ATTENTE', 'EN_COURS', 'TERMINE', 'A_REFAIRE'])->count(),
                'countActives' => $countEnAttente + $countEnCours + $countTermine,
                'valide' => $countValide,
                'archive' => $countArchive,
                'deleted' => $countDeleted,
                'countEnAttente' => $countEnAttente,
                'countEnCours' => $countEnCours,
                'countTermine' => $countTermine,
                'countValide' => $countValide,
                'countArchive' => $countArchive,
                'countDeleted' => $countDeleted,
                'countPaye' => $countPaye,
                'countNonPaye' => $countNonPaye,
            ],
            'permissions' => [
                'canCreate' => $user->hasPermission('prescriptions.creer'),
                'canEdit' => $user->hasPermission('prescriptions.modifier'),
                'canDelete' => $user->hasPermission('prescriptions.supprimer'),
                'canAccessTrash' => $user->hasPermission('corbeille.acceder'),
                'canAccessArchive' => $user->hasPermission('archives.acceder'),
                'canViewPrescription' => $user->hasPermission('prescriptions.voir'),
            ],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Secretaire/Prescriptions/Create', [
            'prescripteurs' => Prescripteur::query()
                ->where('is_active', true)
                ->orderBy('nom')
                ->get(['id', 'nom', 'prenom', 'grade'])
                ->map(function (Prescripteur $prescripteur): array {
                    return [
                        'id' => $prescripteur->id,
                        'nom' => $prescripteur->nom,
                        'prenom' => $prescripteur->prenom,
                        'grade' => $prescripteur->grade,
                        'nom_complet' => $prescripteur->nom_complet,
                    ];
                }),
            'paymentMethods' => PaymentMethod::query()
                ->where('is_active', true)
                ->orderBy('display_order')
                ->get(['id', 'code', 'label']),
            'defaultReference' => (new Prescription)->genererReferenceUnique(),
            'urgenceFees' => [
                'jour' => (float) Setting::getTarifUrgenceJour(),
                'nuit' => (float) Setting::getTarifUrgenceNuit(),
            ],
            'civilites' => Patient::CIVILITES,
        ]);
    }

    public function searchPatients(Request $request): JsonResponse
    {
        $term = trim((string) $request->query('q', ''));

        if (mb_strlen($term) < 2) {
            return response()->json(['data' => []]);
        }

        $patients = Patient::query()
            ->where(function ($query) use ($term): void {
                $query->where('nom', 'like', "%{$term}%")
                    ->orWhere('prenom', 'like', "%{$term}%")
                    ->orWhere('telephone', 'like', "%{$term}%")
                    ->orWhere('numero_dossier', 'like', "%{$term}%");
            })
            ->orderBy('nom')
            ->limit(10)
            ->get();

        return response()->json([
            'data' => $patients->map(function (Patient $patient): array {
                $age = $patient->age_avec_unite;

                return [
                    'id' => $patient->id,
                    'nom' => $patient->nom,
                    'prenom' => $patient->prenom,
                    'nom_complet' => trim(sprintf('%s %s', $patient->nom, $patient->prenom ?? '')),
                    'civilite' => $patient->civilite,
                    'numero_dossier' => $patient->numero_dossier,
                    'telephone' => $patient->telephone,
                    'email' => $patient->email,
                    'adresse' => $patient->adresse,
                    'date_naissance' => $patient->date_naissance?->format('Y-m-d'),
                    'age' => $age['age'] ?? null,
                    'unite_age' => $age['unite'] ?? 'Ans',
                ];
            }),
        ]);
    }

    public function searchAnalyses(Request $request): JsonResponse
    {
        $term = strtoupper(trim((string) $request->query('q', '')));

        if (mb_strlen($term) < 2) {
            return response()->json(['data' => []]);
        }

        $resultats = collect();

        // 1. CHILD/NORMAL with price > 0 that directly match the term
        $childDirectes = Analyse::query()
            ->where('status', true)
            ->whereIn('level', ['CHILD', 'NORMAL'])
            ->where('prix', '>', 0)
            ->where(function ($query) use ($term): void {
                $query->whereRaw('UPPER(code) LIKE ?', ["%{$term}%"])
                    ->orWhereRaw('UPPER(designation) LIKE ?', ["%{$term}%"]);
            })
            ->with('parent:id,designation,prix')
            ->get();

        foreach ($childDirectes as $analyse) {
            $analyse->setAttribute('recherche_directe', true);
        }

        $resultats = $resultats->concat($childDirectes);

        // 2. PARENT with price > 0 matching the term (panels)
        $parentsPayants = Analyse::query()
            ->where('status', true)
            ->where('level', 'PARENT')
            ->where('prix', '>', 0)
            ->where(function ($query) use ($term): void {
                $query->whereRaw('UPPER(code) LIKE ?', ["%{$term}%"])
                    ->orWhereRaw('UPPER(designation) LIKE ?', ["%{$term}%"]);
            })
            ->with(['enfants' => function ($query): void {
                $query->where('status', true);
            }])
            ->get();

        foreach ($parentsPayants as $analyse) {
            $analyse->setAttribute('recherche_directe', false);
        }

        $resultats = $resultats->concat($parentsPayants);

        // 3. Free PARENTs matching term → expand to their paying children
        $parentsGratuits = Analyse::query()
            ->where('status', true)
            ->where('level', 'PARENT')
            ->where(function ($query): void {
                $query->where('prix', 0)->orWhereNull('prix');
            })
            ->where(function ($query) use ($term): void {
                $query->whereRaw('UPPER(code) LIKE ?', ["%{$term}%"])
                    ->orWhereRaw('UPPER(designation) LIKE ?', ["%{$term}%"]);
            })
            ->with(['enfants' => function ($query): void {
                $query->where('status', true);
            }])
            ->get();

        foreach ($parentsGratuits as $parentGratuit) {
            if ($parentGratuit->enfants->count() > 0) {
                $enfantsPayants = $parentGratuit->enfants
                    ->where('prix', '>', 0);

                foreach ($enfantsPayants as $enfant) {
                    $enfant->setAttribute('recherche_directe', false);
                }

                $resultats = $resultats->concat($enfantsPayants);
            }
        }

        // 4. NORMAL orphans (no parent or parent has no price)
        $individuelles = Analyse::query()
            ->where('status', true)
            ->where('level', 'NORMAL')
            ->where('prix', '>', 0)
            ->where(function ($query) use ($term): void {
                $query->whereRaw('UPPER(code) LIKE ?', ["%{$term}%"])
                    ->orWhereRaw('UPPER(designation) LIKE ?', ["%{$term}%"]);
            })
            ->with('parent:id,designation,prix')
            ->get()
            ->filter(function (Analyse $analyse): bool {
                return ! $analyse->parent
                    || ($analyse->parent && (float) $analyse->parent->prix <= 0);
            });

        foreach ($individuelles as $analyse) {
            $analyse->setAttribute('recherche_directe', false);
        }

        $resultats = $resultats->concat($individuelles);

        // Deduplicate and sort by relevance
        $resultatsUniques = $resultats->unique('id')->values();

        $sorted = $resultatsUniques->sortBy([
            function (Analyse $analyse): int {
                return $analyse->getAttribute('recherche_directe') ? 0 : 1;
            },
            function (Analyse $analyse) use ($term): int {
                if (strtoupper((string) $analyse->code) === $term) {
                    return 1;
                }

                if (str_starts_with(strtoupper((string) $analyse->code), $term)) {
                    return 2;
                }

                if (str_contains(strtoupper((string) $analyse->designation), $term)) {
                    return 3;
                }

                return 4;
            },
        ])->take(20);

        return response()->json([
            'data' => $sorted->values()->map(function (Analyse $analyse): array {
                $isParent = $analyse->level === 'PARENT';

                $parentNom = 'Analyse individuelle';
                if ($isParent) {
                    $parentNom = 'Panel complet';
                } elseif ($analyse->parent && (float) $analyse->parent->prix > 0) {
                    $parentNom = $analyse->parent->designation.' (partie)';
                } elseif ($analyse->parent) {
                    $parentNom = $analyse->parent->designation;
                }

                return [
                    'id' => $analyse->id,
                    'code' => $analyse->code,
                    'designation' => $analyse->designation,
                    'level' => $analyse->level,
                    'prix' => (float) $analyse->prix,
                    'parent_id' => $analyse->parent_id,
                    'parent' => $analyse->parent ? [
                        'id' => $analyse->parent->id,
                        'designation' => $analyse->parent->designation,
                        'prix' => (float) $analyse->parent->prix,
                    ] : null,
                    'is_parent' => $isParent,
                    'parent_nom' => $parentNom,
                    'enfants_inclus' => $isParent
                        ? ($analyse->enfants ?? collect())->pluck('designation')->values()
                        : [],
                ];
            }),
        ]);
    }

    public function searchPrelevements(Request $request): JsonResponse
    {
        $term = trim((string) $request->query('q', ''));

        $query = Prelevement::query()
            ->where('is_active', true)
            ->orderBy('denomination');

        if (mb_strlen($term) >= 2) {
            $query->where('denomination', 'like', "%{$term}%");
        }

        $prelevements = $query->limit(20)->get();

        return response()->json([
            'data' => $prelevements->map(function (Prelevement $prelevement): array {
                return [
                    'id' => $prelevement->id,
                    'denomination' => $prelevement->denomination,
                    'prix' => (float) $prelevement->prix,
                    'prix_promotion' => (float) ($prelevement->prix_promotion ?? 0),
                ];
            }),
        ]);
    }

    public function store(StorePrescriptionRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $selectedAnalyseIds = collect($validated['analyse_ids'] ?? [])
            ->map(fn ($id): int => (int) $id)
            ->unique()
            ->values();

        $selectedPrelevements = collect($validated['prelevements'] ?? [])
            ->map(function (array $item): array {
                return [
                    'id' => (int) $item['id'],
                    'quantite' => max(1, (int) $item['quantite']),
                ];
            })
            ->unique('id')
            ->values();

        $prescription = DB::transaction(function () use ($validated, $selectedAnalyseIds, $selectedPrelevements): Prescription {
            $patient = $this->resolveOrCreatePatient($validated);

            $analyses = Analyse::query()
                ->whereIn('id', $selectedAnalyseIds)
                ->with('parent:id,prix')
                ->get()
                ->keyBy('id');

            $prelevements = Prelevement::query()
                ->whereIn('id', $selectedPrelevements->pluck('id'))
                ->get()
                ->keyBy('id');

            $analysesTotal = $this->calculateAnalysesSubtotal($analyses, $selectedAnalyseIds);
            $prelevementsTotal = $this->calculatePrelevementsTotal($prelevements, $selectedPrelevements);
            $servicesTotal = $analysesTotal + $prelevementsTotal;

            $remisePercent = (float) ($validated['remise'] ?? 0);
            $remiseAmount = $servicesTotal * ($remisePercent / 100);

            $urgenceFee = 0.0;
            if (($validated['patient_type'] ?? 'EXTERNE') === 'URGENCE-JOUR') {
                $urgenceFee = (float) Setting::getTarifUrgenceJour();
            }
            if (($validated['patient_type'] ?? 'EXTERNE') === 'URGENCE-NUIT') {
                $urgenceFee = (float) Setting::getTarifUrgenceNuit();
            }

            $total = max(0, ($servicesTotal - $remiseAmount) + $urgenceFee);

            $ageData = $this->calculateAgeData(
                $patient->date_naissance?->format('Y-m-d'),
                $validated['age'] ?? null,
                $validated['unite_age'] ?? null,
            );

            $prescription = Prescription::query()->create([
                'patient_id' => $patient->id,
                'prescripteur_id' => (int) $validated['prescripteur_id'],
                'secretaire_id' => Auth::id(),
                'patient_type' => $validated['patient_type'],
                'age' => $ageData['age'],
                'unite_age' => $ageData['unite_age'],
                'poids' => $validated['poids'] ?? null,
                'renseignement_clinique' => $validated['renseignement_clinique'] ?? null,
                'remise' => $remiseAmount,
                'status' => Prescription::STATUS_EN_ATTENTE,
            ]);

            $prescription->analyses()->sync($selectedAnalyseIds->all());

            $paymentMethod = PaymentMethod::query()
                ->where('code', $validated['payment_method'])
                ->where('is_active', true)
                ->firstOrFail();

            $isPaid = (bool) ($validated['paiement_statut'] ?? true);

            Paiement::query()->create([
                'prescription_id' => $prescription->id,
                'montant' => $total,
                'payment_method_id' => $paymentMethod->id,
                'recu_par' => Auth::id(),
                'status' => $isPaid,
                'date_paiement' => $isPaid ? now() : null,
            ]);

            $tubeCounter = 1;
            foreach ($selectedPrelevements as $item) {
                $prelevement = $prelevements->get($item['id']);
                if (! $prelevement) {
                    continue;
                }

                for ($i = 0; $i < $item['quantite']; $i++) {
                    Tube::query()->create([
                        'prescription_id' => $prescription->id,
                        'patient_id' => $patient->id,
                        'prelevement_id' => $prelevement->id,
                        'code_barre' => $prescription->reference.'-T'.str_pad((string) $tubeCounter, 2, '0', STR_PAD_LEFT),
                    ]);

                    $tubeCounter++;
                }
            }

            return $prescription;
        });

        return redirect()
            ->route('secretaire.prescription.edit', ['prescriptionId' => $prescription->id, 'step' => 'tubes'])
            ->with('success', 'Prescription enregistrée avec succès.')
            ->with('prescription_action', 'created');
    }

    public function edit(int $prescriptionId): Response
    {
        $prescription = Prescription::with([
            'patient',
            'prescripteur:id,nom,prenom',
            'analyses.parent',
            'prelevements',
            'paiements.paymentMethod',
            'tubes',
        ])->findOrFail($prescriptionId);

        return Inertia::render('Secretaire/Prescriptions/Edit', [
            'prescription' => $prescription,
            'wasRecentlyCreated' => $prescription->wasRecentlyCreated,
            'prescripteurs' => Prescripteur::query()->where('is_active', true)->get(['id', 'nom', 'prenom', 'grade']),
            'paymentMethods' => PaymentMethod::query()->where('is_active', true)->get(['id', 'code', 'label']),
            'urgenceFees' => [
                'jour' => Setting::getTarifUrgenceJour(),
                'nuit' => Setting::getTarifUrgenceNuit(),
            ],
            'civilites' => Patient::CIVILITES,
        ]);
    }

    public function destroy(int $id): RedirectResponse
    {
        $user = Auth::user();
        if (! $user->hasPermission('prescriptions.supprimer')) {
            return back()->with('error', 'Vous n\'avez pas la permission de supprimer des prescriptions.');
        }

        try {
            Prescription::findOrFail($id)->delete();

            return back()->with('success', 'Prescription mise en corbeille.');
        } catch (\Exception $e) {
            Log::error('Erreur suppression prescription', ['error' => $e->getMessage()]);

            return back()->with('error', 'Erreur lors de la suppression.');
        }
    }

    public function bulkDestroy(Request $request): RedirectResponse
    {
        $user = Auth::user();
        if (! $user->hasPermission('prescriptions.supprimer')) {
            return back()->with('error', 'Vous n\'avez pas la permission de supprimer des prescriptions.');
        }

        $ids = $request->input('ids', []);
        if (empty($ids)) {
            return back()->with('error', 'Aucune prescription sélectionnée.');
        }

        try {
            Prescription::whereIn('id', $ids)->delete();

            return back()->with('success', count($ids).' prescriptions mises en corbeille.');
        } catch (\Exception $e) {
            Log::error('Erreur suppression groupée prescriptions', ['error' => $e->getMessage()]);

            return back()->with('error', 'Erreur lors de la suppression groupée.');
        }
    }

    public function restore(int $id): RedirectResponse
    {
        $user = Auth::user();
        if (! $user->hasPermission('corbeille.acceder')) {
            return back()->with('error', 'Vous n\'avez pas la permission d\'accéder à la corbeille.');
        }

        try {
            Prescription::withTrashed()->findOrFail($id)->restore();

            return back()->with('success', 'Prescription restaurée.');
        } catch (\Exception $e) {
            Log::error('Erreur restauration prescription', ['error' => $e->getMessage()]);

            return back()->with('error', 'Erreur lors de la restauration.');
        }
    }

    public function forceDelete(int $id): RedirectResponse
    {
        $user = Auth::user();
        if (! $user->hasPermission('corbeille.acceder')) {
            return back()->with('error', 'Vous n\'avez pas la permission de supprimer définitivement.');
        }

        try {
            Prescription::withTrashed()->findOrFail($id)->forceDelete();

            return back()->with('success', 'Prescription supprimée définitivement.');
        } catch (\Exception $e) {
            Log::error('Erreur suppression définitive prescription', ['error' => $e->getMessage()]);

            return back()->with('error', 'Erreur lors de la suppression définitive.');
        }
    }

    public function archive(int $id): RedirectResponse
    {
        $user = Auth::user();
        if (! $user->hasPermission('archives.acceder')) {
            return back()->with('error', 'Vous n\'avez pas la permission d\'archiver des prescriptions.');
        }

        try {
            $prescription = Prescription::findOrFail($id);

            if ($prescription->status !== 'VALIDE') {
                return back()->with('error', 'Seules les prescriptions validées peuvent être archivées.');
            }

            $prescription->update(['status' => 'ARCHIVE']);

            return back()->with('success', 'Prescription archivée.');
        } catch (\Exception $e) {
            Log::error('Erreur archivage prescription', ['error' => $e->getMessage()]);

            return back()->with('error', 'Erreur lors de l\'archivage.');
        }
    }

    public function unarchive(int $id): RedirectResponse
    {
        $user = Auth::user();
        if (! $user->hasPermission('archives.acceder')) {
            return back()->with('error', 'Vous n\'avez pas la permission de désarchiver des prescriptions.');
        }

        try {
            Prescription::findOrFail($id)->update(['status' => 'VALIDE']);

            return back()->with('success', 'Prescription désarchivée.');
        } catch (\Exception $e) {
            Log::error('Erreur désarchivage prescription', ['error' => $e->getMessage()]);

            return back()->with('error', 'Erreur lors du désarchivage.');
        }
    }

    public function togglePayment(int $id): RedirectResponse
    {
        $user = Auth::user();
        if (! $user->hasPermission('prescriptions.modifier')) {
            return back()->with('error', 'Vous n\'avez pas la permission de modifier le paiement.');
        }

        try {
            $paiement = Paiement::whereHas(
                'prescription',
                fn ($q) => $q->where('id', $id)
            )->firstOrFail();

            $nouveauStatut = ! $paiement->status;
            $paiement->update([
                'status' => $nouveauStatut,
                'date_paiement' => $nouveauStatut ? now() : null,
            ]);

            $message = $nouveauStatut ? 'Paiement marqué comme payé.' : 'Paiement marqué comme non payé.';

            return back()->with('success', $message);
        } catch (\Exception $e) {
            Log::error('Erreur toggle paiement', ['error' => $e->getMessage()]);

            return back()->with('error', 'Erreur lors de la modification du paiement.');
        }
    }

    public function notify(int $id): RedirectResponse
    {
        $user = Auth::user();
        if (! $user->hasPermission('prescriptions.modifier')) {
            return back()->with('error', 'Autorisation refusée.');
        }

        if (! app(\App\Services\FeatureService::class)->isEnabledForCurrentUser('notifications_sms_email_validated')) {
            return back()->with('error', 'La fonctionnalité Premium de notification n\'est pas activée sur ce compte.');
        }

        $prescription = Prescription::findOrFail($id);
        $prescription->update(['notified_at' => now()]);

        return back()->with('success', 'Patient notifié (simulation).');
    }

    private function resolveOrCreatePatient(array $validated): Patient
    {
        $patientPayload = $validated['patient'] ?? [];

        if (! empty($validated['patient_id'])) {
            $patient = Patient::query()->findOrFail((int) $validated['patient_id']);

            if (! empty($patientPayload)) {
                $patient->update([
                    'nom' => $patientPayload['nom'] ?? $patient->nom,
                    'prenom' => $patientPayload['prenom'] ?? $patient->prenom,
                    'civilite' => $patientPayload['civilite'] ?? $patient->civilite,
                    'telephone' => $patientPayload['telephone'] ?? $patient->telephone,
                    'email' => $patientPayload['email'] ?? $patient->email,
                    'adresse' => $patientPayload['adresse'] ?? $patient->adresse,
                    'date_naissance' => $patientPayload['date_naissance'] ?? $patient->date_naissance,
                ]);
            }

            return $patient;
        }

        return Patient::query()->create([
            'nom' => $patientPayload['nom'],
            'prenom' => $patientPayload['prenom'] ?? null,
            'civilite' => $patientPayload['civilite'],
            'telephone' => $patientPayload['telephone'] ?? null,
            'email' => $patientPayload['email'] ?? null,
            'adresse' => $patientPayload['adresse'] ?? null,
            'date_naissance' => $patientPayload['date_naissance'] ?? null,
        ]);
    }

    private function calculateAgeData(?string $dateNaissance, ?int $fallbackAge, ?string $fallbackUnite): array
    {
        if ($dateNaissance) {
            $birth = Carbon::parse($dateNaissance);
            $now = Carbon::now();
            $days = $birth->diffInDays($now);
            $months = $birth->diffInMonths($now);

            if ($days <= 60) {
                return ['age' => $days, 'unite_age' => 'Jours'];
            }

            if ($months < 24) {
                return ['age' => $months, 'unite_age' => 'Mois'];
            }

            return ['age' => $birth->age, 'unite_age' => 'Ans'];
        }

        return [
            'age' => $fallbackAge ?? 0,
            'unite_age' => $fallbackUnite ?? 'Ans',
        ];
    }

    private function calculateAnalysesSubtotal(Collection $analyses, Collection $selectedAnalyseIds): float
    {
        $total = 0.0;
        $countedParents = [];

        foreach ($selectedAnalyseIds as $analyseId) {
            /** @var Analyse|null $analyse */
            $analyse = $analyses->get($analyseId);
            if (! $analyse) {
                continue;
            }

            if ($analyse->level === 'PARENT') {
                $total += max(0, (float) $analyse->prix);
                $countedParents[$analyse->id] = true;

                continue;
            }

            if ($analyse->parent_id && isset($countedParents[$analyse->parent_id])) {
                continue;
            }

            if ($analyse->parent_id && $analyse->parent && (float) $analyse->parent->prix > 0) {
                $total += (float) $analyse->parent->prix;
                $countedParents[$analyse->parent_id] = true;

                continue;
            }

            $total += max(0, (float) $analyse->prix);
        }

        return $total;
    }

    private function calculatePrelevementsTotal(Collection $prelevements, Collection $selectedPrelevements): float
    {
        $total = 0.0;

        foreach ($selectedPrelevements as $item) {
            /** @var Prelevement|null $prelevement */
            $prelevement = $prelevements->get($item['id']);
            if (! $prelevement) {
                continue;
            }

            $quantity = max(1, (int) $item['quantite']);
            $unitPrice = (float) $prelevement->prix;

            if ($quantity > 1 && (float) $prelevement->prix_promotion > 0) {
                $unitPrice = (float) $prelevement->prix_promotion;
            }

            $total += $unitPrice * $quantity;
        }

        return $total;
    }
}
