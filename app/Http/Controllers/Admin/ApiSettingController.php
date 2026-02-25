<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\SendTestEmailJob;
use App\Jobs\SendTestSmsJob;
use App\Models\Setting;
use App\Models\SmsProvider;
use App\Services\Sms\SmsManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class ApiSettingController extends Controller
{
    public function __construct(protected SmsManager $smsManager) {}

    /**
     * Display the API settings page.
     */
    public function index(): \Inertia\Response
    {
        $setting = Setting::first();

        return Inertia::render('Admin/ApiSettings', [
            'emailConfig' => [
                'mail_mailer' => env('MAIL_MAILER', 'smtp'),
                'mail_host' => env('MAIL_HOST', 'mailpit'),
                'mail_port' => env('MAIL_PORT', 1025),
                'mail_username' => env('MAIL_USERNAME', ''),
                'mail_password' => env('MAIL_PASSWORD', ''),
                'mail_encryption' => env('MAIL_ENCRYPTION', 'tls'),
                'mail_from_address' => env('MAIL_FROM_ADDRESS', 'hello@example.com'),
                'mail_from_name' => env('MAIL_FROM_NAME', 'La Reference'),
            ],
            'smsDrivers' => $this->smsManager->getAllDriverConfigs(),
            'availableDrivers' => $this->smsManager->getAvailableDrivers(),
            'currentSmsDriver' => $setting?->sms_driver ?? config('sms.default', 'mapi'),
            'smsProviders' => SmsProvider::all(),
        ]);
    }

    /**
     * Mettre a jour la configuration Email (dans le fichier .env).
     */
    public function updateEmailConfig(Request $request): \Illuminate\Http\RedirectResponse
    {
        $validated = $request->validate([
            'mail_mailer' => 'required|string',
            'mail_host' => 'required|string',
            'mail_port' => 'required|numeric',
            'mail_username' => 'nullable|string',
            'mail_password' => 'nullable|string',
            'mail_encryption' => 'nullable|string',
            'mail_from_address' => 'required|email',
            'mail_from_name' => 'required|string',
        ]);

        try {
            $replacements = [
                'MAIL_MAILER' => $validated['mail_mailer'],
                'MAIL_HOST' => $validated['mail_host'],
                'MAIL_PORT' => $validated['mail_port'],
                'MAIL_USERNAME' => $validated['mail_username'],
                'MAIL_PASSWORD' => $validated['mail_password'],
                'MAIL_ENCRYPTION' => $validated['mail_encryption'],
                'MAIL_FROM_ADDRESS' => $validated['mail_from_address'],
                'MAIL_FROM_NAME' => '"'.trim($validated['mail_from_name'], '"').'"',
            ];

            $this->updateEnvFile($replacements);

            return redirect()->back()->with('success', 'Configuration Email enregistree avec succes.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Erreur lors de la sauvegarde: '.$e->getMessage());
        }
    }

    /**
     * Ajouter un fournisseur SMS.
     */
    public function storeSmsProvider(Request $request): \Illuminate\Http\RedirectResponse
    {
        $availableDrivers = array_keys(config('sms.drivers', []));

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'driver' => ['required', 'string', 'in:'.implode(',', $availableDrivers)],
            'credentials' => 'required|array',
        ]);

        try {
            $isActive = SmsProvider::count() === 0;

            SmsProvider::create([
                'name' => $validated['name'],
                'driver' => $validated['driver'],
                'credentials' => $validated['credentials'],
                'is_active' => $isActive,
            ]);

            return redirect()->back()->with('success', 'Fournisseur SMS créé avec succès.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Erreur lors de la création: '.$e->getMessage());
        }
    }

    /**
     * Mettre à jour un fournisseur SMS.
     */
    public function updateSmsProvider(Request $request, SmsProvider $provider): \Illuminate\Http\RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'credentials' => 'required|array',
        ]);

        try {
            $provider->update([
                'name' => $validated['name'],
                'credentials' => $validated['credentials'],
            ]);

            return redirect()->back()->with('success', 'Fournisseur SMS mis à jour avec succès.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Erreur lors de la mise à jour: '.$e->getMessage());
        }
    }

    /**
     * Supprimer un fournisseur SMS.
     */
    public function destroySmsProvider(SmsProvider $provider): \Illuminate\Http\RedirectResponse
    {
        try {
            $wasActive = $provider->is_active;
            $provider->delete();

            if ($wasActive) {
                $first = SmsProvider::first();
                if ($first) {
                    $first->update(['is_active' => true]);
                }
            }

            return redirect()->back()->with('success', 'Fournisseur SMS supprimé avec succès.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Erreur lors de la suppression: '.$e->getMessage());
        }
    }

    /**
     * Rendre un fournisseur SMS actif.
     */
    public function activateSmsProvider(SmsProvider $provider): \Illuminate\Http\RedirectResponse
    {
        try {
            SmsProvider::query()->update(['is_active' => false]);
            $provider->update(['is_active' => true]);

            return redirect()->back()->with('success', 'Fournisseur SMS activé avec succès.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Erreur lors de l\'activation: '.$e->getMessage());
        }
    }

    /**
     * Test de l'API SMS.
     */
    public function testSms(Request $request): RedirectResponse
    {
        $request->validate([
            'phone' => 'required|string',
            'message' => 'nullable|string|max:500',
        ]);

        try {
            $messageContent = $request->input('message') ?: "Ceci est un test de l'API SMS depuis ".config('app.name').'.';

            SendTestSmsJob::dispatch($request->phone, $messageContent);

            return back()->with('success', 'SMS de test mis en file d\'attente. Il sera envoyé prochainement.');
        } catch (\Exception $e) {
            Log::error('Erreur test SMS: '.$e->getMessage());

            return back()->with('error', 'Erreur lors de la mise en file d\'attente du SMS: '.$e->getMessage());
        }
    }

    /**
     * Test de l'API Email.
     */
    public function testEmail(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'email' => 'required|email|max:255',
            'message' => 'nullable|string|max:500',
        ]);

        try {
            $messageContent = $request->input('message') ?: "Ceci est un test de l'API Email depuis La Reference.";

            SendTestEmailJob::dispatch($validated['email'], $messageContent);

            return back()->with('success', 'Email de test mis en file d\'attente. Il sera envoyé prochainement à '.$validated['email']);
        } catch (\Exception $e) {
            Log::error('Erreur test email: '.$e->getMessage());

            return back()->with('error', 'Erreur lors de la mise en file d\'attente de l\'email: '.$e->getMessage());
        }
    }

    /**
     * Mettre a jour le fichier .env avec les paires cle/valeur fournies.
     *
     * @param  array<string, string|null>  $replacements
     */
    protected function updateEnvFile(array $replacements): void
    {
        $envPath = base_path('.env');

        if (! file_exists($envPath)) {
            throw new \RuntimeException('Le fichier .env est introuvable.');
        }

        $envContent = file_get_contents($envPath);

        foreach ($replacements as $key => $value) {
            $value = $value ?? '';
            $pattern = "/^{$key}=(.*)$/m";

            if (preg_match($pattern, $envContent)) {
                $envContent = preg_replace($pattern, "{$key}={$value}", $envContent);
            } else {
                $envContent .= "\n{$key}={$value}";
            }
        }

        file_put_contents($envPath, $envContent);

        if (app()->configurationIsCached()) {
            Artisan::call('config:clear');
        }
    }
}
