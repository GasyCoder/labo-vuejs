<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;

class RoleWorklistController extends Controller
{
    public function technicien(): RedirectResponse
    {
        return redirect()->route('laboratoire.analyses.listes');
    }

    public function biologiste(): RedirectResponse
    {
        return redirect()->route('laboratoire.analyses.listes');
    }

    public function adminGestionAnalyses(): RedirectResponse
    {
        return redirect()->route('laboratoire.analyses.listes');
    }
}
