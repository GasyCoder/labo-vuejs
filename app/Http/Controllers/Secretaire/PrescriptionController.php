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

        // Validation stricte du tab
        $allowedTabs = ['actives', 'valide', 'archive', 'deleted', 'autre_lab'];
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

        // Filtrage strict par onglet (MUTUELLEMENT EXCLUSIF)
        if ($tab === 'autre_lab') {
            $prescriptionsQuery->where('labo_traitement', 'AUTRE');
        } elseif ($tab === 'deleted') {
            $prescriptionsQuery->onlyTrashed();
        } else {
            // Pour tous les autres onglets classiques (actives, valide, archive)
            $prescriptionsQuery->where('labo_traitement', 'LOCAL');

            $statusMap = [
                'actives' => ['EN_ATTENTE', 'EN_COURS', 'TERMINE', 'A_REFAIRE'],
                'valide' => ['VALIDE'],
                'archive' => ['ARCHIVE'],
            ];

            if (isset($statusMap[$tab])) {
                $prescriptionsQuery->whereIn('status', $statusMap[$tab]);
            }
        }

        // 2. Recherche (Encapsulée dans un groupe de parenthèses pour ne pas briser le filtre d'onglet)
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

        // 3. Filtre de paiement (Aussi encapsulé si nécessaire)
        if ($paymentFilter) {
            $prescriptionsQuery->where(function ($query) use ($paymentFilter): void {
                match ($paymentFilter) {
                    'paye' => $query->whereHas('paiements', fn ($q) => $q->where('status', true)),
                    'non_paye' => $query->whereHas('paiements', fn ($q) => $q->where('status', false)),
                    'sans_paiement' => $query->doesntHave('paiements'),
                    default => null,
                };
            });
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
                    'labo_traitement' => $prescription->labo_traitement,
                    'labo_autre_nom' => $prescription->labo_autre_nom,
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
                        'prix' => $a->pivot->prix ?? $a->prix,
                    ]),
                    'age' => $prescription->age,
                    'unite_age' => $prescription->unite_age,
                    'poids' => $prescription->poids,
                    'renseignement_clinique' => $prescription->renseignement_clinique,
                    'created_at' => $prescription->created_at?->format('d/m/Y H:i'),
                    'created_at_relative' => $prescription->created_at?->diffForHumans(),
                    'deleted_at' => $prescription->deleted_at?->format('d/m/Y H:i'),
                    'notified_at' => $prescription->notified_at?->format('d/m/Y'),
                    'patient' => $prescription->patient ? [
                        'id' => $prescription->patient->id,
                        'nom' => $prescription->patient->nom,
                        'prenom' => $prescription->patient->prenom,
                        'nom_complet' => trim(sprintf('%s %s', $prescription->patient->nom, $prescription->patient->prenom ?? '')),
                        'telephone' => $prescription->patient->telephone,
                        'email' => $prescription->patient->email,
                        'adresse' => $prescription->patient->adresse,
                        'civilite' => $prescription->patient->civilite,
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

        $countEnAttente = Prescription::where('status', 'EN_ATTENTE')->where('labo_traitement', 'LOCAL')
            ->when($paymentFilter, function ($q) use ($paymentFilter) {
                match ($paymentFilter) {
                    'paye' => $q->whereHas('paiements', fn ($p) => $p->where('status', true)),
                    'non_paye' => $q->whereHas('paiements', fn ($p) => $p->where('status', false)),
                    'sans_paiement' => $q->doesntHave('paiements'),
                    default => null,
                };
            })->count();

        $countEnCours = Prescription::where('status', 'EN_COURS')->where('labo_traitement', 'LOCAL')
            ->when($paymentFilter, function ($q) use ($paymentFilter) {
                match ($paymentFilter) {
                    'paye' => $q->whereHas('paiements', fn ($p) => $p->where('status', true)),
                    'non_paye' => $q->whereHas('paiements', fn ($p) => $p->where('status', false)),
                    'sans_paiement' => $q->doesntHave('paiements'),
                    default => null,
                };
            })->count();

        $countTermine = Prescription::where('status', 'TERMINE')->where('labo_traitement', 'LOCAL')
            ->when($paymentFilter, function ($q) use ($paymentFilter) {
                match ($paymentFilter) {
                    'paye' => $q->whereHas('paiements', fn ($p) => $p->where('status', true)),
                    'non_paye' => $q->whereHas('paiements', fn ($p) => $p->where('status', false)),
                    'sans_paiement' => $q->doesntHave('paiements'),
                    default => null,
                };
            })->count();

        $countValide = Prescription::where('status', 'VALIDE')->where('labo_traitement', 'LOCAL')
            ->when($paymentFilter, function ($q) use ($paymentFilter) {
                match ($paymentFilter) {
                    'paye' => $q->whereHas('paiements', fn ($p) => $p->where('status', true)),
                    'non_paye' => $q->whereHas('paiements', fn ($p) => $p->where('status', false)),
                    'sans_paiement' => $q->doesntHave('paiements'),
                    default => null,
                };
            })->count();

        $countArchive = Prescription::where('status', 'ARCHIVE')->where('labo_traitement', 'LOCAL')
            ->when($paymentFilter, function ($q) use ($paymentFilter) {
                match ($paymentFilter) {
                    'paye' => $q->whereHas('paiements', fn ($p) => $p->where('status', true)),
                    'non_paye' => $q->whereHas('paiements', fn ($p) => $p->where('status', false)),
                    'sans_paiement' => $q->doesntHave('paiements'),
                    default => null,
                };
            })->count();

        $countAutreLab = Prescription::where('labo_traitement', 'AUTRE')
            ->when($paymentFilter, function ($q) use ($paymentFilter) {
                match ($paymentFilter) {
                    'paye' => $q->whereHas('paiements', fn ($p) => $p->where('status', true)),
                    'non_paye' => $q->whereHas('paiements', fn ($p) => $p->where('status', false)),
                    'sans_paiement' => $q->doesntHave('paiements'),
                    default => null,
                };
            })->count();

        $countDeleted = Prescription::onlyTrashed()
            ->when($paymentFilter, function ($q) use ($paymentFilter) {
                match ($paymentFilter) {
                    'paye' => $q->whereHas('paiements', fn ($p) => $p->where('status', true)),
                    'non_paye' => $q->whereHas('paiements', fn ($p) => $p->where('status', false)),
                    'sans_paiement' => $q->doesntHave('paiements'),
                    default => null,
                };
            })->count();
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
                'actives' => Prescription::whereIn('status', ['EN_ATTENTE', 'EN_COURS', 'TERMINE', 'A_REFAIRE'])
                    ->where('labo_traitement', 'LOCAL')
                    ->when($paymentFilter, function ($q) use ($paymentFilter) {
                        match ($paymentFilter) {
                            'paye' => $q->whereHas('paiements', fn ($p) => $p->where('status', true)),
                            'non_paye' => $q->whereHas('paiements', fn ($p) => $p->where('status', false)),
                            'sans_paiement' => $q->doesntHave('paiements'),
                            default => null,
                        };
                    })->count(),
                'countActives' => $countEnAttente + $countEnCours + $countTermine,
                'valide' => $countValide,
                'archive' => $countArchive,
                'autre_lab' => $countAutreLab,
                'deleted' => $countDeleted,
                'countEnAttente' => $countEnAttente,
                'countEnCours' => $countEnCours,
                'countTermine' => $countTermine,
                'countValide' => $countValide,
                'countArchive' => $countArchive,
                'countAutreLab' => $countAutreLab,
                'countDeleted' => $countDeleted,
                'countPaye' => $countPaye,
                'countNonPaye' => $countNonPaye,
            ],
            'permissions' => [
                'canCreate' => $user->hasPermission('prescriptions.creer'),
                'canEdit' => $user->hasPermission('prescriptions.modifier'),
                'canDelete' => $user->hasPermission('prescriptions.supprimer'),
                'canAccessTrash' => $user->hasPermission('corbeille.voir'),
                'canRestore' => $user->hasPermission('corbeille.restaurer'),
                'canForceDelete' => $user->hasPermission('corbeille.vider'),
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
                    'date_naissance' => $patient->date_naissance,
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

            $ageData = $this->calculateAgeData(
                $patient->date_naissance,
                $validated['age'] ?? null,
                $validated['unite_age'] ?? null,
            );

            // 1. Create prescription first with minimal data to have an object for calculations
            $prescription = Prescription::query()->create([
                'patient_id' => $patient->id,
                'prescripteur_id' => (int) $validated['prescripteur_id'],
                'secretaire_id' => Auth::id(),
                'patient_type' => $validated['patient_type'],
                'age' => $ageData['age'],
                'unite_age' => $ageData['unite_age'],
                'poids' => $validated['poids'] ?? null,
                'renseignement_clinique' => $validated['renseignement_clinique'] ?? null,
                'remise_type' => $validated['remise_type'] ?? 'PERCENT',
                'remise_valeur' => (float) ($validated['remise'] ?? 0),
                'status' => Prescription::STATUS_EN_ATTENTE,
                'labo_traitement' => $validated['labo_traitement'] ?? 'LOCAL',
                'labo_autre_nom' => ($validated['labo_traitement'] ?? 'LOCAL') === 'AUTRE' ? ($validated['labo_autre_nom'] ?? null) : null,
            ]);

            // 2. Snapshot analyses prices (CRITICAL for subtotal consistency)
            $syncData = [];
            foreach ($selectedAnalyseIds as $analyseId) {
                $analyse = $analyses->get($analyseId);
                $syncData[$analyseId] = [
                    'prix' => $analyse ? (float) $analyse->prix : 0.0,
                    'status' => 'EN_ATTENTE',
                ];
            }
            $prescription->analyses()->sync($syncData);

            // 3. Attach prelevements (tubes)
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

            // 4. NOW Calculate totals from the model itself (Single Source of Truth)
            // Reload relations to be sure
            $prescription->load(['analyses', 'tubes', 'paiements']);
            $totals = $prescription->calculateTotals();

            // 5. Update the legacy 'remise' field with calculated amount
            $prescription->update(['remise' => $totals['remise_amount']]);

            // 6. Handle Payment
            $paymentMethod = PaymentMethod::query()
                ->where('code', $validated['payment_method'])
                ->where('is_active', true)
                ->firstOrFail();

            $isPaid = (bool) ($validated['paiement_statut'] ?? true);

            Paiement::query()->create([
                'prescription_id' => $prescription->id,
                'montant' => $totals['net_a_payer'],
                'payment_method_id' => $paymentMethod->id,
                'recu_par' => Auth::id(),
                'status' => $isPaid,
                'date_paiement' => $isPaid ? now() : null,
            ]);

            Log::info('Prescription Created', [
                'ref' => $prescription->reference,
                'totals' => $totals,
            ]);

            return $prescription;
        });

        return redirect()
            ->route('secretaire.prescription.edit', ['prescriptionId' => $prescription->id, 'step' => 'tubes'])
            ->with('success', 'Prescription enregistrée avec succès.')
            ->with('prescription_action', 'created');
    }

    public function update(StorePrescriptionRequest $request, int $id): RedirectResponse
    {
        $prescription = Prescription::findOrFail($id);
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

        DB::transaction(function () use ($validated, $selectedAnalyseIds, $selectedPrelevements, $prescription): void {
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

            $ageData = $this->calculateAgeData(
                $patient->date_naissance,
                $validated['age'] ?? null,
                $validated['unite_age'] ?? null,
            );

            // 1. Update basic info
            $prescription->update([
                'patient_id' => $patient->id,
                'prescripteur_id' => (int) $validated['prescripteur_id'],
                'patient_type' => $validated['patient_type'],
                'age' => $ageData['age'],
                'unite_age' => $ageData['unite_age'],
                'poids' => $validated['poids'] ?? null,
                'renseignement_clinique' => $validated['renseignement_clinique'] ?? null,
                'remise_type' => $validated['remise_type'] ?? 'PERCENT',
                'remise_valeur' => (float) ($validated['remise'] ?? 0),
                'labo_traitement' => $validated['labo_traitement'] ?? 'LOCAL',
                'labo_autre_nom' => ($validated['labo_traitement'] ?? 'LOCAL') === 'AUTRE' ? ($validated['labo_autre_nom'] ?? null) : null,
            ]);

            // 2. Snapshot analyses prices (Sync pivot)
            $syncData = [];
            foreach ($selectedAnalyseIds as $analyseId) {
                $analyse = $analyses->get($analyseId);

                // Keep existing price if already in pivot, otherwise take catalog price
                $existing = $prescription->analyses()->where('analyse_id', $analyseId)->first();
                $snapshotPrice = $existing ? (float) $existing->pivot->prix : ($analyse ? (float) $analyse->prix : 0.0);

                $syncData[$analyseId] = [
                    'prix' => $snapshotPrice,
                    'status' => $existing ? $existing->pivot->status : 'EN_ATTENTE',
                ];
            }
            $prescription->analyses()->sync($syncData);

            // Gérer les résultats
            $prescription->resultats()->whereNotIn('analyse_id', $selectedAnalyseIds)->delete();
            foreach ($selectedAnalyseIds as $analyseId) {
                $prescription->resultats()->firstOrCreate(
                    ['analyse_id' => $analyseId],
                    ['status' => 'EN_ATTENTE']
                );
            }

            // 3. Update tubes (if still pending)
            if ($prescription->status === Prescription::STATUS_EN_ATTENTE) {
                $prescription->tubes()->forceDelete();
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
            }

            // 4. NOW Calculate totals from model
            $prescription->load(['analyses', 'tubes', 'paiements']);
            $totals = $prescription->calculateTotals();

            // 5. Update legacy remise amount
            $prescription->update(['remise' => $totals['remise_amount']]);

            // 6. Update Payment
            $paymentMethod = PaymentMethod::query()
                ->where('code', $validated['payment_method'])
                ->where('is_active', true)
                ->firstOrFail();

            $isPaid = (bool) ($validated['paiement_statut'] ?? true);

            $paiement = $prescription->paiements()->first();
            if ($paiement) {
                $paiement->update([
                    'montant' => $totals['net_a_payer'],
                    'payment_method_id' => $paymentMethod->id,
                    'status' => $isPaid,
                    'date_paiement' => $isPaid ? ($paiement->date_paiement ?? now()) : null,
                ]);
            } else {
                Paiement::query()->create([
                    'prescription_id' => $prescription->id,
                    'montant' => $totals['net_a_payer'],
                    'payment_method_id' => $paymentMethod->id,
                    'recu_par' => Auth::id(),
                    'status' => $isPaid,
                    'date_paiement' => $isPaid ? now() : null,
                ]);
            }

            Log::info('Prescription Updated', [
                'ref' => $prescription->reference,
                'totals' => $totals,
            ]);
        });

        return redirect()
            ->route('secretaire.prescription.edit', ['prescriptionId' => $prescription->id, 'step' => 'confirmation'])
            ->with('success', 'Prescription mise à jour avec succès.')
            ->with('prescription_action', 'updated');
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

        if ($prescription->patient && $prescription->patient->date_naissance) {
            try {
                $prescription->patient->date_naissance = Carbon::parse($prescription->patient->date_naissance)->format('Y-m-d');
            } catch (\Exception $e) {
                // Keep as is if parsing fails
            }
        }

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

    public function bulkArchive(Request $request): RedirectResponse
    {
        $user = Auth::user();
        if (! $user->hasPermission('archives.acceder')) {
            return back()->with('error', 'Vous n\'avez pas la permission d\'archiver des prescriptions.');
        }

        $ids = $request->input('ids', []);
        if (empty($ids)) {
            return back()->with('error', 'Aucune prescription sélectionnée.');
        }

        try {
            $count = Prescription::whereIn('id', $ids)
                ->where('status', 'VALIDE')
                ->update(['status' => 'ARCHIVE']);

            return back()->with('success', $count.' prescriptions archivées.');
        } catch (\Exception $e) {
            Log::error('Erreur archivage groupé prescriptions', ['error' => $e->getMessage()]);

            return back()->with('error', 'Erreur lors de l\'archivage groupé.');
        }
    }

    public function bulkRestore(Request $request): RedirectResponse
    {
        $user = Auth::user();
        if (! $user->hasPermission('corbeille.restaurer')) {
            return back()->with('error', 'Vous n\'avez pas la permission de restaurer des éléments.');
        }

        $ids = $request->input('ids', []);
        if (empty($ids)) {
            return back()->with('error', 'Aucune prescription sélectionnée.');
        }

        try {
            Prescription::onlyTrashed()->whereIn('id', $ids)->restore();

            return back()->with('success', count($ids).' prescriptions restaurées.');
        } catch (\Exception $e) {
            Log::error('Erreur restauration groupée prescriptions', ['error' => $e->getMessage()]);

            return back()->with('error', 'Erreur lors de la restauration groupée.');
        }
    }

    public function bulkForceDelete(Request $request): RedirectResponse
    {
        $user = Auth::user();
        if (! $user->hasPermission('corbeille.vider')) {
            return back()->with('error', 'Vous n\'avez pas la permission de supprimer définitivement.');
        }

        $ids = $request->input('ids', []);
        if (empty($ids)) {
            return back()->with('error', 'Aucune prescription sélectionnée.');
        }

        try {
            $prescriptions = Prescription::onlyTrashed()->whereIn('id', $ids)->get();
            foreach ($prescriptions as $prescription) {
                $prescription->forceDelete();
            }

            return back()->with('success', count($ids).' prescriptions supprimées définitivement.');
        } catch (\Exception $e) {
            Log::error('Erreur suppression définitive groupée prescriptions', ['error' => $e->getMessage()]);

            return back()->with('error', 'Erreur lors de la suppression définitive groupée.');
        }
    }

    public function bulkTogglePayment(Request $request): RedirectResponse
    {
        $user = Auth::user();
        if (! $user->hasPermission('prescriptions.modifier')) {
            return back()->with('error', 'Vous n\'avez pas la permission de modifier le paiement.');
        }

        $ids = $request->input('ids', []);
        $status = (bool) $request->input('status', true);

        if (empty($ids)) {
            return back()->with('error', 'Aucune prescription sélectionnée.');
        }

        try {
            Paiement::whereIn('prescription_id', $ids)->update([
                'status' => $status,
                'date_paiement' => $status ? now() : null,
            ]);

            $message = $status ? 'Paiements marqués comme payés.' : 'Paiements marqués comme non payés.';

            return back()->with('success', $message);
        } catch (\Exception $e) {
            Log::error('Erreur modification groupée paiement', ['error' => $e->getMessage()]);

            return back()->with('error', 'Erreur lors de la modification groupée du paiement.');
        }
    }

    public function restore(int $id): RedirectResponse
    {
        $user = Auth::user();
        if (! $user->hasPermission('corbeille.restaurer')) {
            return back()->with('error', 'Vous n\'avez pas la permission de restaurer des éléments.');
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
        if (! $user->hasPermission('corbeille.vider')) {
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

    public function pdfExterne(int $id)
    {
        $prescription = Prescription::with(['patient', 'prescripteur', 'analyses'])->findOrFail($id);

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('prescriptions.pdf-externe', compact('prescription'))
            ->setPaper('a4', 'portrait')
            ->setOptions(['isHtml5ParserEnabled' => true, 'isRemoteEnabled' => true]);

        return $pdf->stream("prescription-externe-{$prescription->reference}.pdf");
    }

    private function resolveOrCreatePatient(array $validated): Patient
    {
        $patientPayload = $validated['patient'] ?? [];
        $dateNaissance = ! empty($patientPayload['date_naissance']) ? $patientPayload['date_naissance'] : null;

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
                    'date_naissance' => $dateNaissance,
                ]);
            }

            $patient->refresh();

            return $patient;
        }

        return Patient::query()->create([
            'nom' => $patientPayload['nom'],
            'prenom' => $patientPayload['prenom'] ?? null,
            'civilite' => $patientPayload['civilite'],
            'telephone' => $patientPayload['telephone'] ?? null,
            'email' => $patientPayload['email'] ?? null,
            'adresse' => $patientPayload['adresse'] ?? null,
            'date_naissance' => $dateNaissance,
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
