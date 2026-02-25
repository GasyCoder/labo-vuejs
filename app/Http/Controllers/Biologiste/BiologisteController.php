<?php

namespace App\Http\Controllers\Biologiste;

use App\Http\Controllers\Controller;
use App\Models\Prescription;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;

class BiologisteController extends Controller
{
    public function index(Request $request): Response
    {
        $tab = $request->input('tab', 'a_valider');
        $search = $request->input('search', '');

        $baseQuery = Prescription::with([
            'patient:id,nom,prenom',
            'prescripteur:id,nom,prenom',
            'analyses:id,designation',
        ])
            ->when($search, function ($q) use ($search) {
                $q->where(function ($query) use ($search) {
                    $query->where('reference', 'like', '%'.$search.'%')
                        ->orWhereHas('patient', function ($sq) use ($search) {
                            $sq->where('nom', 'like', '%'.$search.'%')
                                ->orWhere('prenom', 'like', '%'.$search.'%');
                        })
                        ->orWhereHas('prescripteur', function ($sq) use ($search) {
                            $sq->where('nom', 'like', '%'.$search.'%')
                                ->orWhere('prenom', 'like', '%'.$search.'%');
                        });
                });
            })
            ->orderBy('updated_at', 'desc');

        switch ($tab) {
            case 'valide':
                $prescriptions = (clone $baseQuery)
                    ->where('status', 'VALIDE')
                    ->paginate(15)
                    ->withQueryString();
                break;
            case 'a_refaire':
                $prescriptions = (clone $baseQuery)
                    ->where('status', 'A_REFAIRE')
                    ->paginate(15)
                    ->withQueryString();
                break;
            default: // a_valider = TERMINE
                $prescriptions = (clone $baseQuery)
                    ->where('status', 'TERMINE')
                    ->paginate(15)
                    ->withQueryString();
                break;
        }

        $stats = [
            'a_valider' => Prescription::where('status', 'TERMINE')->count(),
            'valide' => Prescription::where('status', 'VALIDE')->count(),
            'a_refaire' => Prescription::where('status', 'A_REFAIRE')->count(),
        ];

        return Inertia::render('Biologiste/Index', [
            'prescriptions' => $prescriptions,
            'stats' => $stats,
            'filters' => [
                'tab' => $tab,
                'search' => $search,
            ],
        ]);
    }

    public function validatePrescription(int $id): RedirectResponse
    {
        try {
            DB::beginTransaction();

            $prescription = Prescription::findOrFail($id);

            if ($prescription->status !== 'TERMINE') {
                return back()->with('error', 'Seules les prescriptions terminées peuvent être validées.');
            }

            // Étape 1 : Valider tous les résultats TERMINE
            DB::table('resultats')
                ->where('prescription_id', $id)
                ->where('status', 'TERMINE')
                ->update([
                    'status' => 'VALIDE',
                    'validated_by' => Auth::id(),
                    'validated_at' => now(),
                    'updated_at' => now(),
                ]);

            // Étape 2 : Mettre à jour la table pivot prescription_analyse
            DB::table('prescription_analyse')
                ->where('prescription_id', $id)
                ->update(['status' => 'VALIDE', 'updated_at' => now()]);

            // Étape 3 : Valider la prescription
            $prescription->update([
                'status' => 'VALIDE',
                'updated_by' => Auth::id(),
            ]);

            Log::info('Prescription validée par biologiste', [
                'prescription_id' => $id,
                'reference' => $prescription->reference,
                'user_id' => Auth::id(),
            ]);

            DB::commit();

            return back()->with('success', 'Prescription '.$prescription->reference.' validée avec succès.');
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Erreur validation biologiste', [
                'prescription_id' => $id,
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'Erreur lors de la validation : '.$e->getMessage());
        }
    }

    public function rejectPrescription(Request $request, int $id): RedirectResponse
    {
        $request->validate([
            'commentaire' => 'required|string|max:500',
        ]);

        try {
            DB::beginTransaction();

            $prescription = Prescription::findOrFail($id);

            if ($prescription->status !== 'TERMINE') {
                return back()->with('error', 'Seules les prescriptions terminées peuvent être renvoyées.');
            }

            $prescription->update([
                'status' => 'A_REFAIRE',
                'updated_by' => Auth::id(),
                'commentaire_biologiste' => $request->input('commentaire'),
            ]);

            // Mark all analyse_prescription as A_REFAIRE
            DB::table('prescription_analyse')
                ->where('prescription_id', $id)
                ->update(['status' => 'A_REFAIRE', 'updated_at' => now()]);

            Log::info('Prescription renvoyée par biologiste', [
                'prescription_id' => $id,
                'reference' => $prescription->reference,
                'commentaire' => $request->input('commentaire'),
                'user_id' => Auth::id(),
            ]);

            DB::commit();

            return back()->with('success', 'Prescription '.$prescription->reference.' renvoyée au technicien.');
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Erreur rejet biologiste', [
                'prescription_id' => $id,
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'Erreur lors du rejet : '.$e->getMessage());
        }
    }
}
