<?php

namespace App\Http\Controllers\Laboratoire;

use App\Http\Controllers\Controller;
use App\Models\Prelevement;
use App\Models\TypeTube;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PrelevementController extends Controller
{
    public function index(Request $request): Response
    {
        $search = $request->input('search', '');
        $perPage = $request->input('perPage', 15);

        $prelevements = Prelevement::query()
            ->with('typeTubeRecommande')
            ->when($search, fn ($q) => $q->where('denomination', 'like', "%{$search}%"))
            ->orderBy('denomination')
            ->paginate($perPage)
            ->withQueryString();

        $typesTubes = TypeTube::orderBy('code')->get();

        $stats = [
            'total' => Prelevement::count(),
            'actifs' => Prelevement::where('is_active', true)->count(),
            'inactifs' => Prelevement::where('is_active', false)->count(),
        ];

        return Inertia::render('Laboratoire/Analyses/Prelevements', [
            'prelevements' => $prelevements,
            'typesTubes' => $typesTubes,
            'filters' => ['search' => $search, 'perPage' => $perPage],
            'stats' => $stats,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'denomination' => 'required|string|max:255',
            'prix' => 'required|numeric|min:0',
            'prix_promotion' => 'nullable|numeric|min:0',
            'quantite' => 'required|integer|min:1',
            'is_active' => 'boolean',
            'type_tube_id' => 'nullable|exists:type_tubes,id',
        ]);

        Prelevement::create($validated);

        return redirect()->back()->with('success', 'Prélèvement créé avec succès !');
    }

    public function update(Request $request, Prelevement $prelevement): RedirectResponse
    {
        $validated = $request->validate([
            'denomination' => 'required|string|max:255',
            'prix' => 'required|numeric|min:0',
            'prix_promotion' => 'nullable|numeric|min:0',
            'quantite' => 'required|integer|min:1',
            'is_active' => 'boolean',
            'type_tube_id' => 'nullable|exists:type_tubes,id',
        ]);

        $prelevement->update($validated);

        return redirect()->back()->with('success', 'Prélèvement modifié avec succès !');
    }

    public function toggleStatus(Prelevement $prelevement): RedirectResponse
    {
        $prelevement->update(['is_active' => ! $prelevement->is_active]);
        $label = $prelevement->is_active ? 'activé' : 'désactivé';

        return redirect()->back()->with('success', "Prélèvement {$label} avec succès !");
    }

    public function destroy(Prelevement $prelevement): RedirectResponse
    {
        $prelevement->delete();

        return redirect()->back()->with('success', 'Prélèvement supprimé avec succès !');
    }
}
