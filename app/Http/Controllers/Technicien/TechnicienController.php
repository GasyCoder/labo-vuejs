<?php

namespace App\Http\Controllers\Technicien;

use App\Http\Controllers\Controller;
use App\Models\Prescription;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;

class TechnicienController extends Controller
{
    public function index(Request $request): Response
    {
        $tab = $request->input('tab', 'en_attente');
        $search = $request->input('search', '');

        $baseQuery = Prescription::with(['patient:id,nom,prenom', 'prescripteur:id,nom,prenom', 'analyses:id,code,designation'])
            ->withCount('analyses')
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
                        })
                        ->orWhereHas('analyses', function ($sq) use ($search) {
                            $sq->where('code', 'like', '%'.$search.'%')
                                ->orWhere('designation', 'like', '%'.$search.'%');
                        });
                });
            })
            ->orderBy('created_at', 'desc');

        // Paginate based on active tab
        switch ($tab) {
            case 'termine':
                $prescriptions = (clone $baseQuery)
                    ->where('status', 'TERMINE')
                    ->paginate(15)
                    ->withQueryString();
                break;
            case 'a_refaire':
                $prescriptions = (clone $baseQuery)
                    ->where('status', 'A_REFAIRE')
                    ->paginate(15)
                    ->withQueryString();
                break;
            default: // en_attente = EN_ATTENTE + EN_COURS
                $prescriptions = (clone $baseQuery)
                    ->whereIn('status', ['EN_ATTENTE', 'EN_COURS'])
                    ->paginate(15)
                    ->withQueryString();
                break;
        }

        // Stats
        $stats = [
            'en_attente' => Prescription::where('status', 'EN_ATTENTE')->count(),
            'en_cours' => Prescription::where('status', 'EN_COURS')->count(),
            'termine' => Prescription::where('status', 'TERMINE')->count(),
            'a_refaire' => Prescription::where('status', 'A_REFAIRE')->count(),
        ];
        $stats['toutes'] = $stats['en_attente'] + $stats['en_cours'];

        return Inertia::render('Technicien/Index', [
            'prescriptions' => $prescriptions,
            'stats' => $stats,
            'filters' => [
                'tab' => $tab,
                'search' => $search,
            ],
        ]);
    }

    public function startAnalysis(int $id)
    {
        try {
            DB::beginTransaction();

            $prescription = Prescription::findOrFail($id);

            if ($prescription->status !== 'EN_ATTENTE') {
                return back()->with('error', 'Cette prescription ne peut pas être traitée.');
            }

            $prescription->update([
                'status' => 'EN_COURS',
                'technicien_id' => Auth::id(),
                'date_debut_traitement' => now(),
            ]);

            DB::table('prescription_analyse')
                ->where('prescription_id', $id)
                ->update(['status' => 'EN_COURS', 'updated_at' => now()]);

            Log::info('Prescription passée en cours', [
                'prescription_id' => $id,
                'reference' => $prescription->reference,
                'user_id' => Auth::id(),
            ]);

            DB::commit();

            return redirect()->route('technicien.prescription.show', $prescription)
                ->with('success', 'Traitement de la prescription '.$prescription->reference.' commencé.');

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Erreur lors du démarrage de l\'analyse', [
                'prescription_id' => $id,
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'Erreur lors du démarrage : '.$e->getMessage());
        }
    }

    public function continueAnalysis(int $id)
    {
        $prescription = Prescription::findOrFail($id);

        if ($prescription->status !== 'EN_COURS') {
            return back()->with('error', 'Cette prescription ne peut pas être continuée.');
        }

        return redirect()->route('technicien.prescription.show', $prescription);
    }

    public function redoAnalysis(int $id)
    {
        try {
            DB::beginTransaction();

            $prescription = Prescription::findOrFail($id);

            if ($prescription->status !== 'A_REFAIRE') {
                return back()->with('error', 'Cette prescription ne peut pas être recommencée.');
            }

            $prescription->update([
                'status' => 'EN_COURS',
                'technicien_id' => Auth::id(),
                'commentaire_biologiste' => null,
                'date_debut_traitement' => now(),
                'date_reprise_traitement' => now(),
            ]);

            DB::table('prescription_analyse')
                ->where('prescription_id', $id)
                ->update(['status' => 'EN_COURS', 'updated_at' => now()]);

            Log::info('Prescription relancée pour un nouveau traitement', [
                'prescription_id' => $id,
                'reference' => $prescription->reference,
                'user_id' => Auth::id(),
            ]);

            DB::commit();

            return redirect()->route('technicien.prescription.show', $prescription)
                ->with('success', 'La prescription '.$prescription->reference.' a été relancée.');

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Erreur lors du recommencement de l\'analyse', [
                'prescription_id' => $id,
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'Erreur lors du recommencement : '.$e->getMessage());
        }
    }
}
