<?php

use App\Models\Prescription;
use App\Models\User;

beforeEach(function () {
    $this->technicien = User::factory()->create(['type' => 'technicien']);
    $this->technicien->assignRole('technicien');

    $this->secretaire = User::factory()->create(['type' => 'secretaire']);
    $this->secretaire->assignRole('secretaire');
});

test('technicien can access worklist index', function () {
    $this->actingAs($this->technicien)
        ->get(route('technicien.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Technicien/Index')
            ->has('prescriptions')
            ->has('stats')
            ->has('filters')
        );
});

test('non-technicien is redirected from worklist', function () {
    $this->actingAs($this->secretaire)
        ->get(route('technicien.index'))
        ->assertRedirect();
});

test('technicien can start analysis on EN_ATTENTE prescription', function () {
    $prescription = Prescription::factory()->create(['status' => 'EN_ATTENTE']);

    $this->actingAs($this->technicien)
        ->post(route('technicien.prescription.start', $prescription->id))
        ->assertRedirect();

    $prescription->refresh();
    expect($prescription->status)->toBe('EN_COURS');
    expect($prescription->technicien_id)->toBe($this->technicien->id);
});

test('technicien cannot start analysis on non EN_ATTENTE prescription', function () {
    $prescription = Prescription::factory()->create(['status' => 'TERMINE']);

    $this->actingAs($this->technicien)
        ->post(route('technicien.prescription.start', $prescription->id))
        ->assertRedirect();

    $prescription->refresh();
    expect($prescription->status)->toBe('TERMINE');
});

test('technicien can redo A_REFAIRE prescription', function () {
    $prescription = Prescription::factory()->create([
        'status' => 'A_REFAIRE',
        'commentaire_biologiste' => 'Résultats incorrects',
    ]);

    $this->actingAs($this->technicien)
        ->post(route('technicien.prescription.redo', $prescription->id))
        ->assertRedirect();

    $prescription->refresh();
    expect($prescription->status)->toBe('EN_COURS');
    expect($prescription->commentaire_biologiste)->toBeNull();
    expect($prescription->technicien_id)->toBe($this->technicien->id);
});
