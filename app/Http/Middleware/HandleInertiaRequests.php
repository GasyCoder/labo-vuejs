<?php

namespace App\Http\Middleware;

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
                    'roles' => method_exists($request->user(), 'getRoleNames') ? $request->user()->getRoleNames() : [],
                    'isAdmin' => method_exists($request->user(), 'isAdmin') ? $request->user()->isAdmin() : false,
                ] : null,
            ],
            'enabledFeatures' => function () use ($request) {
                if ($request->user()) {
                    // Superadmins get all features
                    if ($request->user()->type === 'superadmin') {
                        $registeredFeatures = config('features.list', []);
                        $result = [];
                        foreach ($registeredFeatures as $key => $config) {
                            $result[$key] = true;
                        }

                        return $result;
                    }

                    return app(\App\Services\FeatureService::class)->getAllForCurrentUser();
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
                'logo' => \App\Models\Setting::getLogo(),
                'nom_entreprise' => \App\Models\Setting::getNomEntreprise(),
                'favicon' => \App\Models\Setting::first()?->favicon ? asset('storage/'.\App\Models\Setting::first()->favicon) : asset('favicon.ico'),
            ],
            'stats' => [
                'countArchive' => \App\Models\Prescription::where('status', \App\Models\Prescription::STATUS_ARCHIVE)->count(),
                'countAnalyses' => \App\Models\Prescription::where('status', '!=', \App\Models\Prescription::STATUS_ARCHIVE)->count(),
                'countTrace' => \App\Models\Patient::onlyTrashed()->count() + \App\Models\Prescription::onlyTrashed()->count(),
            ],
        ];
    }
}
