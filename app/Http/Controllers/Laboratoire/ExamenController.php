<?php

namespace App\Http\Controllers\Laboratoire;

use App\Http\Controllers\Controller;
use App\Models\Examen;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ExamenController extends Controller
{
    public function index(Request $request): Response
    {
        $examens = Examen::query()
            ->orderBy('name')
            ->get();

        return Inertia::render('Laboratoire/Analyses/Examens', [
            'examens' => $examens,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'abr' => 'required|string|max:10|unique:examens,abr',
            'status' => 'boolean',
        ], [
            'name.required' => "Le nom de l'examen est requis.",
            'abr.required' => "L'abréviation est requise.",
            'abr.unique' => 'Cette abréviation existe déjà.',
        ]);

        Examen::create($validated);

        return redirect()->back()->with('success', 'Examen créé avec succès !');
    }

    public function update(Request $request, Examen $examen): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'abr' => 'required|string|max:10|unique:examens,abr,'.$examen->id,
            'status' => 'boolean',
        ]);

        $examen->update($validated);

        return redirect()->back()->with('success', 'Examen modifié avec succès !');
    }

    public function destroy(Examen $examen): RedirectResponse
    {
        $examen->delete();

        return redirect()->back()->with('success', 'Examen supprimé avec succès !');
    }
}
