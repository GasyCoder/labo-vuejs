<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Prescription;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PrescriptionExportController extends Controller
{
    public function export(Request $request): StreamedResponse
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
                            ->orWhere('prenom', 'like', "%{$prescriptionSearch}%");
                    });
            });
        }

        $prescriptions = $query->get();

        $filename = 'prescriptions_'.now()->format('Y-m-d_His').'.csv';

        return response()->streamDownload(function () use ($prescriptions) {
            $handle = fopen('php://output', 'w');

            // BOM for Excel UTF-8
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));

            fputcsv($handle, [
                'Référence',
                'Date',
                'Patient',
                'N° Dossier',
                'Secrétaire',
                'Prescripteur',
                'Analyses',
                'Statut',
                'Montant Total (Ar)',
                'Montant Payé (Ar)',
                'Reste (Ar)',
                'Statut Paiement',
            ], ';');

            foreach ($prescriptions as $p) {
                $montantTotal = $p->montant_total;
                $montantPaye = $p->paiements->sum('montant');
                $reste = max(0, $montantTotal - $montantPaye);

                $paymentStatus = match (true) {
                    $montantPaye >= $montantTotal && $montantTotal > 0 => 'Payé',
                    $montantPaye > 0 => 'Partiel',
                    default => 'Impayé',
                };

                $analysesCodes = $p->analyses->pluck('code')->unique()->implode(', ');

                fputcsv($handle, [
                    $p->reference,
                    $p->created_at->format('d/m/Y H:i'),
                    ($p->patient->nom ?? '').' '.($p->patient->prenom ?? ''),
                    $p->patient->numero_dossier ?? '',
                    $p->secretaire->name ?? 'N/A',
                    $p->prescripteur->nom ?? 'N/A',
                    $analysesCodes,
                    $p->status_label,
                    number_format($montantTotal, 0, ',', ' '),
                    number_format($montantPaye, 0, ',', ' '),
                    number_format($reste, 0, ',', ' '),
                    $paymentStatus,
                ], ';');
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}
