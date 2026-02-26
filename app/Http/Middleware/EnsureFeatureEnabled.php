<?php

namespace App\Http\Middleware;

use App\Services\FeatureService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureFeatureEnabled
{
    protected $featureService;

    public function __construct(FeatureService $featureService)
    {
        $this->featureService = $featureService;
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string $featureKey): Response
    {
        // 1. We require a logged in user to evaluate features securely
        $user = $request->user();

        if (! $user) {
            return redirect()->route('login');
        }

        // 2. Superadmins *could* bypass this, but for testing UI/UX we might want them to experience
        //    the same restrictions if they are attached to a specific client.
        //    For now, we enforce strictly based on the client_id attached to the user.
        if (! $this->featureService->isEnabledForCurrentUser($featureKey)) {
            
            // Handle XHR / JSON requests
            if ($request->wantsJson()) {
                return response()->json(['message' => 'Fonctionnalité Premium requise.'], 403);
            }

            // Handle Inertia or standard redirects
            // We flash a message and redirect to dashboard.
            return redirect()
                ->route('dashboard')
                ->with('error', 'Cette fonctionnalité est désactivée. Veuillez contacter un administrateur pour l\'activer.');
        }

        return $next($request);
    }
}
