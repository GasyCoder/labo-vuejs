<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;

class RoleWorklistController extends Controller
{
    public function technicien(): RedirectResponse
    {
        return redirect()->route('dashboard');
    }

    public function biologiste(): mixed
    {
        return app(\App\Http\Controllers\Biologiste\BiologisteController::class)->index(request());
    }

    public function adminGestionAnalyses(): RedirectResponse
    {
        return redirect()->route('dashboard');
    }
}
