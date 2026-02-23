<?php

namespace App\Http\Controllers\Laboratoire;

use App\Http\Controllers\Controller;
use App\Models\Type;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TypeController extends Controller
{
    public function index(Request $request): Response
    {
        $search = $request->input('search', '');
        $perPage = $request->input('perPage', 10);

        $types = Type::query()
            ->withCount('analyses')
            ->when($search, fn ($q) => $q->where('name', 'like', "%{$search}%")
                ->orWhere('libelle', 'like', "%{$search}%"))
            ->orderBy('id')
            ->paginate($perPage)
            ->withQueryString();

        $stats = [
            'total' => Type::count(),
            'actifs' => Type::where('status', true)->count(),
            'inactifs' => Type::where('status', false)->count(),
            'avec_analyses' => Type::has('analyses')->count(),
        ];

        return Inertia::render('Laboratoire/Analyses/Types', [
            'types' => $types,
            'filters' => ['search' => $search, 'perPage' => $perPage],
            'stats' => $stats,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100|unique:types,name',
            'libelle' => 'required|string|max:255',
            'status' => 'boolean',
        ]);

        Type::create([
            'name' => strtoupper(trim($validated['name'])),
            'libelle' => trim($validated['libelle']),
            'status' => $validated['status'] ?? true,
        ]);

        return redirect()->back()->with('success', "Type d'analyse créé avec succès !");
    }

    public function update(Request $request, Type $type): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100|unique:types,name,'.$type->id,
            'libelle' => 'required|string|max:255',
            'status' => 'boolean',
        ]);

        $type->update([
            'name' => strtoupper(trim($validated['name'])),
            'libelle' => trim($validated['libelle']),
            'status' => $validated['status'] ?? true,
        ]);

        return redirect()->back()->with('success', "Type d'analyse modifié avec succès !");
    }

    public function toggleStatus(Type $type): RedirectResponse
    {
        $type->update(['status' => ! $type->status]);
        $label = $type->status ? 'activé' : 'désactivé';

        return redirect()->back()->with('success', "Type {$label} avec succès !");
    }

    public function destroy(Type $type): RedirectResponse
    {
        if ($type->analyses()->count() > 0) {
            return redirect()->back()->with('error', 'Impossible de supprimer : des analyses utilisent ce type.');
        }

        $type->delete();

        return redirect()->back()->with('success', 'Type supprimé avec succès !');
    }
}
