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
                'patient:id,nom,prenom,telephone',
                'prescripteur:id,nom',
                'paiements:id,prescription_id,status,date_paiement',
            ])
            ->withCount('analyses');

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

        if ($tab === 'deleted') {
            $prescriptionsQuery->onlyTrashed();
        } else {
            $statusMap = [
                'actives' => ['EN_ATTENTE', 'EN_COURS', 'TERMINE', 'A_REFAIRE'],
                'valide' => ['VALIDE'],
                'archive' => ['ARCHIVE'],
            ];

            $prescriptionsQuery->whereIn('status', $statusMap[$tab]);
        }

        if ($paymentFilter === 'paye') {
            $prescriptionsQuery->whereHas('paiements', function ($query): void {
                $query->where('status', true);
            });
        }

        if ($paymentFilter === 'non_paye') {
            $prescriptionsQuery->whereHas('paiements', function ($query): void {
                $query->where('status', false);
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
                    'created_at' => $prescription->created_at?->format('d/m/Y H:i'),
                    'created_at_relative' => $prescription->created_at?->diffForHumans(),
                    'deleted_at' => $prescription->deleted_at?->format('d/m/Y H:i'),
                    'patient' => $prescription->patient ? [
                        'nom_complet' => trim(sprintf('%s %s', $prescription->patient->nom, $prescription->patient->prenom ?? '')),
                        'telephone' => $prescription->patient->telephone,
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
        $countPaye = Paiement::where('status', true)->count();
        $countNonPaye = Paiement::where('status', false)->count();

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

        $analyses = Analyse::query()
            ->where('status', true)
            ->whereIn('level', ['PARENT', 'NORMAL', 'CHILD'])
            ->where(function ($query) use ($term): void {
                $query->whereRaw('UPPER(code) LIKE ?', ["%{$term}%"])
                    ->orWhereRaw('UPPER(designation) LIKE ?', ["%{$term}%"]);
            })
            ->with(['parent:id,designation,prix', 'enfants:id,parent_id,designation'])
            ->limit(30)
            ->get()
            ->sortBy([
                function (Analyse $analyse) use ($term): int {
                    if (strtoupper((string) $analyse->code) === $term) {
                        return 0;
                    }

                    if (str_starts_with(strtoupper((string) $analyse->code), $term)) {
                        return 1;
                    }

                    if (str_contains(strtoupper((string) $analyse->designation), $term)) {
                        return 2;
                    }

                    return 3;
                },
                function (Analyse $analyse): string {
                    return (string) $analyse->designation;
                },
            ])
            ->values()
            ->take(20);

        return response()->json([
            'data' => $analyses->map(function (Analyse $analyse): array {
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
                    'is_parent' => $analyse->level === 'PARENT',
                    'enfants_inclus' => $analyse->level === 'PARENT'
                        ? $analyse->enfants->pluck('designation')->values()
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
            ->route('secretaire.prescription.edit', $prescription->id)
            ->with('success', 'Prescription enregistrée avec succès.');
    }

    public function edit(int $prescriptionId): Response
    {
        $prescription = Prescription::with(['patient:id,nom,prenom,telephone', 'prescripteur:id,nom'])
            ->findOrFail($prescriptionId);

        return Inertia::render('Secretaire/Prescriptions/Edit', [
            'prescription' => [
                'id' => $prescription->id,
                'reference' => $prescription->reference,
                'status' => $prescription->status,
                'patient' => $prescription->patient ? trim(sprintf('%s %s', $prescription->patient->nom, $prescription->patient->prenom ?? '')) : null,
                'prescripteur' => $prescription->prescripteur?->nom,
                'created_at' => $prescription->created_at?->format('d/m/Y H:i'),
            ],
        ]);
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
