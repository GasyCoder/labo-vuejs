<?php

namespace App\Http\Controllers\Laboratoire;

use App\Http\Controllers\Controller;
use App\Models\Analyse;
use App\Models\AnalyseRange;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AnalyseRangeController extends Controller
{
    public function index(Request $request): Response
    {
        $query = Analyse::where('level', '!=', 'PARENT');

        $stats = [
            'total' => (clone $query)->count(),
            'configured' => (clone $query)->whereHas('ranges')->count(),
            'missing' => (clone $query)->whereDoesntHave('ranges')->count(),
        ];

        $analyses = Analyse::with('ranges', 'type', 'examen')
            ->where('level', '!=', 'PARENT')
            ->when($request->search, function ($query, $search) {
                $query->where('designation', 'like', "%{$search}%")
                      ->orWhere('code', 'like', "%{$search}%");
            })
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Laboratoire/Analyses/Ranges/Index', [
            'analyses' => $analyses,
            'filters' => $request->only(['search']),
            'stats' => $stats,
        ]);
    }

    public function edit(Analyse $analyse): Response
    {
        return Inertia::render('Laboratoire/Analyses/Ranges/Edit', [
            'analyse' => $analyse->load('ranges', 'type', 'examen'),
            'contexts' => ['HOMME', 'FEMME', 'ENFANT_GARCON', 'ENFANT_FILLE'],
        ]);
    }

    public function store(Request $request, Analyse $analyse)
    {
        $data = $request->validate([
            'ranges' => 'required|array',
            'ranges.*.context' => 'required|string',
            'ranges.*.normal_min' => 'nullable|numeric',
            'ranges.*.normal_max' => 'nullable|numeric',
            'ranges.*.critical_min' => 'nullable|numeric',
            'ranges.*.critical_max' => 'nullable|numeric',
        ]);

        foreach ($data['ranges'] as $rangeData) {
            $analyse->ranges()->updateOrCreate(
                ['context' => $rangeData['context']],
                [
                    'normal_min' => $rangeData['normal_min'],
                    'normal_max' => $rangeData['normal_max'],
                    'critical_min' => $rangeData['critical_min'],
                    'critical_max' => $rangeData['critical_max'],
                ]
            );
        }

        return redirect()->back()->with('success', 'Bornes mises à jour avec succès.');
    }
}
