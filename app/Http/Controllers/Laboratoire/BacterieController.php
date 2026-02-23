<?php

namespace App\Http\Controllers\Laboratoire;

use App\Http\Controllers\Controller;
use App\Models\Bacterie;
use App\Models\BacterieFamille;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class BacterieController extends Controller
{
    public function index(Request $request): Response
    {
        $search = $request->input('search', '');
        $familleFilter = $request->input('familleFilter', '');
        $perPage = $request->input('perPage', 15);

        $bacteries = Bacterie::query()
            ->with('famille')
            ->when($search, function ($q) use ($search) {
                $q->where('designation', 'like', "%{$search}%")
                    ->orWhereHas('famille', fn ($sq) => $sq->where('designation', 'like', "%{$search}%"));
            })
            ->when($familleFilter, fn ($q) => $q->where('famille_id', $familleFilter))
            ->orderBy('designation')
            ->paginate($perPage)
            ->withQueryString();

        $familles = BacterieFamille::where('status', true)->orderBy('designation')->get();

        $stats = [
            'total' => Bacterie::count(),
            'actives' => Bacterie::where('status', true)->count(),
            'inactives' => Bacterie::where('status', false)->count(),
        ];

        return Inertia::render('Laboratoire/Microbiologie/Bacteries', [
            'bacteries' => $bacteries,
            'familles' => $familles,
            'filters' => ['search' => $search, 'familleFilter' => $familleFilter, 'perPage' => $perPage],
            'stats' => $stats,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'famille_id' => 'required|exists:bacterie_familles,id',
            'designation' => 'required|string|max:255',
            'status' => 'boolean',
        ]);

        Bacterie::create([
            'famille_id' => $request->famille_id,
            'designation' => trim($request->designation),
            'status' => $request->status ?? true,
        ]);

        return redirect()->back()->with('success', 'Bactérie créée avec succès !');
    }

    public function update(Request $request, Bacterie $bacterie): RedirectResponse
    {
        $request->validate([
            'famille_id' => 'required|exists:bacterie_familles,id',
            'designation' => 'required|string|max:255',
            'status' => 'boolean',
        ]);

        $bacterie->update([
            'famille_id' => $request->famille_id,
            'designation' => trim($request->designation),
            'status' => $request->status ?? true,
        ]);

        return redirect()->back()->with('success', 'Bactérie modifiée avec succès !');
    }

    public function toggleStatus(Bacterie $bacterie): RedirectResponse
    {
        $bacterie->update(['status' => ! $bacterie->status]);
        $label = $bacterie->status ? 'activée' : 'désactivée';

        return redirect()->back()->with('success', "Bactérie {$label} avec succès !");
    }

    public function destroy(Bacterie $bacterie): RedirectResponse
    {
        $bacterie->delete();

        return redirect()->back()->with('success', 'Bactérie supprimée avec succès !');
    }
}
