<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

$user = App\Models\User::where('type', 'superadmin')->first();

$request = Illuminate\Http\Request::create(
    '/secretaire/patients/2',
    'PUT',
    [
        'nom' => 'Nom update from code',
        'civilite' => 'Monsieur',
    ]
);
$app->make('auth')->login($user);

$response = $kernel->handle($request);
echo 'Status: '.$response->getStatusCode()."\n";
echo 'Location: '.$response->headers->get('Location')."\n";
if ($response->getStatusCode() === 302 && ! $response->headers->get('Location')) {
    echo 'Session errors: '.json_encode(session()->get('errors')?->getBag('default')->getMessages())."\n";
}
