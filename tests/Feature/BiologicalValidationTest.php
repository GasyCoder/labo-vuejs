<?php

use App\Models\Analyse;
use App\Models\AnalyseRange;
use App\Models\Patient;

uses(Tests\TestCase::class);

test('la validation biologique respecte l inclusivité des bornes (cas 4.0)', function () {
    // Création d'une analyse de test
    $analyse = Analyse::create([
        'designation' => 'TEST GLOBULES ROUGES',
        'code' => 'TGR',
        'level' => 'CHILD',
        'status' => true
    ]);

    // Configuration des ranges (nMin=4, nMax=5.2, cMin=2, cMax=7)
    AnalyseRange::create([
        'analyse_id' => $analyse->id,
        'context' => 'FEMME',
        'normal_min' => 4.0,
        'normal_max' => 5.2,
        'critical_min' => 2.0,
        'critical_max' => 7.0
    ]);

    $patientFemme = new Patient(['civilite' => 'Madame']);

    // CAS 1 : valeur = 4 (Borne minimale normale)
    $result1 = $analyse->validateValue('4', $patientFemme);
    expect($result1['status'])->toBe('OK')
        ->and($result1['message'])->toBe('Valeur normale');

    // CAS 2 : valeur = 3 (Hors norme mais au-dessus du critique)
    $result2 = $analyse->validateValue('3', $patientFemme);
    expect($result2['status'])->toBe('WARNING')
        ->and($result2['message'])->toBe('VALEUR HORS PLAGE NORMALE');

    // CAS 3 : valeur = 1 (En dessous du critique)
    $result3 = $analyse->validateValue('1', $patientFemme);
    expect($result3['status'])->toBe('BLOCK')
        ->and($result3['message'])->toBe('VALEUR BIOLOGIQUEMENT INCOHÉRENTE');
        
    // CAS 4 : valeur = 5.2 (Borne maximale normale)
    $result4 = $analyse->validateValue('5.2', $patientFemme);
    expect($result4['status'])->toBe('OK');
});
