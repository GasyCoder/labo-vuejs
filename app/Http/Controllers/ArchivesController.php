<?php

namespace App\Http\Controllers;

use App\Models\Prescription;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class ArchivesController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->input('search', '');
        $statusFilter = $request->input('statusFilter', '');
        $dateFilter = $request->input('dateFilter', '');
        $prescripteurFilter = $request->input('prescripteurFilter', '');

        $countArchive = Prescription::where('status', Prescription::STATUS_ARCHIVE)->count();

        $prescriptions = Prescription::with(['patient', 'prescripteur', 'analyses'])
            ->archivees()
            ->when($search, function (Builder $query) use ($search) {
                $query->whereHas('patient', function (Builder $subQuery) use ($search) {
                    $subQuery->where('nom', 'like', '%'.$search.'%')
                        ->orWhere('prenom', 'like', '%'.$search.'%')
                        ->orWhere('reference', 'like', '%'.$search.'%')
                        ->orWhere('telephone', 'like', '%'.$search.'%');
                })
                    ->orWhereHas('prescripteur', function (Builder $subQuery) use ($search) {
                        $subQuery->where('nom', 'like', '%'.$search.'%');
                    });
            })
            ->when($prescripteurFilter, function (Builder $query) use ($prescripteurFilter) {
                $query->where('prescripteur_id', $prescripteurFilter);
            })
            ->when($dateFilter, function (Builder $query) use ($dateFilter) {
                switch ($dateFilter) {
                    case 'today':
                        $query->whereDate('created_at', today());
                        break;
                    case 'week':
                        $query->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()]);
                        break;
                    case 'month':
                        $query->whereMonth('created_at', now()->month)
                            ->whereYear('created_at', now()->year);
                        break;
                    case 'year':
                        $query->whereYear('created_at', now()->year);
                        break;
                }
            })
            ->latest('updated_at')
            ->paginate(15)
            ->withQueryString();

        $prescripteurs = User::where('type', 'prescripteur')
            ->whereHas('prescriptions', function (Builder $query) {
                $query->archivees();
            })
            ->get(['id', 'name', 'nom_complet']);

        // Stats for the top cards
        $archivesMoisActuel = Prescription::archivees()
            ->whereMonth('updated_at', now()->month)
            ->whereYear('updated_at', now()->year)
            ->count();

        $totalAnalyses = \DB::table('prescription_analyse')
            ->whereIn('prescription_id', function ($query) {
                $query->select('id')->from('prescriptions')->where('status', Prescription::STATUS_ARCHIVE);
            })->count();

        $patientsUniques = Prescription::archivees()
            ->distinct('patient_id')
            ->count('patient_id');

        return Inertia::render('Archives', [
            'prescriptions' => $prescriptions,
            'prescripteurs' => $prescripteurs,
            'countArchive' => $countArchive,
            'filters' => [
                'search' => $search,
                'statusFilter' => $statusFilter,
                'dateFilter' => $dateFilter,
                'prescripteurFilter' => $prescripteurFilter,
            ],
            'stats' => [
                'total' => $countArchive,
                'mois_actuel' => $archivesMoisActuel,
                'total_analyses' => $totalAnalyses,
                'patients_uniques' => $patientsUniques,
            ],
        ]);
    }

    public function unarchive(Request $request, Prescription $prescription)
    {
        if (! $this->canUnarchive($prescription)) {
            return back()->with('error', "Vous n'avez pas l'autorisation de désarchiver cette prescription.");
        }

        try {
            $prescription->unarchive();

            return back()->with('success', 'La prescription a été désarchivée avec succès.');
        } catch (\Exception $e) {
            return back()->with('error', 'Erreur lors de la désarchivage : '.$e->getMessage());
        }
    }

    public function permanentDelete(Request $request, Prescription $prescription)
    {
        if (! Auth::user()->hasPermission('archives.acceder')) {
            return back()->with('error', 'Seuls les administrateurs peuvent supprimer définitivement.');
        }

        try {
            $prescription->forceDelete();

            return back()->with('success', 'La prescription a été supprimée définitivement.');
        } catch (\Exception $e) {
            return back()->with('error', 'Erreur lors de la suppression : '.$e->getMessage());
        }
    }

    private function canUnarchive($prescription)
    {
        $user = Auth::user();

        if ($user->hasPermission('archives.acceder')) {
            return true;
        }

        if ($user->type === 'biologiste' && $prescription->prescripteur_id !== $user->id) {
            return false;
        }

        return true;
    }
}
