<?php

namespace App\Http\Controllers\Laboratoire;

use App\Http\Controllers\Controller;
use App\Models\Antibiotique;
use App\Models\BacterieFamille;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AntibiotiqueController extends Controller
{
    public function index(Request $request): Response
    {
        $search = $request->input('search', '');
        $familleFilter = $request->input('familleFilter', '');
        $perPage = $request->input('perPage', 15);

        $antibiotiques = Antibiotique::query()
            ->with('famille')
            ->when($search, function ($q) use ($search) {
                $q->where('designation', 'like', "%{$search}%")
                    ->orWhere('commentaire', 'like', "%{$search}%")
                    ->orWhereHas('famille', fn ($sq) => $sq->where('designation', 'like', "%{$search}%"));
            })
            ->when($familleFilter, fn ($q) => $q->where('famille_id', $familleFilter))
            ->orderBy('designation')
            ->paginate($perPage)
            ->withQueryString();

        $familles = BacterieFamille::where('status', true)->orderBy('designation')->get();

        $stats = [
            'total' => Antibiotique::count(),
            'actifs' => Antibiotique::where('status', true)->count(),
            'inactifs' => Antibiotique::where('status', false)->count(),
            'avec_bacteries' => Antibiotique::has('bacteries')->count(),
        ];

        return Inertia::render('Laboratoire/Microbiologie/Antibiotiques', [
            'antibiotiques' => $antibiotiques,
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
            'commentaire' => 'nullable|string|max:1000',
            'status' => 'boolean',
        ]);

        Antibiotique::create([
            'famille_id' => $request->famille_id,
            'designation' => trim($request->designation),
            'commentaire' => $request->commentaire ? trim($request->commentaire) : null,
            'status' => $request->status ?? true,
        ]);

        return redirect()->back()->with('success', 'Antibiotique créé avec succès !');
    }

    public function update(Request $request, Antibiotique $antibiotique): RedirectResponse
    {
        $request->validate([
            'famille_id' => 'required|exists:bacterie_familles,id',
            'designation' => 'required|string|max:255',
            'commentaire' => 'nullable|string|max:1000',
            'status' => 'boolean',
        ]);

        $antibiotique->update([
            'famille_id' => $request->famille_id,
            'designation' => trim($request->designation),
            'commentaire' => $request->commentaire ? trim($request->commentaire) : null,
            'status' => $request->status ?? true,
        ]);

        return redirect()->back()->with('success', 'Antibiotique modifié avec succès !');
    }

    public function toggleStatus(Antibiotique $antibiotique): RedirectResponse
    {
        $antibiotique->update(['status' => ! $antibiotique->status]);
        $label = $antibiotique->status ? 'activé' : 'désactivé';

        return redirect()->back()->with('success', "Antibiotique {$label} avec succès !");
    }

    public function destroy(Antibiotique $antibiotique): RedirectResponse
    {
        if ($antibiotique->bacteries()->count() > 0) {
            return redirect()->back()->with('error', 'Impossible : lié à des bactéries.');
        }

        $antibiotique->delete();

        return redirect()->back()->with('success', 'Antibiotique supprimé avec succès !');
    }
}
