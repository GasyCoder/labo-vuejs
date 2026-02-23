<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $search = $request->input('search');
        $perPage = $request->input('perPage', 10);

        $users = User::query()
            ->when($search, function ($query, $search) {
                // Adjust this scope if search method doesn't exist on User model
                // Assuming it searches name, username or email
                if (method_exists(User::class, 'scopeSearch')) {
                    $query->search($search);
                } else {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('username', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                }
            })
            ->paginate($perPage)
            ->withQueryString();

        $sessions = $this->getSessionsData();

        // Ajout du statut de connexion pour chaque utilisateur
        $users->getCollection()->transform(function ($user) use ($sessions) {
            $user->session_status = $this->getUserStatus($user->id, $sessions);
            // On charge aussi les rôles via Spatie si nécessaire
            $user->roles_array = $user->getRoleNames();

            return $user;
        });

        return Inertia::render('Admin/Users/Index', [
            'users' => $users,
            'filters' => ['search' => $search],
            'stats' => User::getCountByType(),
            'types' => User::TYPES,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|min:3',
            'username' => 'required|string|min:3|unique:users,username',
            'type' => ['required', Rule::in(['superadmin', 'secretaire', 'technicien', 'biologiste'])],
            'password' => 'required|confirmed|min:6',
        ]);

        $newUser = User::create([
            'name' => $validated['name'],
            'username' => $validated['username'],
            'type' => $validated['type'],
            'password' => Hash::make($validated['password']),
        ]);

        // Assigner le rôle Spatie
        $newUser->assignRole($validated['type']);

        return redirect()->back()->with('success', 'Utilisateur créé avec succès!');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, User $user)
    {
        $rules = [
            'name' => 'required|min:3',
            'username' => ['required', 'string', 'min:3', Rule::unique('users', 'username')->ignore($user->id)],
            'type' => ['required', Rule::in(['superadmin', 'secretaire', 'technicien', 'biologiste'])],
        ];

        if ($request->filled('password')) {
            $rules['password'] = 'min:6|confirmed';
        }

        $validated = $request->validate($rules);

        $userData = [
            'name' => $validated['name'],
            'username' => $validated['username'],
            'type' => $validated['type'],
        ];

        if ($request->filled('password')) {
            $userData['password'] = Hash::make($validated['password']);
        }

        $user->update($userData);

        // Synchroniser le rôle Spatie
        $user->syncRoles([$validated['type']]);

        return redirect()->back()->with('success', 'Utilisateur mis à jour avec succès!');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(User $user)
    {
        // Vérifier si c'est le dernier superadmin
        if ($user->hasRole('superadmin') && User::role('superadmin')->count() <= 1) {
            return redirect()->back()->with('error', 'Impossible de supprimer le dernier super administrateur!');
        }

        DB::table('sessions')->where('user_id', $user->id)->delete();

        $user->delete();

        return redirect()->back()->with('success', 'Utilisateur supprimé avec succès!');
    }

    /**
     * Déconnecter un utilisateur (supprimer sa session).
     */
    public function logout(User $user)
    {
        DB::table('sessions')->where('user_id', $user->id)->delete();

        return redirect()->back()->with('success', 'Utilisateur déconnecté avec succès!');
    }

    // --- Helper methods translated from Livewire ---

    private function getSessionsData()
    {
        try {
            $sessionsExist = DB::table('sessions')->exists();

            if (! $sessionsExist) {
                return collect();
            }

            $sessions = DB::table('sessions')
                ->selectRaw('user_id, MAX(last_activity) as last_activity, COUNT(*) as session_count')
                ->whereNotNull('user_id')
                ->groupBy('user_id')
                ->get();

            return $sessions->keyBy('user_id');

        } catch (\Exception $e) {
            return collect();
        }
    }

    private function getUserStatus($userId, $sessions)
    {
        $session = $sessions->get($userId);

        if (! $session) {
            return [
                'status' => 'never_connected',
                'text' => 'Jamais connecté',
                'color' => 'gray-300',
                'text_color' => 'text-gray-400 dark:text-gray-500',
                'last_activity' => null,
                'show_date' => false,
            ];
        }

        $lastActivity = $session->last_activity;

        if (! $this->isValidTimestamp($lastActivity)) {
            return [
                'status' => 'invalid_session',
                'text' => 'Session invalide',
                'color' => 'red-300',
                'text_color' => 'text-red-400',
                'last_activity' => null,
                'show_date' => false,
            ];
        }

        $currentTimestamp = now()->timestamp;
        $diffInSeconds = $currentTimestamp - $lastActivity;

        if ($diffInSeconds < 300) {
            return [
                'status' => 'online',
                'text' => 'En ligne',
                'color' => 'green-500',
                'text_color' => 'text-green-600 dark:text-green-400 font-medium',
                'last_activity' => $lastActivity,
                'show_date' => false,
            ];
        }

        $lastActivityCarbon = Carbon::createFromTimestamp($lastActivity);

        return [
            'status' => 'offline',
            'text' => 'Déconnecté il y a '.$lastActivityCarbon->diffForHumans(['locale' => 'fr']),
            'color' => 'gray-400',
            'text_color' => 'text-gray-600 dark:text-gray-400',
            'last_activity' => $lastActivity,
            'last_activity_formatted' => $lastActivityCarbon->format('d/m/Y à H:i'),
            'last_activity_full' => $lastActivityCarbon->format('d/m/Y à H:i:s'),
            'show_date' => true,
        ];
    }

    private function isValidTimestamp($timestamp)
    {
        if (! is_numeric($timestamp)) {
            return false;
        }

        $currentYear = date('Y');
        $timestampYear = date('Y', $timestamp);

        return $timestampYear >= 2020 && $timestampYear <= ($currentYear + 1);
    }
}
