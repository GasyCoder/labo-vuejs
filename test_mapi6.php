<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$provider = \App\Models\SmsProvider::where('driver', 'mapi')->first();
$username = $provider->credentials['username'] ?? '';
$password = $provider->credentials['password'] ?? '';
$apiUrl = 'https://messaging.mapi.mg/api';

echo "Testing login with multiple login tries to mimic a session hijacking attempt...\n";
$response = \Illuminate\Support\Facades\Http::asMultipart()->post($apiUrl.'/authentication/login', [
    ['name' => 'username', 'contents' => $username],
    ['name' => 'password', 'contents' => $password],
]);
echo "Lowercase credentials -> Status: " . $response->status() . " Body: " . $response->body() . "\n";


