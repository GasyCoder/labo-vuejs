<?php

namespace App\Http\Requests\Technicien;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class SaveResultatRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $user = Auth::user();
        if (! $user) {
            return false;
        }

        // Si c'est un biologiste, on vérifie la permission
        if ($user->hasRole('biologiste')) {
            return $user->hasPermissionTo('analyses.effectuer');
        }

        // Sinon rôles standards
        return $user->hasRole('technicien') ||
               $user->hasRole('superadmin') ||
               $user->hasRole('admin');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'prescription_id' => 'required|integer|exists:prescriptions,id',
            'analyse_id' => 'required|integer|exists:analyses,id',
            'valeur' => 'nullable',
            'resultats' => 'nullable',
            'interpretation' => 'nullable|string|in:NORMAL,PATHOLOGIQUE',
            'confirmed' => 'nullable|boolean',
        ];
    }

    /**
     * Custom messages
     */
    public function messages(): array
    {
        return [
            'prescription_id.exists' => 'La prescription spécifiée n\'existe pas.',
            'analyse_id.exists' => 'L\'analyse spécifiée n\'existe pas.',
            'interpretation.in' => 'L\'interprétation doit être NORMAL ou PATHOLOGIQUE.',
        ];
    }
}
