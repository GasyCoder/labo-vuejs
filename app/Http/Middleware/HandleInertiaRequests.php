<?php

namespace App\Http\Middleware;

use App\Models\Patient;
use App\Models\Prescription;
use App\Models\Setting;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'auth' => [
                'user' => $request->user() ? [
                    'id' => $request->user()->id,
                    'name' => $request->user()->name,
                    'email' => $request->user()->email,
                    'type' => $request->user()->type,
                    'permissions' => method_exists($request->user(), 'getAllPermissions') ? $request->user()->getAllPermissions()->pluck('name') : [],
                    'roles' => method_exists($request->user(), 'getRoleNames') ? $request->user()->getRoleNames()->toArray() : [],
                    'isAdmin' => method_exists($request->user(), 'isAdmin') ? $request->user()->isAdmin() : false,
                ] : null,
            ],
            'enabledFeatures' => function () use ($request) {
                if ($request->user()) {
                    $featureService = app(\App\Services\FeatureService::class);

                    // If superadmin but has a client, we want to show the REAL client status in the UI
                    // but they still bypass the checks in logic because of isEnabledForCurrentUser()
                    if ($request->user()->type === 'superadmin' && $request->user()->client_id) {
                        return $featureService->getAllForClient($request->user()->client_id);
                    }

                    // Fallback for superadmins without client_id (system level)
                    if ($request->user()->type === 'superadmin') {
                        $registeredFeatures = config('features.list', []);
                        $result = [];
                        foreach ($registeredFeatures as $key => $config) {
                            $result[$key] = true;
                        }

                        return $result;
                    }

                    return $featureService->getAllForCurrentUser();
                }

                return [];
            },
            'flash' => [
                'success' => $request->session()->get('success'),
                'error' => $request->session()->get('error'),
                'prescription_action' => $request->session()->get('prescription_action'),
                '_ts' => now()->timestamp,
            ],
            'skeleton' => $request->session()->get('skeleton'),
            'settings' => [
                'logo' => Setting::getLogo(),
                'nom_entreprise' => Setting::getNomEntreprise(),
                'site_name' => Setting::getSiteName(),
                'favicon' => Setting::first()?->favicon ? asset('storage/'.Setting::first()->favicon) : asset('favicon.ico'),
            ],
            'stats' => $request->user() ? [
                'countArchive' => Prescription::where('status', Prescription::STATUS_ARCHIVE)->count(),
                'countAnalyses' => Prescription::where('status', '!=', Prescription::STATUS_ARCHIVE)->count(),
                'countTrace' => Patient::onlyTrashed()->count() + Prescription::onlyTrashed()->count(),
            ] : null,
        ];
    }
}
