<?php

namespace App\Http\Controllers\Secretaire;

use App\Http\Controllers\Controller;
use App\Models\Prescription;
use App\Services\NotificationService;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    protected NotificationService $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    /**
     * Envoyer un SMS au patient
     */
    public function sendSms(Request $request, Prescription $prescription)
    {
        $validated = $request->validate([
            'message' => 'required|string|max:160',
        ]);

        try {
            $this->notificationService->sendSms($prescription, $validated['message']);

            return back()->with('success', 'SMS envoyé avec succès.');
        } catch (\Exception $e) {
            return back()->with('error', 'Erreur lors de l\'envoi du SMS : '.$e->getMessage());
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
            $this->notificationService->sendEmail($prescription, $validated['message'], $validated['lien_pdf'] ?? '');

            return back()->with('success', 'E-mail envoyé avec succès.');
        } catch (\Exception $e) {
            return back()->with('error', 'Erreur lors de l\'envoi de l\'e-mail : '.$e->getMessage());
        }
    }
}
