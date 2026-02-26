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
        $clients = Client::withCount('users')->get();

        return Inertia::render('Admin/Features/Index', [
            'clients' => $clients,
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

        return redirect()->route('admin.features.index')
            ->with('success', "Les fonctionnalités Premium du client {$client->name} ont été mises à jour.");
    }
}
