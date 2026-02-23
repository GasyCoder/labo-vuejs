<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Patient;
use App\Models\Prescription;
use Illuminate\Http\Request;
use Inertia\Inertia;

class TracePatientController extends Controller
{
    /**
     * Display a listing of softly deleted patients and prescriptions.
     */
    public function index(Request $request)
    {
        $search = $request->input('search');
        $activeTab = $request->input('tab', 'prescriptions'); // prescriptions or patients
        $perPage = $request->input('perPage', 10);

        $patients = Patient::onlyTrashed()
            ->withCount('prescriptions')
            ->when($search, function ($query, $search) {
                $query->where('numero_dossier', 'like', "%{$search}%")
                    ->orWhere('nom', 'like', "%{$search}%")
                    ->orWhere('prenom', 'like', "%{$search}%")
                    ->orWhere('telephone', 'like', "%{$search}%");
            })
            ->orderBy('deleted_at', 'desc')
            ->paginate($perPage)
            ->withQueryString();

        $prescriptions = Prescription::onlyTrashed()
            ->with(['patient' => function ($query) {
                $query->withTrashed();
            }, 'prescripteur'])
            ->when($search, function ($query, $search) {
                $query->where('reference', 'like', "%{$search}%")
                    ->orWhereHas('patient', function ($q) use ($search) {
                        $q->withTrashed()
                            ->where('nom', 'like', "%{$search}%")
                            ->orWhere('prenom', 'like', "%{$search}%")
                            ->orWhere('numero_dossier', 'like', "%{$search}%");
                    })
                    ->orWhereHas('prescripteur', function ($q) use ($search) {
                        $q->where('nom', 'like', "%{$search}%");
                    });
            })
            ->orderBy('deleted_at', 'desc')
            ->paginate($perPage)
            ->withQueryString();

        return Inertia::render('Admin/TracePatient', [
            'patients' => $patients,
            'prescriptions' => $prescriptions,
            'filters' => [
                'search' => $search,
                'tab' => $activeTab,
            ],
            'stats' => $this->getStats(),
        ]);
    }

    private function getStats()
    {
        $patientsCount = Patient::onlyTrashed()->count();
        $prescriptionsCount = Prescription::onlyTrashed()->count();

        return [
            'patients' => [
                'total' => $patientsCount,
                'recent' => Patient::onlyTrashed()->where('deleted_at', '>=', now()->subDays(7))->count(),
                'old' => Patient::onlyTrashed()->where('deleted_at', '<', now()->subDays(30))->count(),
                'with_prescriptions' => Patient::onlyTrashed()->has('prescriptions')->count(),
            ],
            'prescriptions' => [
                'total' => $prescriptionsCount,
                'recent' => Prescription::onlyTrashed()->where('deleted_at', '>=', now()->subDays(7))->count(),
                'old' => Prescription::onlyTrashed()->where('deleted_at', '<', now()->subDays(30))->count(),
                'valeur_totale' => Prescription::onlyTrashed()->sum('montant_total'),
            ],
            'total' => $patientsCount + $prescriptionsCount,
        ];
    }

    // --- PATIENTS METHODS ---

    public function restorePatient(Patient $patient)
    {
        // Must use $patient since implicit binding might not find softly deleted models unless explicitly configured
        // Best approach: use ID explicitly
    }

    public function restorePatientById($id)
    {
        $patient = Patient::onlyTrashed()->findOrFail($id);
        $patient->restore();

        return redirect()->back()->with('success', 'Patient restauré avec succès!');
    }

    public function forceDeletePatientById($id)
    {
        $patient = Patient::onlyTrashed()->findOrFail($id);
        $patient->forceDeleteWithRelations();

        return redirect()->back()->with('success', 'Patient et toutes ses données définitivement supprimés!');
    }

    public function restoreMultiplePatients(Request $request)
    {
        $validated = $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'integer',
        ]);

        Patient::onlyTrashed()->whereIn('id', $validated['ids'])->restore();

        return redirect()->back()->with('success', 'Patients sélectionnés restaurés!');
    }

    public function forceDeleteMultiplePatients(Request $request)
    {
        $validated = $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'integer',
        ]);

        $patients = Patient::onlyTrashed()->whereIn('id', $validated['ids'])->get();

        foreach ($patients as $patient) {
            $patient->forceDeleteWithRelations();
        }

        return redirect()->back()->with('success', 'Patients sélectionnés définitivement supprimés!');
    }

    public function emptyPatientsTrash()
    {
        $patients = Patient::onlyTrashed()->get();

        foreach ($patients as $patient) {
            $patient->forceDeleteWithRelations();
        }

        return redirect()->back()->with('success', 'Corbeille des patients vidée avec succès!');
    }

    // --- PRESCRIPTIONS METHODS ---

    public function restorePrescriptionById($id)
    {
        $prescription = Prescription::onlyTrashed()->findOrFail($id);
        $prescription->restore();

        return redirect()->back()->with('success', 'Prescription restaurée avec succès!');
    }

    public function forceDeletePrescriptionById($id)
    {
        $prescription = Prescription::onlyTrashed()->findOrFail($id);
        $prescription->forceDeleteWithRelations();

        return redirect()->back()->with('success', 'Prescription définitivement supprimée!');
    }

    public function restoreMultiplePrescriptions(Request $request)
    {
        $validated = $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'integer',
        ]);

        Prescription::onlyTrashed()->whereIn('id', $validated['ids'])->restore();

        return redirect()->back()->with('success', 'Prescriptions sélectionnées restaurées!');
    }

    public function forceDeleteMultiplePrescriptions(Request $request)
    {
        $validated = $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'integer',
        ]);

        $prescriptions = Prescription::onlyTrashed()->whereIn('id', $validated['ids'])->get();

        foreach ($prescriptions as $prescription) {
            $prescription->forceDeleteWithRelations();
        }

        return redirect()->back()->with('success', 'Prescriptions sélectionnées définitivement supprimées!');
    }

    public function emptyPrescriptionsTrash()
    {
        $prescriptions = Prescription::onlyTrashed()->get();

        foreach ($prescriptions as $prescription) {
            $prescription->forceDeleteWithRelations();
        }

        return redirect()->back()->with('success', 'Corbeille des prescriptions vidée avec succès!');
    }
}
