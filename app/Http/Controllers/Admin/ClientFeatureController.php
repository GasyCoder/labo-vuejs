<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Services\FeatureService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ClientFeatureController extends Controller
{
    protected $featureService;

    public function __construct(FeatureService $featureService)
    {
        $this->featureService = $featureService;
    }

    /**
     * Display a listing of clients.
     */
    public function index()
    {
        $clients = Client::withCount('users')->with('features')->get();

        $clients->transform(function ($client) {
            $client->enabled_features = $client->features
                ->where('is_enabled', true)
                ->pluck('feature_key')
                ->toArray();

            return $client;
        });

        return Inertia::render('Admin/Features/Index', [
            'clients' => $clients,
            'availableFeatures' => config('features.list', []),
        ]);
    }

    /**
     * Show the form for editing features for a specific client.
     */
    public function edit(Client $client)
    {
        $features = config('features.list', []);
        $enabledFeatures = $this->featureService->getAllForClient($client->id);

        // Map the current state
        $featureList = [];
        foreach ($features as $key => $config) {
            $featureList[] = [
                'key' => $key,
                'name' => $config['name'],
                'description' => $config['description'],
                'is_enabled' => $enabledFeatures[$key] ?? false,
            ];
        }

        return Inertia::render('Admin/Features/Edit', [
            'client' => $client,
            'features' => $featureList,
        ]);
    }

    /**
     * Assign the Pack Premium to a client.
     */
    public function assignPackPremium(Client $client)
    {
        $plan = config('plans.premium');

        $client->update([
            'plan_name' => $plan['name'],
            'subscription_price' => $plan['price'],
            'sms_quota' => $plan['sms_quota'],
            'email_quota' => $plan['email_quota'],
            'next_renewal_at' => now()->addMonth(),
        ]);

        // Enable all premium features
        foreach ($plan['features'] as $featureKey) {
            $this->featureService->setEnabled($client->id, $featureKey, true);
        }

        return back()->with('success', 'Pack Premium assigné avec succès.');
    }

    /**
     * Display the current client's subscription information (Tenant view).
     */
    public function subscription()
    {
        $user = auth()->user();
        $client = $user->client ?? Client::first();

        if (!$client) {
            abort(404, 'Client non trouvé.');
        }

        $features = config('features.list', []);

        // We use getAllForClient instead of getAllForCurrentUser to see REAL status, 
        // because superadmins bypass the check in the service but we want to see the real DB status here.
        $enabledFeatures = $this->featureService->getAllForClient($client->id);

        $featureList = [];
        foreach ($features as $key => $config) {
            $featureList[] = [
                'key' => $key,
                'name' => $config['name'],
                'description' => $config['description'],
                'is_enabled' => $enabledFeatures[$key] ?? false,
            ];
        }

        return Inertia::render('Admin/Subscription', [
            'client' => $client,
            'features' => $featureList,
            'plan' => config('plans.premium'),
        ]);
    }

    /**
     * Update the features for a specific client.
     */
    public function update(Request $request, Client $client)
    {
        $validated = $request->validate([
            'features' => 'required|array',
            'features.*.key' => 'required|string',
            'features.*.is_enabled' => 'required|boolean',
        ]);

        foreach ($validated['features'] as $featureData) {
            $this->featureService->setEnabled($client->id, $featureData['key'], $featureData['is_enabled']);
        }

        return back()->with('success', 'Configuration mise à jour avec succès.');
    }
}
