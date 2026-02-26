<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Prescription;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class PrescriptionTrackingController extends Controller
{
    /**
     * Display the operational prescription tracking table.
     */
    public function index(Request $request)
    {
        $user = Auth::user();

        if (! $user) {
            abort(401, 'Utilisateur non connecté');
        }

        if (! in_array($user->type, ['superadmin', 'admin', 'secretaire'])) {
            abort(403, 'Accès non autorisé');
        }

        return Inertia::render('Admin/PrescriptionsTracking', [
            'prescriptionsList' => $this->getPrescriptionsList($request),
            'secretaires' => \App\Models\User::where('type', 'secretaire')
                ->orWhere('type', 'superadmin')
                ->orWhere('type', 'admin')
                ->orderBy('name')
                ->get(['id', 'name', 'type']),
            'prescriptionsFilters' => [
                'date_from' => $request->input('date_from', ''),
                'date_to' => $request->input('date_to', ''),
                'prescriptionSearch' => $request->input('prescriptionSearch', ''),
                'secretaire_id' => $request->input('secretaire_id', ''),
                'prescriptionsPerPage' => $request->input('prescriptionsPerPage', 15),
            ],
        ]);
    }

    private function getPrescriptionsList(Request $request)
    {
        $query = Prescription::with(['patient', 'secretaire', 'prescripteur', 'paiements', 'analyses'])
            ->orderBy('created_at', 'desc');

        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');

        if ($dateFrom && $dateTo) {
            $query->whereBetween('created_at', [
                Carbon::parse($dateFrom)->startOfDay(),
                Carbon::parse($dateTo)->endOfDay(),
            ]);
        } elseif ($dateFrom) {
            $query->whereDate('created_at', '>=', $dateFrom);
        } elseif ($dateTo) {
            $query->whereDate('created_at', '<=', $dateTo);
        }

        $secretaireId = $request->input('secretaire_id');
        if ($secretaireId) {
            $query->where('secretaire_id', $secretaireId);
        }

        $prescriptionSearch = $request->input('prescriptionSearch');
        if ($prescriptionSearch) {
            $query->where(function ($q) use ($prescriptionSearch) {
                $q->where('reference', 'like', "%{$prescriptionSearch}%")
                    ->orWhereHas('patient', function ($pq) use ($prescriptionSearch) {
                        $pq->where('nom', 'like', "%{$prescriptionSearch}%")
                            ->orWhere('prenom', 'like', "%{$prescriptionSearch}%")
                            ->orWhere('numero_dossier', 'like', "%{$prescriptionSearch}%");
                    });
            });
        }

        $perPage = $request->input('prescriptionsPerPage', 15);

        return $query->paginate($perPage, ['*'], 'prescriptions_page')->withQueryString();
    }
}
