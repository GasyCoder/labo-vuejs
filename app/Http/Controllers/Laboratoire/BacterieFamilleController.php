<?php

namespace App\Http\Controllers\Laboratoire;

use App\Http\Controllers\Controller;
use App\Models\BacterieFamille;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class BacterieFamilleController extends Controller
{
    public function index(Request $request): Response
    {
        $search = $request->input('search', '');
        $perPage = $request->input('perPage', 15);

        $familles = BacterieFamille::query()
            ->withCount('bacteries')
            ->when($search, fn ($q) => $q->where('designation', 'like', "%{$search}%"))
            ->orderBy('designation')
            ->paginate($perPage)
            ->withQueryString();

        $stats = [
            'total' => BacterieFamille::count(),
            'actives' => BacterieFamille::where('status', true)->count(),
            'inactives' => BacterieFamille::where('status', false)->count(),
            'avec_bacteries' => BacterieFamille::has('bacteries')->count(),
        ];

        return Inertia::render('Laboratoire/Microbiologie/FamillesBacteries', [
            'familles' => $familles,
            'filters' => ['search' => $search, 'perPage' => $perPage],
            'stats' => $stats,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'designation' => 'required|string|max:255|unique:bacterie_familles,designation',
            'status' => 'boolean',
        ]);

        BacterieFamille::create([
            'designation' => trim($request->designation),
            'status' => $request->status ?? true,
        ]);

        return redirect()->back()->with('success', 'Famille de bactérie créée avec succès !');
    }

    public function update(Request $request, BacterieFamille $bacterieFamille): RedirectResponse
    {
        $request->validate([
            'designation' => 'required|string|max:255|unique:bacterie_familles,designation,'.$bacterieFamille->id,
            'status' => 'boolean',
        ]);

        $bacterieFamille->update([
            'designation' => trim($request->designation),
            'status' => $request->status ?? true,
        ]);

        return redirect()->back()->with('success', 'Famille de bactérie modifiée avec succès !');
    }

    public function toggleStatus(BacterieFamille $bacterieFamille): RedirectResponse
    {
        $bacterieFamille->update(['status' => ! $bacterieFamille->status]);
        $label = $bacterieFamille->status ? 'activée' : 'désactivée';

        return redirect()->back()->with('success', "Famille {$label} avec succès !");
    }

    public function destroy(BacterieFamille $bacterieFamille): RedirectResponse
    {
        if ($bacterieFamille->bacteries()->count() > 0) {
            return redirect()->back()->with('error', 'Impossible : cette famille contient des bactéries.');
        }

        $bacterieFamille->delete();

        return redirect()->back()->with('success', 'Famille de bactérie supprimée avec succès !');
    }
}
