<?php

namespace Tests\Feature;

use App\Models\Patient;
use App\Models\User;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Tests\TestCase;

class PatientUpdateIssueTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware([VerifyCsrfToken::class]);
        $this->withoutExceptionHandling();
    }

    public function test_can_update_patient()
    {
        $user = User::where('type', 'admin')->first();
        if (! $user) {
            $user = User::factory()->create(['type' => 'admin']);
            $user->assignRole('admin');
        }

        $patient = Patient::find(2);

        $response = $this->actingAs($user)->put(route('secretaire.patient.update', $patient->id), [
            'nom' => 'Nom update from code',
            'civilite' => 'Monsieur',
            'prenom' => null,
            'telephone' => '0331023456',
            'email' => 'test@test.com',
            'adresse' => 'Test adresse',
            'date_naissance' => null,
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect(route('secretaire.patient.detail', $patient->id));

        $patient->refresh();
        $this->assertEquals('Nom update from code', $patient->nom);
        echo "Update successful!\n";
    }
}
