<?php

use App\Http\Controllers\Admin\PermissionController;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\Admin\TracePatientController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Biologiste\PrescriptionPdfController;
use App\Http\Controllers\Laboratoire\AnalyseController;
use App\Http\Controllers\Laboratoire\AntibiotiqueController;
use App\Http\Controllers\Laboratoire\BacterieController;
use App\Http\Controllers\Laboratoire\BacterieFamilleController;
use App\Http\Controllers\Laboratoire\ExamenController;
use App\Http\Controllers\Laboratoire\PrelevementController;
use App\Http\Controllers\Laboratoire\TypeController;
use App\Http\Controllers\PrescriptionWorkspaceController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ResultatController;
use App\Http\Controllers\RoleWorklistController;
use App\Http\Controllers\Secretaire\PatientController;
use App\Http\Controllers\Secretaire\PrescriptionController;
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
    Route::get('/dashboard', [\App\Http\Controllers\Admin\DashboardController::class, 'index'])->name('dashboard');

    // Profil utilisateur
    Route::prefix('profile')->name('profile.')->group(function () {
        Route::get('/', [ProfileController::class, 'edit'])->name('edit');
        Route::patch('/', [ProfileController::class, 'update'])->name('update');
        Route::delete('/', [ProfileController::class, 'destroy'])->name('destroy');
    });

    // Archives
    Route::get('/archives', [\App\Http\Controllers\ArchivesController::class, 'index'])->name('archives');
    Route::post('/archives/{prescription}/unarchive', [\App\Http\Controllers\ArchivesController::class, 'unarchive'])->name('archives.unarchive');
    Route::delete('/archives/{prescription}', [\App\Http\Controllers\ArchivesController::class, 'permanentDelete'])->name('archives.destroy');
});

// ============================================
// ROUTES SPÉCIFIQUES AUX SECRÉTAIRES
// ============================================
Route::middleware(['auth', 'verified', 'role:secretaire,superadmin'])->prefix('secretaire')->name('secretaire.')->group(function () {
    Route::controller(PrescriptionController::class)->group(function () {
        Route::get('prescription/listes', 'index')->name('prescription.index');
        Route::get('nouvel-prescription', 'create')->name('prescription.create');
        Route::get('prescription/workspace/lookup/patients', 'searchPatients')->name('prescription.lookup.patients');
        Route::get('prescription/workspace/lookup/analyses', 'searchAnalyses')->name('prescription.lookup.analyses');
        Route::get('prescription/workspace/lookup/prelevements', 'searchPrelevements')->name('prescription.lookup.prelevements');
        Route::post('prescription/workspace/store', 'store')->name('prescription.store');
        Route::get('prescription/edit/{prescriptionId}', 'edit')->name('prescription.edit');
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
    });
    Route::controller(\App\Http\Controllers\Secretaire\PrescripteurController::class)->group(function () {
        Route::get('prescripteurs', 'index')->name('prescripteurs.index');
        Route::post('prescripteurs', 'store')->name('prescripteurs.store');
        Route::put('prescripteurs/{prescripteur}', 'update')->name('prescripteurs.update');
        Route::delete('prescripteurs/{prescripteur}', 'destroy')->name('prescripteurs.destroy');
        Route::post('prescripteurs/{prescripteur}/toggle-status', 'toggleStatus')->name('prescripteurs.toggle-status');
        Route::get('prescripteurs/export/csv', 'export')->name('prescripteurs.export');
        Route::get('prescripteurs/{prescripteur}/commissions/pdf', 'generateCommissionPDF')->name('prescripteurs.commissions.pdf');
        Route::get('prescripteurs/{prescripteur}/commissions/api', 'getCommissions')->name('prescripteurs.commissions.api');
    });
    Route::get('dashboard', [\App\Http\Controllers\Admin\DashboardController::class, 'index'])->name('dashboard');

    // Route pour afficher le journal de caisse
    Route::get('/journal-caisse', [\App\Http\Controllers\JournalCaisseController::class, 'index'])->name('journal-caisse');
    Route::get('/journal-caisse/export', [\App\Http\Controllers\JournalCaisseController::class, 'exportPdf'])->name('journal-caisse.export');
    Route::get('/journal-decaissement', [\App\Http\Controllers\JournalDecaissementController::class, 'index'])->name('journal-decaissement');
    Route::get('/journal-decaissement/export', [\App\Http\Controllers\JournalDecaissementController::class, 'exportPdf'])->name('journal-decaissement.export');
    // Route pour afficher la page de recettage / gestion des étiquettes
    Route::get('/secretaire/etiquettes', [\App\Http\Controllers\Secretaire\Tubes\GestionEtiquettesController::class, 'index'])->name('etiquettes');
    Route::get('/secretaire/etiquettes/export', [\App\Http\Controllers\Secretaire\Tubes\GestionEtiquettesController::class, 'exportPdf'])->name('etiquettes.export');
    Route::post('/secretaire/etiquettes/{id}/receptionner', [\App\Http\Controllers\Secretaire\Tubes\GestionEtiquettesController::class, 'marquerReceptionne'])->name('etiquettes.marquer.reception');
});

// ============================================ Résultats PDF prescriptions
// ROUTES SPÉCIFIQUES AUX SECRETAIRES, BIOLOGISTES
// ============================================
Route::middleware(['auth', 'verified', 'role:secretaire,biologiste,superadmin'])->prefix('laboratoire')->name('laboratoire.')->group(function () {
    Route::get('/prescription/{prescription}/pdf', [PrescriptionPdfController::class, 'show'])
        ->name('prescription.pdf');
});

// ============================================
// ROUTES SPÉCIFIQUES AUX TECHNICIENS
// ============================================
Route::middleware(['auth', 'verified', 'role:technicien'])->prefix('technicien')->name('technicien.')->group(function () {
    Route::get('traitement', [RoleWorklistController::class, 'technicien'])->name('index');
    Route::get('/technicien/prescription/{prescription}', [PrescriptionWorkspaceController::class, 'showTechnicien'])->name('prescription.show');
});

// ============================================
// ROUTES SPÉCIFIQUES AUX BIOLOGISTES
// ============================================
Route::middleware(['auth', 'verified', 'role:biologiste'])->prefix('biologiste')->name('biologiste.')->group(function () {
    // Routes principales
    Route::get('/analyse-valide', [RoleWorklistController::class, 'biologiste'])->name('analyse.index');
    Route::get('/prescription/{prescription}', [PrescriptionWorkspaceController::class, 'showBiologiste'])->name('prescription.show');
    Route::get('/valide/{prescription}/analyse', [PrescriptionWorkspaceController::class, 'showBiologisteValidation'])->name('valide.show');
});

// ============================================
// ROUTES SPÉCIFIQUES AUX ADMINS, BIOLOGISTES, TECHNICIENS
// ============================================
Route::middleware(['auth', 'verified', 'role:technicien,biologiste,superadmin'])->prefix('laboratoire')->name('laboratoire.')->group(function () {
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
Route::middleware(['auth', 'verified', 'role:superadmin'])->prefix('admin')->name('admin.')->group(function () {
    // Utilisateurs
    Route::controller(UserController::class)->group(function () {
        Route::get('utilisateurs', 'index')->name('users');
        Route::post('utilisateurs', 'store')->name('users.store');
        Route::put('utilisateurs/{user}', 'update')->name('users.update');
        Route::delete('utilisateurs/{user}', 'destroy')->name('users.destroy');
        Route::post('utilisateurs/{user}/logout', 'logoutSession')->name('users.logout');
    });

    // Permissions
    Route::controller(PermissionController::class)->group(function () {
        Route::get('permissions', 'index')->name('permissions');
        Route::put('permissions', 'update')->name('permissions.update');
    });

    // Paramètres
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
    Route::get('gestion-analyses', [RoleWorklistController::class, 'adminGestionAnalyses'])->name('gestion-analyses');
});

// ============================================
// ROUTE SIGNÉE - TÉLÉCHARGEMENT PDF RÉSULTATS
// ============================================
Route::get('/resultats/{prescription}/download', [ResultatController::class, 'download'])
    ->name('resultats.download')
    ->middleware('signed');

require __DIR__.'/auth.php';
