<?php

use App\Models\Prescripteur;
use App\Models\User;
use Spatie\Permission\Models\Permission;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    // Nettoyer la base de données ou configurer les permissions
    Permission::findOrCreate('prescripteurs.gerer', 'web');

    $this->user = User::factory()->create([
        'type' => User::TYPE_SUPERADMIN,
    ]);

    // On s'assure qu'il est reconnu comme superadmin par Spatie aussi
    $role = \Spatie\Permission\Models\Role::findOrCreate(User::TYPE_SUPERADMIN, 'web');
    $this->user->assignRole($role);
});

it('empeche la creation d\'un prescripteur en double (nom et prenom identiques)', function () {
    // 1. Créer un premier prescripteur
    Prescripteur::create([
        'nom' => 'RAKOTO',
        'prenom' => 'Jean',
        'status' => 'Medecin',
        'is_active' => true,
        'is_commissionned' => true,
    ]);

    // 2. Tenter de créer le même
    $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class);
    actingAs($this->user)
        ->post(route('secretaire.prescripteurs.store'), [
            'nom' => 'RAKOTO',
            'prenom' => 'Jean',
            'status' => 'Medecin',
            'is_active' => true,
            'is_commissionned' => true,
        ])
        ->assertSessionHasErrors(['nom' => 'Un prescripteur avec ce nom et ce prénom existe déjà.']);

    // Vérifier qu'il n'y en a qu'un seul dans la base
    expect(Prescripteur::where('nom', 'RAKOTO')->where('prenom', 'Jean')->count())->toBe(1);
});

it('autorise la creation de prescripteurs avec le meme nom mais prenoms differents', function () {
    Prescripteur::create([
        'nom' => 'RAKOTO',
        'prenom' => 'Jean',
        'status' => 'Medecin',
        'is_active' => true,
        'is_commissionned' => true,
    ]);

    $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class);
    actingAs($this->user)
        ->post(route('secretaire.prescripteurs.store'), [
            'nom' => 'RAKOTO',
            'prenom' => 'Paul',
            'status' => 'Medecin',
            'is_active' => true,
            'is_commissionned' => true,
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirect();

    expect(Prescripteur::where('nom', 'RAKOTO')->count())->toBe(2);
});

it('empeche la mise a jour vers un nom et prenom deja existant', function () {
    Prescripteur::create([
        'nom' => 'RAKOTO',
        'prenom' => 'Jean',
        'status' => 'Medecin',
    ]);

    $prescripteur2 = Prescripteur::create([
        'nom' => 'ANDRIA',
        'prenom' => 'Paul',
        'status' => 'Medecin',
    ]);

    $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class);
    actingAs($this->user)
        ->put(route('secretaire.prescripteurs.update', $prescripteur2->id), [
            'nom' => 'RAKOTO',
            'prenom' => 'Jean',
            'status' => 'Medecin',
        ])
        ->assertSessionHasErrors(['nom' => 'Un prescripteur avec ce nom et ce prénom existe déjà.']);
});

it('autorise la mise a jour de son propre record sans changer nom/prenom', function () {
    $prescripteur = Prescripteur::create([
        'nom' => 'RAKOTO',
        'prenom' => 'Jean',
        'status' => 'Medecin',
    ]);

    $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class);
    actingAs($this->user)
        ->put(route('secretaire.prescripteurs.update', $prescripteur->id), [
            'nom' => 'RAKOTO',
            'prenom' => 'Jean',
            'status' => 'Medecin',
            'telephone' => '0341122233',
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirect();

    expect($prescripteur->fresh()->telephone)->toBe('0341122233');
});

it('empeche la creation d\'un prescripteur en double avec prenom null', function () {
    Prescripteur::create([
        'nom' => 'RAKOTO',
        'prenom' => null,
        'status' => 'Medecin',
    ]);

    $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class);
    actingAs($this->user)
        ->post(route('secretaire.prescripteurs.store'), [
            'nom' => 'RAKOTO',
            'prenom' => null,
            'status' => 'Medecin',
        ])
        ->assertSessionHasErrors(['nom' => 'Un prescripteur avec ce nom et ce prénom existe déjà.']);
});
