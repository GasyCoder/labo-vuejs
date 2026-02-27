<?php

use App\Http\Controllers\Admin\ClientFeatureController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\PrescriptionTrackingController;
use App\Http\Controllers\Admin\ApiSettingController;
use App\Http\Controllers\ArchivesController;
use App\Http\Controllers\Biologiste\BiologisteController;
use App\Http\Controllers\JournalCaisseController;
use App\Http\Controllers\JournalDecaissementController;
use App\Http\Controllers\Laboratoire\AnalyseController;
use App\Http\Controllers\Laboratoire\AntibiotiqueController;
use App\Http\Controllers\Laboratoire\BacterieController;
use App\Http\Controllers\Laboratoire\BacterieFamilleController;
use App\Http\Controllers\Laboratoire\ExamenController;
use App\Http\Controllers\Laboratoire\PrelevementController;
use App\Http\Controllers\Laboratoire\TypeController;
use App\Http\Controllers\Secretaire\PatientController;
use App\Http\Controllers\Admin\PermissionController;
use App\Http\Controllers\Secretaire\PrescriptionController;
use App\Http\Controllers\Admin\PrescriptionExportController;
use App\Http\Controllers\Biologiste\PrescriptionPdfController;
use App\Http\Controllers\PrescriptionWorkspaceController;
use App\Http\Controllers\ResultatController;
use App\Http\Controllers\Technicien\ResultatController as TechnicienResultatController;
use App\Http\Controllers\RoleWorklistController;
use App\Http\Controllers\Secretaire\NotificationController;
use App\Http\Controllers\Secretaire\PaiementController;
use App\Http\Controllers\Secretaire\PrescripteurController;
use App\Http\Controllers\Secretaire\Tubes\GestionEtiquettesController;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\Technicien\TechnicienController;
use App\Http\Controllers\Admin\TracePatientController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\PdfBrandingController;
use App\Http\Controllers\Admin\LogController;
use App\Models\Prescription;
use Barryvdh\DomPDF\Facade\Pdf as PDF;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

// ============================================
// ROUTES PUBLIQUES ET REDIRECTIONS
// ============================================
Route::redirect('/', '/login')->name('home');
Route::redirect('/register', '/login')->name('register.redirect');

Route::get('/', function () {
    if (Auth::check()) {
        $user = Auth::user();

        // Si l'utilisateur a la permission dashboard, on l'envoie là-bas
        if ($user->hasPermission('dashboard.voir')) {
            return redirect()->route('dashboard');
        }

        if ($user->hasRole('biologiste')) {
            return redirect()->route('biologiste.analyse.index');
        }
        if ($user->hasRole('technicien')) {
            return redirect()->route('technicien.index');
        }
        if ($user->hasRole('secretaire')) {
            return redirect()->route('secretaire.prescription.index');
        }

        return redirect()->route('dashboard');
    }

    return redirect('/login');
})->name('root');

// ============================================
// ROUTES COMMUNES (TOUS LES UTILISATEURS CONNECTÉS)
// ============================================
Route::middleware(['auth', 'verified', 'role.redirect'])->group(function () {
    // Dashboard principal
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Suivi Opérationnel des Prescriptions (Gated by Premium Feature)
    Route::middleware('feature:prescriptions_tracking')
        ->get('/prescriptions-tracking', [PrescriptionTrackingController::class, 'index'])
        ->name('admin.prescriptions-tracking.index');

    // Archives
    Route::get('/archives', [ArchivesController::class, 'index'])->name('archives');
    Route::post('/archives/{prescription}/unarchive', [ArchivesController::class, 'unarchive'])->name('archives.unarchive');
    Route::delete('/archives/{prescription}', [ArchivesController::class, 'permanentDelete'])->name('archives.destroy');
});

// ============================================
// ROUTES SPÉCIFIQUES AUX SECRÉTAIRES
// ============================================
Route::middleware(['auth', 'verified', 'role:secretaire,superadmin,admin'])->prefix('secretaire')->name('secretaire.')->group(function () {
    Route::controller(PrescriptionController::class)->group(function () {
        Route::get('prescription/listes', 'index')->name('prescription.index');
        Route::get('nouvel-prescription', 'create')->name('prescription.create');
        Route::get('prescription/workspace/lookup/patients', 'searchPatients')->name('prescription.lookup.patients');
        Route::get('prescription/workspace/lookup/analyses', 'searchAnalyses')->name('prescription.lookup.analyses');
        Route::get('prescription/workspace/lookup/prelevements', 'searchPrelevements')->name('prescription.lookup.prelevements');
        Route::post('prescription/workspace/store', 'store')->name('prescription.store');
        Route::get('prescription/edit/{prescriptionId}', 'edit')->name('prescription.edit');
        Route::delete('prescription/{id}', 'destroy')->name('prescription.destroy');
        Route::post('prescription/{id}/restore', 'restore')->name('prescription.restore');
        Route::delete('prescription/{id}/force-delete', 'forceDelete')->name('prescription.forceDelete');
        Route::post('prescription/{id}/archive', 'archive')->name('prescription.archive');
        Route::post('prescription/{id}/unarchive', 'unarchive')->name('prescription.unarchive');
        Route::post('prescription/{id}/toggle-payment', 'togglePayment')->name('prescription.togglePayment');
        Route::post('prescription/{id}/notify', 'notify')->name('prescription.notify');
    });

    Route::get('/prescription/{prescription}/facture', function (Prescription $prescription) {
        $prescription->load(['patient', 'prescripteur', 'analyses', 'prelevements', 'paiements.paymentMethod', 'secretaire']);

        $pdf = PDF::loadView('factures.pdf-template', compact('prescription'))
            ->setPaper('a4', 'portrait')
            ->setOptions(['isHtml5ParserEnabled' => true, 'isRemoteEnabled' => true]);

        return $pdf->stream("facture-{$prescription->reference}.pdf");
    })->name('prescription.facture');

    Route::controller(PatientController::class)->group(function () {
        Route::get('patients', 'index')->name('patients.index');
        Route::get('patients/{patient}', 'show')->name('patient.detail');
        Route::put('patients/{patient}', 'update')->name('patient.update');
        Route::delete('patients/{patient}', 'destroy')->name('patient.destroy');
        Route::post('prescriptions/{prescription}/send-invoice', 'sendInvoice')
            ->name('patient.send-invoice')
            ->middleware('feature:patient_invoice_email');
    });
    Route::controller(PrescripteurController::class)->group(function () {
        Route::get('prescripteurs', 'index')->name('prescripteurs.index');
        Route::post('prescripteurs', 'store')->name('prescripteurs.store');
        Route::put('prescripteurs/{prescripteur}', 'update')->name('prescripteurs.update');
        Route::delete('prescripteurs/{prescripteur}', 'destroy')->name('prescripteurs.destroy');
        Route::post('prescripteurs/{prescripteur}/toggle-status', 'toggleStatus')->name('prescripteurs.toggle-status');
        Route::get('prescripteurs/export/csv', 'export')->name('prescripteurs.export');
        Route::get('prescripteurs/{prescripteur}/commissions/pdf', 'generateCommissionPDF')->name('prescripteurs.commissions.pdf');
        Route::get('prescripteurs/{prescripteur}/commissions/api', 'getCommissions')->name('prescripteurs.commissions.api');
    });
    Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Route pour afficher le journal de caisse
    Route::get('/journal-caisse', [JournalCaisseController::class, 'index'])->name('journal-caisse');
    Route::get('/journal-caisse/export', [JournalCaisseController::class, 'exportPdf'])->name('journal-caisse.export');
    // Journal de decaissement (Gated by Premium Feature)
    Route::middleware('feature:journal_decaissement')
        ->get('journal-decaissement', [JournalDecaissementController::class, 'index'])
        ->name('journal-decaissement');
    Route::get('/journal-decaissement/export', [JournalDecaissementController::class, 'exportPdf'])->name('journal-decaissement.export');
    Route::controller(GestionEtiquettesController::class)->group(function () {
        Route::get('/secretaire/etiquettes', 'index')->name('etiquettes');
        Route::get('/secretaire/etiquettes/export', 'exportPdf')->name('etiquettes.export');
        Route::post('/secretaire/etiquettes/{id}/receptionner', 'marquerReceptionne')->name('etiquettes.marquer.reception');
    });

    // Paiements
    Route::controller(PaiementController::class)->group(function () {
        Route::post('/paiements/{paiement}/pay', 'markAsPaid')->name('paiement.mark-paid');
        Route::post('/paiements/{paiement}/unpay', 'markAsUnpaid')->name('paiement.mark-unpaid');
    });

    // Notifications
    Route::controller(NotificationController::class)
        ->middleware('feature:notifications_sms_email_validated')
        ->group(function () {
            Route::post('/prescriptions/{prescription}/sms', 'sendSms')->name('prescription.send-sms');
            Route::post('/prescriptions/{prescription}/email', 'sendEmail')->name('prescription.send-email');
        });
});

// ============================================ Résultats PDF prescriptions
// ROUTES SPÉCIFIQUES AUX SECRETAIRES, BIOLOGISTES
// ============================================
Route::middleware(['auth', 'verified', 'role:secretaire,biologiste,superadmin,admin'])->prefix('laboratoire')->name('laboratoire.')->group(function () {
    Route::get('/prescription/{prescription}/pdf', [PrescriptionPdfController::class, 'show'])
        ->name('prescription.pdf');
});

// ============================================
// ROUTES SPÉCIFIQUES AUX TECHNICIENS
// ============================================
Route::middleware(['auth', 'verified', 'role:technicien,superadmin,admin'])->prefix('technicien')->name('technicien.')->group(function () {
    Route::get('traitement', [TechnicienController::class, 'index'])->name('index');
    Route::post('prescription/{id}/start', [TechnicienController::class, 'startAnalysis'])->name('prescription.start');
    Route::post('prescription/{id}/continue', [TechnicienController::class, 'continueAnalysis'])->name('prescription.continue');
    Route::post('prescription/{id}/redo', [TechnicienController::class, 'redoAnalysis'])->name('prescription.redo');
    Route::get('/technicien/prescription/{prescription}', [PrescriptionWorkspaceController::class, 'showTechnicien'])->name('prescription.show');
    Route::get('prescription/{prescription}/progression', [PrescriptionWorkspaceController::class, 'getProgression'])->name('prescription.progression');

    // API endpoints pour la saisie des résultats
    Route::post('resultats/save', [TechnicienResultatController::class, 'save'])->name('resultats.save');
    Route::post('resultats/save-all', [TechnicienResultatController::class, 'saveAll'])->name('resultats.saveAll');

    // Nouveaux endpoints pour les notes et conclusions
    Route::post('resultats/group-conclusion', [TechnicienResultatController::class, 'saveGroupConclusion'])->name('resultats.groupConclusion');
    Route::post('resultats/notes', [TechnicienResultatController::class, 'addConclusionNote'])->name('resultats.addNote');
    Route::put('resultats/notes/{id}', [TechnicienResultatController::class, 'updateConclusionNote'])->name('resultats.updateNote');
    Route::delete('resultats/notes/{id}', [TechnicienResultatController::class, 'deleteConclusionNote'])->name('resultats.deleteNote');

    // Nouveaux endpoints pour Antibiogrammes (Germe/Culture)
    Route::post('resultats/antibiogrammes/sync', [TechnicienResultatController::class, 'syncAntibiogrammes'])->name('resultats.antibiogrammes.sync');
    Route::post('resultats/antibiogrammes/data', [TechnicienResultatController::class, 'getAntibiogrammesData'])->name('resultats.antibiogrammes.data');
    Route::post('resultats/antibiogrammes', [TechnicienResultatController::class, 'addAntibiotique'])->name('resultats.antibiogrammes.add');
    Route::put('resultats/antibiogrammes/{id}', [TechnicienResultatController::class, 'updateResultatAntibiotique'])->name('resultats.antibiogrammes.update');
    Route::delete('resultats/antibiogrammes/{id}', [TechnicienResultatController::class, 'deleteResultatAntibiotique'])->name('resultats.antibiogrammes.delete');

    Route::post('analyse/{id}/complete', [TechnicienResultatController::class, 'completeAnalyse'])->name('analyse.complete');
    Route::post('prescription/{id}/complete', [TechnicienResultatController::class, 'completePrescription'])->name('prescription.finalize');
});

// ============================================
// ROUTES SPÉCIFIQUES AUX BIOLOGISTES
// ============================================
Route::middleware(['auth', 'verified', 'role:biologiste,superadmin,admin'])->prefix('biologiste')->name('biologiste.')->group(function () {
    // Routes principales
    Route::get('/analyse-valide', [RoleWorklistController::class, 'biologiste'])->name('analyse.index');
    Route::get('/prescription/{prescription}', [PrescriptionWorkspaceController::class, 'showBiologiste'])->name('prescription.show');
    Route::get('/valide/{prescription}/analyse', [PrescriptionWorkspaceController::class, 'showBiologisteValidation'])->name('valide.show');

    // Validation actions
    Route::post('/prescription/{id}/validate', [BiologisteController::class, 'validatePrescription'])->name('prescription.validate');
    Route::post('/prescription/{id}/reject', [BiologisteController::class, 'rejectPrescription'])->name('prescription.reject');
});

// ============================================
// ROUTES SPÉCIFIQUES AUX ADMINS, BIOLOGISTES, TECHNICIENS
// ============================================
Route::middleware(['auth', 'verified', 'role:technicien,biologiste,superadmin,admin'])->prefix('laboratoire')->name('laboratoire.')->group(function () {
    // Section Analyses
    Route::prefix('analyses')->name('analyses.')->group(function () {
        Route::controller(ExamenController::class)->group(function () {
            Route::get('examens', 'index')->name('examens');
            Route::post('examens', 'store')->name('examens.store');
            Route::put('examens/{examen}', 'update')->name('examens.update');
            Route::delete('examens/{examen}', 'destroy')->name('examens.destroy');
        });
        Route::controller(TypeController::class)->group(function () {
            Route::get('types', 'index')->name('types');
            Route::post('types', 'store')->name('types.store');
            Route::put('types/{type}', 'update')->name('types.update');
            Route::post('types/{type}/toggle', 'toggleStatus')->name('types.toggle');
            Route::delete('types/{type}', 'destroy')->name('types.destroy');
        });
        Route::controller(AnalyseController::class)->group(function () {
            Route::get('listes', 'index')->name('listes');
            Route::post('listes', 'store')->name('listes.store');
            Route::put('listes/{analyse}', 'update')->name('listes.update');
            Route::post('listes/{analyse}/toggle', 'toggleStatus')->name('listes.toggle');
            Route::delete('listes/{analyse}', 'destroy')->name('listes.destroy');
        });
        Route::controller(PrelevementController::class)->group(function () {
            Route::get('prelevements', 'index')->name('prelevements');
            Route::post('prelevements', 'store')->name('prelevements.store');
            Route::put('prelevements/{prelevement}', 'update')->name('prelevements.update');
            Route::post('prelevements/{prelevement}/toggle', 'toggleStatus')->name('prelevements.toggle');
            Route::delete('prelevements/{prelevement}', 'destroy')->name('prelevements.destroy');
        });
    });

    // Section Microbiologie
    Route::prefix('microbiologie')->name('microbiologie.')->group(function () {
        Route::controller(BacterieFamilleController::class)->group(function () {
            Route::get('familles-bacteries', 'index')->name('familles-bacteries');
            Route::post('familles-bacteries', 'store')->name('familles-bacteries.store');
            Route::put('familles-bacteries/{bacterieFamille}', 'update')->name('familles-bacteries.update');
            Route::post('familles-bacteries/{bacterieFamille}/toggle', 'toggleStatus')->name('familles-bacteries.toggle');
            Route::delete('familles-bacteries/{bacterieFamille}', 'destroy')->name('familles-bacteries.destroy');
        });
        Route::controller(BacterieController::class)->group(function () {
            Route::get('bacteries', 'index')->name('bacteries');
            Route::post('bacteries', 'store')->name('bacteries.store');
            Route::put('bacteries/{bacterie}', 'update')->name('bacteries.update');
            Route::post('bacteries/{bacterie}/toggle', 'toggleStatus')->name('bacteries.toggle');
            Route::delete('bacteries/{bacterie}', 'destroy')->name('bacteries.destroy');
        });
        Route::controller(AntibiotiqueController::class)->group(function () {
            Route::get('antibiotiques', 'index')->name('antibiotiques');
            Route::post('antibiotiques', 'store')->name('antibiotiques.store');
            Route::put('antibiotiques/{antibiotique}', 'update')->name('antibiotiques.update');
            Route::post('antibiotiques/{antibiotique}/toggle', 'toggleStatus')->name('antibiotiques.toggle');
            Route::delete('antibiotiques/{antibiotique}', 'destroy')->name('antibiotiques.destroy');
        });
    });
});

// ============================================
// ROUTES SPÉCIFIQUES AUX ADMINS
// ============================================
Route::middleware(['auth', 'verified', 'role:superadmin,admin'])->prefix('admin')->name('admin.')->group(function () {
    // Utilisateurs
    Route::controller(UserController::class)->group(function () {
        Route::get('utilisateurs', 'index')->name('users');
        Route::post('utilisateurs', 'store')->name('users.store');
        Route::put('utilisateurs/{user}', 'update')->name('users.update');
        Route::delete('utilisateurs/{user}', 'destroy')->name('users.destroy');
        Route::post('utilisateurs/{user}/logout', 'logoutSession')->name('users.logout');
    });

    // ==== ROUTES STRICTEMENT RÉSERVÉES AU SUPER ADMIN ====
    Route::middleware(['role:superadmin'])->group(function () {
        // Permissions
        Route::controller(PermissionController::class)->group(function () {
            Route::get('permissions', 'index')->name('permissions');
            Route::put('permissions', 'update')->name('permissions.update');
        });
        
        // Administration des fonctionnalités Premium SaaS
        Route::prefix('features')->name('features.')->group(function () {
            Route::get('/', [ClientFeatureController::class, 'index'])->name('index');
            Route::get('/{client}/edit', [ClientFeatureController::class, 'edit'])->name('edit');
            Route::put('/{client}', [ClientFeatureController::class, 'update'])->name('update');
        });
        Route::get('parametres', [SettingController::class, 'index'])->name('settings');
        Route::controller(SettingController::class)->prefix('parametres')->name('settings.')->group(function () {
            Route::post('entreprise', 'updateEnterprise')->name('enterprise');
            Route::post('remove-image', 'removeImage')->name('remove-image');
            Route::post('discount', 'updateDiscount')->name('discount');
            Route::post('commission', 'updateCommission')->name('commission');
            Route::post('emergency', 'updateEmergency')->name('emergency');
            Route::post('payment-method', 'storePaymentMethod')->name('payment-method.store');
            Route::put('payment-method/{paymentMethod}', 'updatePaymentMethod')->name('payment-method.update');
            Route::delete('payment-method/{paymentMethod}', 'destroyPaymentMethod')->name('payment-method.destroy');
            Route::post('test-sms', 'testSms')->name('test-sms');
            Route::post('test-email', 'testEmail')->name('test-email');
            Route::post('api-config', 'updateApiConfig')->name('api-config');
        });

        // Configuration API (Email & SMS) - Page séparée
        Route::get('api-settings', [ApiSettingController::class, 'index'])->name('api-settings');
        Route::get('pdf-branding', [PdfBrandingController::class, 'index'])->name('pdf-branding');
        Route::post('pdf-branding', [PdfBrandingController::class, 'store'])->name('pdf-branding.store');
        Route::get('logs-viewer', [LogController::class, 'index'])->name('logs.viewer');
        Route::post('logs-viewer/{file}/clear', [LogController::class, 'clear'])->name('logs.clear');
        Route::get('logs-viewer/{file}/download', [LogController::class, 'download'])->name('logs.download');
        Route::delete('logs-viewer/{file}', [LogController::class, 'delete'])->name('logs.delete');
        Route::controller(ApiSettingController::class)->prefix('api-settings')->name('api-settings.')->group(function () {
            Route::post('email', 'updateEmailConfig')->name('update-email');
            Route::post('sms', 'storeSmsProvider')->name('store-sms');
            Route::put('sms/{provider}', 'updateSmsProvider')->name('update-sms');
            Route::delete('sms/{provider}', 'destroySmsProvider')->name('destroy-sms');
            Route::post('sms/{provider}/activate', 'activateSmsProvider')->name('activate-sms');
            Route::post('test-sms', 'testSms')->name('test-sms');
            Route::post('test-email', 'testEmail')->name('test-email');
        });
    });

    // Traçabilité des patients
    Route::get('trace-patients', [TracePatientController::class, 'index'])->name('trace-patients');
    Route::controller(TracePatientController::class)->prefix('trace-patients')->name('trace-patient.')->group(function () {
        Route::post('patients/{id}/restore', 'restorePatientById')->name('patients.restore');
        Route::delete('patients/{id}/force-delete', 'forceDeletePatientById')->name('patients.force-delete');
        Route::delete('patients/empty', 'emptyPatientsTrash')->name('patients.empty');

        Route::post('prescriptions/{id}/restore', 'restorePrescriptionById')->name('prescriptions.restore');
        Route::delete('prescriptions/{id}/force-delete', 'forceDeletePrescriptionById')->name('prescriptions.force-delete');
        Route::delete('prescriptions/empty', 'emptyPrescriptionsTrash')->name('prescriptions.empty');
    });

    // Ajout de la route pour voir une prescription en tant qu'admin
    Route::get('/prescription/{prescription}', [PrescriptionWorkspaceController::class, 'showAdmin'])->name('prescriptions.show');

    // Export CSV des prescriptions
    Route::get('prescriptions/export', [PrescriptionExportController::class, 'export'])->name('prescriptions.export');
    Route::post('prescriptions/export/email', [PrescriptionExportController::class, 'sendEmail'])->name('prescriptions.export.email');
});

// ============================================
// ROUTE SIGNÉE - TÉLÉCHARGEMENT PDF RÉSULTATS
// ============================================
Route::get('/resultats/{prescription}/download', [ResultatController::class, 'download'])
    ->name('resultats.download')
    ->middleware('signed');

require __DIR__.'/auth.php';
