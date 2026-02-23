<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RoleRedirectMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        if (Auth::check() && $request->route()->getName() === 'dashboard') {
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
            // superadmin reste sur le dashboard
        }

        return $next($request);
    }
}
