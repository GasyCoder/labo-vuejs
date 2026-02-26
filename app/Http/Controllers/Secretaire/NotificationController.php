<?php

namespace App\Http\Controllers\Secretaire;

use App\Http\Controllers\Controller;
use App\Models\Prescription;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    /**
     * Envoyer un SMS au patient
     */
    public function sendSms(Request $request, Prescription $prescription)
    {
        $validated = $request->validate([
            'message' => 'required|string|max:160',
        ]);

        try {
            \App\Jobs\SendManualNotification::dispatch($prescription, 'sms', $validated['message']);

            return back()->with('success', 'La notification SMS a été ajoutée à la file d\'attente.');
        } catch (\Exception $e) {
            return back()->with('error', 'Erreur lors de la mise en file d\'attente du SMS : '.$e->getMessage());
        }
    }

    /**
     * Envoyer un Email au patient
     */
    public function sendEmail(Request $request, Prescription $prescription)
    {
        $validated = $request->validate([
            'message' => 'required|string',
            'lien_pdf' => 'nullable|string',
        ]);

        try {
            \App\Jobs\SendManualNotification::dispatch($prescription, 'email', $validated['message'], $validated['lien_pdf'] ?? null);

            return back()->with('success', 'L\'e-mail a été ajouté à la file d\'attente.');
        } catch (\Exception $e) {
            return back()->with('error', 'Erreur lors de la mise en file d\'attente de l\'e-mail : '.$e->getMessage());
        }
    }
}
