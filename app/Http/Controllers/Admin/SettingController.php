<?php

namespace App\Http\Controllers\Admin;

use App\Contracts\SmsDriverInterface;
use App\Http\Controllers\Controller;
use App\Models\PaymentMethod;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class SettingController extends Controller
{
    /**
     * Display the settings page.
     */
    public function index()
    {
        $setting = Setting::first();

        if (! $setting) {
            $setting = new Setting;
            // Default values
            $setting->format_unite_argent = 'Ar';
            $setting->remise_pourcentage = 0;
            $setting->activer_remise = false;
            $setting->commission_prescripteur = true;
            $setting->commission_prescripteur_pourcentage = 10;
            $setting->commission_prescripteur_quota = 250000;
            $setting->tarif_urgence_jour = 15000;
            $setting->tarif_urgence_nuit = 20000;
        }

        $paymentMethods = PaymentMethod::orderBy('display_order')->get();

        return Inertia::render('Admin/Settings', [
            'settings' => $setting,
            'paymentMethods' => $paymentMethods,
        ]);
    }

    /**
     * Update enterprise information.
     */
    public function updateEnterprise(Request $request)
    {
        $validated = $request->validate([
            'nom_entreprise' => 'required|string|max:255',
            'nif' => 'nullable|string|max:100',
            'statut' => 'nullable|string|max:100',
            'format_unite_argent' => 'required|string|max:10',
            'adresse' => 'nullable|string|max:500',
            'telephone' => 'nullable|string|max:100',
            'email' => 'nullable|email|max:255',
            'logo' => 'nullable|image|max:2048',
            'favicon' => 'nullable|image|max:1024',
        ]);

        $setting = Setting::first() ?? new Setting;

        $setting->nom_entreprise = $validated['nom_entreprise'];
        $setting->nif = $validated['nif'];
        $setting->statut = $validated['statut'];
        $setting->format_unite_argent = $validated['format_unite_argent'];
        $setting->adresse = $validated['adresse'];
        $setting->telephone = $validated['telephone'];
        $setting->email = $validated['email'];

        if ($request->hasFile('logo')) {
            if ($setting->logo) {
                Storage::disk('public')->delete($setting->logo);
            }
            $setting->logo = $request->file('logo')->store('logos', 'public');
        }

        if ($request->hasFile('favicon')) {
            if ($setting->favicon) {
                Storage::disk('public')->delete($setting->favicon);
            }
            $setting->favicon = $request->file('favicon')->store('favicons', 'public');
        }

        $setting->save();

        return redirect()->back()->with('success', 'Informations de l\'entreprise enregistrées avec succès.');
    }

    /**
     * Remove the logo or favicon.
     */
    public function removeImage(Request $request)
    {
        $validated = $request->validate([
            'type' => ['required', Rule::in(['logo', 'favicon'])],
        ]);

        $setting = Setting::first();
        if ($setting) {
            $type = $validated['type'];
            if ($setting->$type) {
                Storage::disk('public')->delete($setting->$type);
                $setting->$type = null;
                $setting->save();
            }
        }

        return redirect()->back()->with('success', ucfirst($validated['type']).' supprimé avec succès.');
    }

    /**
     * Update discount settings.
     */
    public function updateDiscount(Request $request)
    {
        $validated = $request->validate([
            'remise_pourcentage' => 'required|numeric|min:0|max:100',
            'activer_remise' => 'boolean',
        ]);

        $setting = Setting::first() ?? new Setting;
        $setting->fill($validated)->save();

        return redirect()->back()->with('success', 'Paramètres des remises enregistrés avec succès.');
    }

    /**
     * Update commission settings.
     */
    public function updateCommission(Request $request)
    {
        $validated = $request->validate([
            'commission_prescripteur' => 'boolean',
            'commission_prescripteur_pourcentage' => 'required|numeric|min:0|max:100',
            'commission_prescripteur_quota' => 'required|numeric|min:0',
        ]);

        $setting = Setting::first() ?? new Setting;

        $oldPourcentage = (float) $setting->commission_prescripteur_pourcentage;
        $newPourcentage = (float) $validated['commission_prescripteur_pourcentage'];

        $setting->fill($validated)->save();

        $message = 'Paramètres des commissions enregistrés avec succès.';

        if ($oldPourcentage !== $newPourcentage) {
            $message .= ' Le pourcentage a été modifié de '.$oldPourcentage.'% à '.$newPourcentage.'%.';
            // Here you could dispatch a job to recalculate commissions if requested by user logic
            // like: UpdateCommissionsJob::dispatch($oldPourcentage, $newPourcentage);
        }

        return redirect()->back()->with('success', $message);
    }

    /**
     * Update emergency tariffs.
     */
    public function updateEmergency(Request $request)
    {
        $validated = $request->validate([
            'tarif_urgence_jour' => 'required|numeric|min:0',
            'tarif_urgence_nuit' => 'required|numeric|min:0',
        ]);

        $setting = Setting::first() ?? new Setting;
        $setting->fill($validated)->save();

        return redirect()->back()->with('success', 'Tarifs d\'urgence enregistrés avec succès.');
    }

    /**
     * Add a payment method.
     */
    public function storePaymentMethod(Request $request)
    {
        $validated = $request->validate([
            'code' => 'required|string|max:50|unique:payment_methods,code',
            'label' => 'required|string|max:100',
            'is_active' => 'boolean',
            'display_order' => 'required|integer|min:1',
        ]);

        PaymentMethod::create($validated);

        return redirect()->back()->with('success', 'Mode de paiement ajouté avec succès.');
    }

    /**
     * Update a payment method.
     */
    public function updatePaymentMethod(Request $request, PaymentMethod $paymentMethod)
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:50', Rule::unique('payment_methods', 'code')->ignore($paymentMethod->id)],
            'label' => 'required|string|max:100',
            'is_active' => 'boolean',
            'display_order' => 'required|integer|min:1',
        ]);

        $paymentMethod->update($validated);

        return redirect()->back()->with('success', 'Mode de paiement modifié avec succès.');
    }

    /**
     * Delete a payment method.
     */
    public function destroyPaymentMethod(PaymentMethod $paymentMethod)
    {
        $paymentMethod->delete();

        return redirect()->back()->with('success', 'Mode de paiement supprimé avec succès.');
    }

    /**
     * Test de l'API SMS
     */
    public function testSms(Request $request, SmsDriverInterface $smsService)
    {
        $validated = $request->validate([
            'phone' => 'required|string|min:8|max:20',
        ]);

        try {
            $smsService->sendSms($validated['phone'], 'Ceci est un test de l\'API SMS depuis La Reference.');

            return redirect()->back()->with('success', 'SMS de test envoyé avec succès au '.$validated['phone']);
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Erreur lors de l\'envoi du SMS: '.$e->getMessage());
        }
    }

    /**
     * Test de l'API Email
     */
    public function testEmail(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|email|max:255',
        ]);

        try {
            Mail::raw('Ceci est un test de l\'API Email depuis La Reference.', function ($message) use ($validated) {
                $message->to($validated['email'])
                    ->subject('Test API Email - La Reference');
            });

            return redirect()->back()->with('success', 'Email de test envoyé avec succès à '.$validated['email']);
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Erreur lors de l\'envoi de l\'email: '.$e->getMessage());
        }
    }

    /**
     * Mettre à jour la configuration des APIs (dans le fichier .env)
     */
    public function updateApiConfig(Request $request)
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

            'orange_sms_client_id' => 'nullable|string',
            'orange_sms_client_secret' => 'nullable|string',
            'orange_sms_sender_name' => 'nullable|string|max:11',

            'mapi_sms_url' => 'nullable|url',
            'mapi_sms_username' => 'nullable|string',
            'mapi_sms_password' => 'nullable|string',
        ]);

        try {
            $envPath = base_path('.env');

            if (! file_exists($envPath)) {
                return redirect()->back()->with('error', 'Le fichier .env est introuvable.');
            }

            // Lire le fichier .env actuel
            $envContent = file_get_contents($envPath);

            // Préparer les nouvelles valeurs
            $replacements = [
                'MAIL_MAILER' => $validated['mail_mailer'],
                'MAIL_HOST' => $validated['mail_host'],
                'MAIL_PORT' => $validated['mail_port'],
                'MAIL_USERNAME' => $validated['mail_username'],
                'MAIL_PASSWORD' => $validated['mail_password'],
                'MAIL_ENCRYPTION' => $validated['mail_encryption'],
                'MAIL_FROM_ADDRESS' => $validated['mail_from_address'],
                'MAIL_FROM_NAME' => '"'.trim($validated['mail_from_name'], '"').'"', // Quote string with spaces

                'ORANGE_SMS_CLIENT_ID' => $validated['orange_sms_client_id'],
                'ORANGE_SMS_CLIENT_SECRET' => $validated['orange_sms_client_secret'],
                'ORANGE_SMS_SENDER_NAME' => '"'.trim($validated['orange_sms_sender_name'] ?? '', '"').'"',

                'MAPI_SMS_URL' => $validated['mapi_sms_url'],
                'MAPI_SMS_USERNAME' => $validated['mapi_sms_username'],
                'MAPI_SMS_PASSWORD' => $validated['mapi_sms_password'],
            ];

            // Remplacer ou ajouter chaque valeur
            foreach ($replacements as $key => $value) {
                // Ensure nulls are written as empty strings
                $value = $value ?? '';

                // Regex: Match the key at the start of a line, = and everything to the end of the line
                $pattern = "/^{$key}=(.*)$/m";

                if (preg_match($pattern, $envContent)) {
                    // Update existing key
                    $envContent = preg_replace($pattern, "{$key}={$value}", $envContent);
                } else {
                    // Append new key if it doesn't exist
                    $envContent .= "\n{$key}={$value}";
                }
            }

            // Écrire les modifications dans le fichier .env
            file_put_contents($envPath, $envContent);

            // Vider le cache de configuration pour appliquer les changements
            if (app()->configurationIsCached()) {
                Artisan::call('config:clear');
            }

            return redirect()->back()->with('success', 'Configuration des APIs enregistrée avec succès.');

        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Erreur lors de la sauvegarde: '.$e->getMessage());
        }
    }
}
