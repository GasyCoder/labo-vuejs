<?php

namespace App\Http\Controllers\Secretaire;

use App\Http\Controllers\Controller;
use App\Models\Paiement;

class PaiementController extends Controller
{
    /**
     * Marquer un paiement comme payé
     */
    public function markAsPaid(Paiement $paiement)
    {
        $paiement->update([
            'status' => true,
            'date_paiement' => now(),
        ]);

        return back()->with('success', 'Paiement enregistré avec succès.');
    }

    /**
     * Marquer un paiement comme non payé
     */
    public function markAsUnpaid(Paiement $paiement)
    {
        $paiement->update([
            'status' => false,
            'date_paiement' => null,
        ]);

        return back()->with('success', 'Paiement annulé avec succès.');
    }
}
