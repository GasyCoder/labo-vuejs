<?php

namespace App\Http\Requests\Secretaire;

use Illuminate\Foundation\Http\FormRequest;

class StorePrescriptionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'patient_id' => ['nullable', 'integer', 'exists:patients,id'],
            'patient.nom' => ['required_without:patient_id', 'string', 'max:255'],
            'patient.prenom' => ['nullable', 'string', 'max:255'],
            'patient.civilite' => ['required_without:patient_id', 'string', 'max:100'],
            'patient.telephone' => ['nullable', 'string', 'max:30'],
            'patient.email' => ['nullable', 'email', 'max:255'],
            'patient.adresse' => ['nullable', 'string', 'max:500'],
            'patient.date_naissance' => ['nullable', 'date', 'before:today', 'after:1900-01-01'],

            'prescripteur_id' => ['required', 'integer', 'exists:prescripteurs,id'],
            'patient_type' => ['required', 'string', 'in:EXTERNE,HOSPITALISE,URGENCE-JOUR,URGENCE-NUIT'],
            'age' => ['nullable', 'integer', 'min:0', 'max:150'],
            'unite_age' => ['nullable', 'string', 'in:Ans,Mois,Jours'],
            'poids' => ['nullable', 'numeric', 'min:0', 'max:500'],
            'renseignement_clinique' => ['nullable', 'string', 'max:2000'],

            'analyse_ids' => ['required', 'array', 'min:1'],
            'analyse_ids.*' => ['integer', 'distinct', 'exists:analyses,id'],

            'prelevements' => ['nullable', 'array'],
            'prelevements.*.id' => ['required_with:prelevements', 'integer', 'exists:prelevements,id'],
            'prelevements.*.quantite' => ['required_with:prelevements', 'integer', 'min:1', 'max:10'],

            'payment_method' => ['required', 'string', 'exists:payment_methods,code'],
            'remise' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'paiement_statut' => ['nullable', 'boolean'],
        ];
    }
}
