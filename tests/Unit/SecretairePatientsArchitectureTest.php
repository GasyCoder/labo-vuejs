<?php

it('keeps secretaire patients mapped to patient controller routes', function () {
    $projectRoot = dirname(__DIR__, 2);
    $routesFile = file_get_contents($projectRoot.'/routes/web.php');

    expect($routesFile)->toContain('Route::controller(PatientController::class)->group(function () {');
    expect($routesFile)->toContain("Route::get('patients', 'index')->name('patients.index');");
    expect($routesFile)->not->toContain("Route::get('patients', Patients::class)");
});

it('maps secretaire prescription routes to inertia controller actions', function () {
    $projectRoot = dirname(__DIR__, 2);
    $routesFile = file_get_contents($projectRoot.'/routes/web.php');

    expect($routesFile)->toContain('Route::controller(PrescriptionController::class)->group(function () {');
    expect($routesFile)->toContain("Route::get('prescription/listes', 'index')->name('prescription.index');");
    expect($routesFile)->toContain("Route::get('nouvel-prescription', 'create')->name('prescription.create');");
    expect($routesFile)->toContain("Route::get('prescription/edit/{prescriptionId}', 'edit')->name('prescription.edit');");
    expect($routesFile)->not->toContain("Route::get('prescription/listes', PrescriptionIndex::class)");
    expect($routesFile)->not->toContain("Route::get('nouvel-prescription', AddPrescription::class)");
    expect($routesFile)->not->toContain("Route::get('/prescription/edit/{prescriptionId}', EditPrescription::class)");
});

it('keeps inertia app blade as a single inertia root without blade sidebar shell', function () {
    $projectRoot = dirname(__DIR__, 2);
    $appBlade = file_get_contents($projectRoot.'/resources/views/app.blade.php');

    expect($appBlade)->toContain('@inertia');
    expect($appBlade)->not->toContain("include('layouts.partials.sidebar')");
    expect($appBlade)->not->toContain('<livewire:');
    expect($appBlade)->not->toContain('resources/js/apps.js');
    expect($appBlade)->not->toContain('resources/js/charts.js');
});

it('keeps a single inertia bootstrap mount', function () {
    $projectRoot = dirname(__DIR__, 2);
    $inertiaBootstrap = file_get_contents($projectRoot.'/resources/js/app.js');
    $legacyScripts = file_get_contents($projectRoot.'/resources/js/scripts.js');

    expect(substr_count($inertiaBootstrap, 'createInertiaApp('))->toBe(1);
    expect($legacyScripts)->not->toContain('createInertiaApp(');
    expect($legacyScripts)->not->toContain('.mount(');
});

it('disables livewire navigate spa mode', function () {
    $projectRoot = dirname(__DIR__, 2);
    $livewireConfig = file_get_contents($projectRoot.'/config/livewire.php');

    expect($livewireConfig)->toContain("'navigate' => false");
});

it('removes legacy livewire directories from the application layer', function () {
    $projectRoot = dirname(__DIR__, 2);

    expect(is_dir($projectRoot.'/app/Livewire'))->toBeFalse();
    expect(is_dir($projectRoot.'/resources/views/livewire'))->toBeFalse();
});

it('shows sidebar backdrop only when sidebar has sidebar-visible state', function () {
    $projectRoot = dirname(__DIR__, 2);
    $appLayout = file_get_contents($projectRoot.'/resources/js/Layouts/AppLayout.vue');
    $sidebar = file_get_contents($projectRoot.'/resources/js/Layouts/Partials/Sidebar.vue');
    $sidebarJs = file_get_contents($projectRoot.'/resources/js/component/Sidebar.js');

    expect($appLayout)->toContain('sidebar-backdrop sidebar-toggle');
    expect($appLayout)->toContain('lg:hidden');
    expect($appLayout)->toContain('pointer-events-none');
    expect($sidebar)->toContain('[&.sidebar-visible~.sidebar-backdrop]:opacity-100');
    expect($sidebar)->toContain('[&.sidebar-visible~.sidebar-backdrop]:visible');
    expect($sidebar)->toContain('[&.sidebar-visible~.sidebar-backdrop]:pointer-events-auto');
    expect($sidebarJs)->toContain("document.body.classList.toggle('overflow-hidden', isSidebarVisible);");
});

it('applies the same sidebar backdrop guard in blade livewire layout', function () {
    $projectRoot = dirname(__DIR__, 2);
    $bladeSidebar = file_get_contents($projectRoot.'/resources/views/layouts/partials/sidebar.blade.php');

    expect($bladeSidebar)->toContain('[&.sidebar-visible~.sidebar-backdrop]:opacity-100');
    expect($bladeSidebar)->toContain('[&.sidebar-visible~.sidebar-backdrop]:visible');
    expect($bladeSidebar)->toContain('[&.sidebar-visible~.sidebar-backdrop]:pointer-events-auto');
    expect($bladeSidebar)->toContain('sidebar-backdrop sidebar-toggle fixed inset-0');
    expect($bladeSidebar)->toContain('lg:hidden');
    expect($bladeSidebar)->toContain('pointer-events-none');
});

it('uses the prefixed secretaire route names in inertia pages to avoid ziggy render crashes', function () {
    $projectRoot = dirname(__DIR__, 2);
    $journalCaisse = file_get_contents($projectRoot.'/resources/js/Pages/JournalCaisse.vue');
    $journalDecaissement = file_get_contents($projectRoot.'/resources/js/Pages/JournalDecaissement.vue');
    $gestionEtiquettes = file_get_contents($projectRoot.'/resources/js/Pages/Secretaire/Tubes/GestionEtiquettes.vue');

    expect($journalCaisse)->toContain("route('secretaire.journal-caisse')");
    expect($journalCaisse)->toContain("route('secretaire.journal-caisse.export'");
    expect($journalCaisse)->not->toContain("route('journal-caisse'");
    expect($journalDecaissement)->toContain("route('secretaire.journal-decaissement')");
    expect($journalDecaissement)->toContain("route('secretaire.journal-decaissement.export'");
    expect($journalDecaissement)->not->toContain("route('journal-decaissement'");
    expect($gestionEtiquettes)->toContain("route('secretaire.etiquettes')");
    expect($gestionEtiquettes)->toContain("route('secretaire.etiquettes.export')");
    expect($gestionEtiquettes)->toContain("route('secretaire.etiquettes.marquer.reception'");
});

it('uses inertia navigation for secretaire and role workspaces', function () {
    $projectRoot = dirname(__DIR__, 2);
    $sidebar = file_get_contents($projectRoot.'/resources/js/Layouts/Partials/Sidebar.vue');
    $dashboard = file_get_contents($projectRoot.'/resources/js/Pages/Dashboard.vue');

    expect($sidebar)->toContain('<Link :href="route(\'secretaire.prescription.index\')"');
    expect($dashboard)->toContain('<Link :href="route(\'secretaire.prescription.index\')"');
    expect($sidebar)->toContain('<Link :href="route(\'admin.gestion-analyses\')"');
    expect($sidebar)->toContain('<Link :href="route(\'technicien.index\')"');
    expect($sidebar)->toContain('<Link :href="route(\'biologiste.analyse.index\')"');
    expect($dashboard)->toContain('<Link :href="route(\'technicien.index\')"');
    expect($dashboard)->toContain('<Link :href="route(\'biologiste.analyse.index\')"');
});

it('removes livewire page routes in favor of controllers', function () {
    $projectRoot = dirname(__DIR__, 2);
    $routesFile = file_get_contents($projectRoot.'/routes/web.php');

    expect($routesFile)->not->toContain('IndexTechnicien::class');
    expect($routesFile)->not->toContain('AnalyseValide::class');
    expect($routesFile)->not->toContain('BiologisteAnalysisForm::class');
    expect($routesFile)->not->toContain('ShowPrescription::class');
    expect($routesFile)->not->toContain('GestionAnalyses::class');
    expect($routesFile)->toContain('RoleWorklistController::class');
    expect($routesFile)->toContain('PrescriptionWorkspaceController::class');
});

it('falls back to browser navigation on invalid inertia responses', function () {
    $projectRoot = dirname(__DIR__, 2);
    $inertiaBootstrap = file_get_contents($projectRoot.'/resources/js/app.js');

    expect($inertiaBootstrap)->toContain("router.on('invalid', fallbackToBrowserVisit);");
    expect($inertiaBootstrap)->toContain('event.preventDefault();');
    expect($inertiaBootstrap)->toContain('window.location.href = targetUrl;');
});
