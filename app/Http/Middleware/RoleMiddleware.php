<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RoleMiddleware
{
    public function handle(Request $request, Closure $next, string ...$roles)
    {
        if (! Auth::check()) {
            return redirect('/login');
        }

        $user = Auth::user();

        // Vérifier si l'utilisateur a un des rôles autorisés (via Spatie)
        if ($user->hasRole($roles)) {
            return $next($request);
        }

        // Si l'utilisateur n'a pas le bon rôle, on le redirige selon son rôle
        return $this->redirectToUserRole($user);
    }

    private function redirectToUserRole($user)
    {
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
}
