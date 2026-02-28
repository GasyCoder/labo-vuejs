<?php

namespace App\Http\Controllers\Laboratoire;

use App\Http\Controllers\Controller;
use App\Models\Analyse;
use App\Models\Examen;
use App\Models\Type;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class AnalyseController extends Controller
{
    public function index(Request $request): Response
    {
        $search = $request->input('search', '');
        $selectedExamen = $request->input('selectedExamen', '');
        $selectedLevel = $request->input('selectedLevel', 'tous');
        $perPage = $request->input('perPage', 10);

        $query = Analyse::with(['examen', 'type', 'parent', 'enfants.enfants']);

        match ($selectedLevel) {
            'parents' => $query->where('level', 'PARENT'),
            'racines' => $query->whereNull('parent_id'),
            'normales' => $query->where('level', 'NORMAL'),
            'enfants' => $query->where('level', 'CHILD'),
            default => null,
        };

        $query->orderBy('level', 'DESC')->orderBy('ordre')->orderBy('designation');

        if ($selectedExamen) {
            $query->where('examen_id', $selectedExamen);
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")
                    ->orWhere('designation', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $analyses = $query->paginate($perPage)->withQueryString();

        $counts = [
            'racines' => Analyse::whereNull('parent_id')->count(),
            'parents' => Analyse::where('level', 'PARENT')->count(),
            'normales' => Analyse::where('level', 'NORMAL')->count(),
            'enfants' => Analyse::where('level', 'CHILD')->count(),
            'tous' => Analyse::count(),
        ];

        $examens = Examen::where('status', true)->orderBy('name')->get();
        $types = Type::where('status', true)->orderBy('name')->get();
        $analysesParents = Analyse::where('level', 'PARENT')
            ->where('status', true)
            ->orderBy('designation')
            ->get(['id', 'designation', 'code']);

        return Inertia::render('Laboratoire/Analyses/Listes', [
            'analyses' => $analyses,
            'counts' => $counts,
            'examens' => $examens,
            'types' => $types,
            'analysesParents' => $analysesParents,
            'filters' => [
                'search' => $search,
                'selectedExamen' => $selectedExamen,
                'selectedLevel' => $selectedLevel,
                'perPage' => $perPage,
            ],
        ]);
    }

    public function show(Analyse $analyse): Response
    {
        $analyse->load(['examen', 'type', 'parent', 'enfants.enfants']);

        return Inertia::render('Laboratoire/Analyses/Show', [
            'analyse' => $analyse,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'code' => 'required|string|max:50|unique:analyses,code',
            'level' => 'required|in:PARENT,NORMAL,CHILD',
            'parent_id' => 'nullable|exists:analyses,id',
            'designation' => 'required|string|max:255',
            'description' => 'nullable|string',
            'prix' => 'required|numeric|min:0',
            'is_bold' => 'boolean',
            'examen_id' => 'required|exists:examens,id',
            'type_id' => 'required|exists:types,id',
            'valeur_ref_homme' => 'nullable|string|max:255',
            'valeur_ref_femme' => 'nullable|string|max:255',
            'valeur_ref_enfant_garcon' => 'nullable|string|max:255',
            'valeur_ref_enfant_fille' => 'nullable|string|max:255',
            'unite' => 'nullable|string|max:50',
            'suffixe' => 'nullable|string|max:50',
            'ordre' => 'nullable|integer',
            'status' => 'boolean',
            'sousAnalyses' => 'nullable|array',
            'sousAnalyses.*.code' => 'required|string|max:50',
            'sousAnalyses.*.designation' => 'required|string|max:255',
            'sousAnalyses.*.prix' => 'required|numeric|min:0',
            'sousAnalyses.*.level' => 'required|in:PARENT,NORMAL,CHILD',
        ]);

        DB::transaction(function () use ($validated) {
            $analyseParent = Analyse::create([
                'code' => $validated['code'],
                'level' => $validated['level'],
                'parent_id' => $validated['parent_id'] ?? null,
                'designation' => $validated['designation'],
                'description' => $validated['description'] ?? null,
                'prix' => $validated['prix'],
                'is_bold' => $validated['is_bold'] ?? false,
                'examen_id' => $validated['examen_id'],
                'type_id' => $validated['type_id'],
                'valeur_ref_homme' => $validated['valeur_ref_homme'] ?? '',
                'valeur_ref_femme' => $validated['valeur_ref_femme'] ?? '',
                'valeur_ref_enfant_garcon' => $validated['valeur_ref_enfant_garcon'] ?? '',
                'valeur_ref_enfant_fille' => $validated['valeur_ref_enfant_fille'] ?? '',
                'unite' => $validated['unite'] ?? '',
                'suffixe' => $validated['suffixe'] ?? null,
                'ordre' => $validated['ordre'] ?? 99,
                'status' => $validated['status'] ?? true,
            ]);

            if (! empty($validated['sousAnalyses'])) {
                foreach ($validated['sousAnalyses'] as $sa) {
                    if (! empty($sa['_delete'])) {
                        continue;
                    }

                    $sousRecord = Analyse::create([
                        'code' => $sa['code'],
                        'level' => $sa['level'] ?? 'CHILD',
                        'parent_id' => $analyseParent->id,
                        'designation' => $sa['designation'],
                        'prix' => $sa['prix'],
                        'is_bold' => $sa['is_bold'] ?? false,
                        'examen_id' => $sa['examen_id'] ?? $validated['examen_id'],
                        'type_id' => $sa['type_id'] ?? $validated['type_id'],
                        'valeur_ref_homme' => $sa['valeur_ref_homme'] ?? '',
                        'valeur_ref_femme' => $sa['valeur_ref_femme'] ?? '',
                        'valeur_ref_enfant_garcon' => $sa['valeur_ref_enfant_garcon'] ?? '',
                        'valeur_ref_enfant_fille' => $sa['valeur_ref_enfant_fille'] ?? '',
                        'unite' => $sa['unite'] ?? '',
                        'suffixe' => $sa['suffixe'] ?? null,
                        'ordre' => $sa['ordre'] ?? 1,
                        'status' => $sa['status'] ?? true,
                    ]);

                    // Children of sub-analyses
                    if (! empty($sa['children'])) {
                        foreach ($sa['children'] as $child) {
                            if (! empty($child['_delete'])) {
                                continue;
                            }
                            Analyse::create([
                                'code' => $child['code'],
                                'level' => $child['level'] ?? 'CHILD',
                                'parent_id' => $sousRecord->id,
                                'designation' => $child['designation'],
                                'prix' => $child['prix'],
                                'is_bold' => $child['is_bold'] ?? false,
                                'examen_id' => $child['examen_id'] ?? $sa['examen_id'] ?? $validated['examen_id'],
                                'type_id' => $child['type_id'] ?? $sa['type_id'] ?? $validated['type_id'],
                                'valeur_ref_homme' => $child['valeur_ref_homme'] ?? '',
                                'valeur_ref_femme' => $child['valeur_ref_femme'] ?? '',
                                'valeur_ref_enfant_garcon' => $child['valeur_ref_enfant_garcon'] ?? '',
                                'valeur_ref_enfant_fille' => $child['valeur_ref_enfant_fille'] ?? '',
                                'unite' => $child['unite'] ?? '',
                                'suffixe' => $child['suffixe'] ?? null,
                                'ordre' => $child['ordre'] ?? 1,
                                'status' => $child['status'] ?? true,
                            ]);
                        }
                    }
                }
            }
        });

        return redirect()->back()->with('success', 'Analyse créée avec succès !');
    }

    public function update(Request $request, Analyse $analyse): RedirectResponse
    {
        $validated = $request->validate([
            'code' => 'required|string|max:50|unique:analyses,code,'.$analyse->id,
            'level' => 'required|in:PARENT,NORMAL,CHILD',
            'parent_id' => 'nullable|exists:analyses,id',
            'designation' => 'required|string|max:255',
            'description' => 'nullable|string',
            'prix' => 'required|numeric|min:0',
            'is_bold' => 'boolean',
            'examen_id' => 'required|exists:examens,id',
            'type_id' => 'required|exists:types,id',
            'valeur_ref_homme' => 'nullable|string|max:255',
            'valeur_ref_femme' => 'nullable|string|max:255',
            'valeur_ref_enfant_garcon' => 'nullable|string|max:255',
            'valeur_ref_enfant_fille' => 'nullable|string|max:255',
            'unite' => 'nullable|string|max:50',
            'suffixe' => 'nullable|string|max:50',
            'ordre' => 'nullable|integer',
            'status' => 'boolean',
            'sousAnalyses' => 'nullable|array',
        ]);

        DB::transaction(function () use ($validated, $analyse) {
            $analyse->update([
                'code' => $validated['code'],
                'level' => $validated['level'],
                'parent_id' => $validated['parent_id'] ?? null,
                'designation' => $validated['designation'],
                'description' => $validated['description'] ?? null,
                'prix' => $validated['prix'],
                'is_bold' => $validated['is_bold'] ?? false,
                'examen_id' => $validated['examen_id'],
                'type_id' => $validated['type_id'],
                'valeur_ref_homme' => $validated['valeur_ref_homme'] ?? '',
                'valeur_ref_femme' => $validated['valeur_ref_femme'] ?? '',
                'valeur_ref_enfant_garcon' => $validated['valeur_ref_enfant_garcon'] ?? '',
                'valeur_ref_enfant_fille' => $validated['valeur_ref_enfant_fille'] ?? '',
                'unite' => $validated['unite'] ?? '',
                'suffixe' => $validated['suffixe'] ?? null,
                'ordre' => $validated['ordre'] ?? 99,
                'status' => $validated['status'] ?? true,
            ]);

            $existingIds = [];
            $sousAnalyses = $validated['sousAnalyses'] ?? [];
            $hasSous = ($validated['level'] === 'PARENT' && count($sousAnalyses) > 0);

            if ($hasSous) {
                foreach ($sousAnalyses as $sa) {
                    if (! empty($sa['_delete'])) {
                        if (! empty($sa['id'])) {
                            Analyse::where('id', $sa['id'])->delete();
                        }

                        continue;
                    }

                    $data = [
                        'code' => $sa['code'],
                        'level' => $sa['level'] ?? 'CHILD',
                        'parent_id' => $analyse->id,
                        'designation' => $sa['designation'],
                        'prix' => $sa['prix'],
                        'is_bold' => $sa['is_bold'] ?? false,
                        'examen_id' => $sa['examen_id'] ?? $validated['examen_id'],
                        'type_id' => $sa['type_id'] ?? $validated['type_id'],
                        'valeur_ref_homme' => $sa['valeur_ref_homme'] ?? '',
                        'valeur_ref_femme' => $sa['valeur_ref_femme'] ?? '',
                        'valeur_ref_enfant_garcon' => $sa['valeur_ref_enfant_garcon'] ?? '',
                        'valeur_ref_enfant_fille' => $sa['valeur_ref_enfant_fille'] ?? '',
                        'unite' => $sa['unite'] ?? '',
                        'suffixe' => $sa['suffixe'] ?? null,
                        'ordre' => $sa['ordre'] ?? 1,
                        'status' => $sa['status'] ?? true,
                    ];

                    if (! empty($sa['id'])) {
                        $sousRecord = Analyse::find($sa['id']);
                        $sousRecord?->update($data);
                        $existingIds[] = $sa['id'];
                    } else {
                        $sousRecord = Analyse::create($data);
                        $existingIds[] = $sousRecord->id;
                    }

                    // Handle children of sub-analyse
                    $existingChildIds = [];
                    if (! empty($sa['children'])) {
                        foreach ($sa['children'] as $child) {
                            if (! empty($child['_delete'])) {
                                if (! empty($child['id'])) {
                                    Analyse::where('id', $child['id'])->delete();
                                }

                                continue;
                            }

                            $childData = [
                                'code' => $child['code'],
                                'level' => $child['level'] ?? 'CHILD',
                                'parent_id' => $sousRecord->id,
                                'designation' => $child['designation'],
                                'prix' => $child['prix'],
                                'is_bold' => $child['is_bold'] ?? false,
                                'examen_id' => $child['examen_id'] ?? $sa['examen_id'] ?? $validated['examen_id'],
                                'type_id' => $child['type_id'] ?? $sa['type_id'] ?? $validated['type_id'],
                                'valeur_ref_homme' => $child['valeur_ref_homme'] ?? '',
                                'valeur_ref_femme' => $child['valeur_ref_femme'] ?? '',
                                'valeur_ref_enfant_garcon' => $child['valeur_ref_enfant_garcon'] ?? '',
                                'valeur_ref_enfant_fille' => $child['valeur_ref_enfant_fille'] ?? '',
                                'unite' => $child['unite'] ?? '',
                                'suffixe' => $child['suffixe'] ?? null,
                                'ordre' => $child['ordre'] ?? 1,
                                'status' => $child['status'] ?? true,
                            ];

                            if (! empty($child['id'])) {
                                Analyse::find($child['id'])?->update($childData);
                                $existingChildIds[] = $child['id'];
                            } else {
                                $childRecord = Analyse::create($childData);
                                $existingChildIds[] = $childRecord->id;
                            }
                        }
                    }

                    // Delete removed children
                    if ($sousRecord) {
                        Analyse::where('parent_id', $sousRecord->id)
                            ->whereNotIn('id', $existingChildIds)
                            ->delete();
                    }
                }
            }

            // Delete removed sub-analyses
            Analyse::where('parent_id', $analyse->id)
                ->whereNotIn('id', $existingIds)
                ->delete();
        });

        return redirect()->back()->with('success', 'Analyse mise à jour avec succès !');
    }

    public function toggleStatus(Analyse $analyse): RedirectResponse
    {
        $analyse->update(['status' => ! $analyse->status]);

        return redirect()->back()->with('success', 'Statut mis à jour !');
    }

    public function destroy(Analyse $analyse): RedirectResponse
    {
        try {
            $analyse->delete();

            return redirect()->back()->with('success', "L'analyse a été supprimée avec succès.");
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Erreur lors de la suppression.');
        }
    }
}
