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
            'secretaire:id,name',
            'technicien:id,name',
            'validator:id,name',
            'analyses:id,code,designation',
        ])
            ->withCount('analyses')
            ->where('labo_traitement', 'LOCAL')
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
            'a_valider' => Prescription::where('status', 'TERMINE')->where('labo_traitement', 'LOCAL')->count(),
            'valide' => Prescription::where('status', 'VALIDE')->where('labo_traitement', 'LOCAL')->count(),
            'a_refaire' => Prescription::where('status', 'A_REFAIRE')->where('labo_traitement', 'LOCAL')->count(),
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

            // Lancer le Job de notification
            \App\Jobs\NotifyPatientOfValidatedResults::dispatch($prescription);

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

    /**
     * Mettre à jour le commentaire d'une prescription déjà en mode 'A_REFAIRE'
     */
    public function updateComment(Request $request, int $id): RedirectResponse
    {
        $request->validate([
            'commentaire' => 'required|string|max:500',
        ]);

        try {
            $prescription = Prescription::findOrFail($id);

            if ($prescription->status !== 'A_REFAIRE') {
                return back()->with('error', 'Le commentaire ne peut être modifié que pour les dossiers à refaire.');
            }

            $prescription->update([
                'commentaire_biologiste' => $request->input('commentaire'),
                'updated_by' => Auth::id(),
            ]);

            return back()->with('success', 'Commentaire mis à jour avec succès.');
        } catch (\Exception $e) {
            return back()->with('error', 'Erreur lors de la mise à jour du commentaire.');
        }
    }
}
