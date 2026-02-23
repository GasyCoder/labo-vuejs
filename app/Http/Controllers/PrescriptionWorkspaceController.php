<?php

namespace App\Http\Controllers;

use App\Models\Prescription;
use Inertia\Inertia;
use Inertia\Response;

class PrescriptionWorkspaceController extends Controller
{
    public function showTechnicien(Prescription $prescription): Response
    {
        return $this->renderWorkspace($prescription, 'technicien', 'Espace Technicien');
    }

    public function showBiologiste(Prescription $prescription): Response
    {
        return $this->renderWorkspace($prescription, 'biologiste', 'Espace Biologiste');
    }

    public function showBiologisteValidation(Prescription $prescription): Response
    {
        return $this->renderWorkspace($prescription, 'biologiste-validation', 'Validation Biologiste');
    }

    public function showAdmin(Prescription $prescription): Response
    {
        return $this->renderWorkspace($prescription, 'admin', 'Consultation Admin');
    }

    private function renderWorkspace(Prescription $prescription, string $context, string $title): Response
    {
        $prescription->load([
            'patient:id,nom,prenom,telephone',
            'prescripteur:id,nom',
            'analyses:id,designation',
        ]);

        return Inertia::render('Shared/PrescriptionWorkspace', [
            'context' => $context,
            'title' => $title,
            'prescription' => [
                'id' => $prescription->id,
                'reference' => $prescription->reference,
                'status' => $prescription->status,
                'created_at' => $prescription->created_at?->format('d/m/Y H:i'),
                'patient' => $prescription->patient ? [
                    'nom_complet' => trim(sprintf('%s %s', $prescription->patient->nom, $prescription->patient->prenom ?? '')),
                    'telephone' => $prescription->patient->telephone,
                ] : null,
                'prescripteur' => $prescription->prescripteur?->nom,
                'analyses_count' => $prescription->analyses->count(),
            ],
        ]);
    }
}
