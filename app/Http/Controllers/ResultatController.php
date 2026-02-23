<?php

namespace App\Http\Controllers;

use App\Models\Prescription;
use App\Services\ResultatPdfShow;
use Illuminate\Support\Facades\Log;

class ResultatController extends Controller
{
    public function download(Prescription $prescription, ResultatPdfShow $pdfService)
    {
        try {
            $hasValidatedResults = $prescription->resultats()
                ->where('status', 'VALIDE')
                ->whereNotNull('validated_by')
                ->exists();

            if (! $hasValidatedResults) {
                abort(404, 'Aucun résultat validé trouvé.');
            }

            $pdfUrl = $pdfService->generateFinalPDF($prescription);

            $filename = 'resultats-'.$prescription->reference.'.pdf';
            $filePath = storage_path('app/public/pdfs/'.basename($pdfUrl));

            if (! file_exists($filePath)) {
                abort(404, 'Fichier PDF non trouvé.');
            }

            return response()->download($filePath, $filename);

        } catch (\Exception $e) {
            Log::error('Erreur téléchargement PDF signé:', [
                'prescription_id' => $prescription->id,
                'error' => $e->getMessage(),
            ]);

            abort(500, 'Erreur lors du téléchargement du PDF.');
        }
    }
}
