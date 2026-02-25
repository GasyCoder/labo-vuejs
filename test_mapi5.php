<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$provider = \App\Models\SmsProvider::where('driver', 'mapi')->first();
$username = $provider->credentials['username'] ?? '';
$password = $provider->credentials['password'] ?? '';
$apiUrl = 'https://messaging.mapi.mg/api';

echo "Testing normal logout request that the user session might use...\n";
$response = \Illuminate\Support\Facades\Http::post($apiUrl.'/authentication/logout');
echo "Empty Logout -> Status: " . $response->status() . " Body: " . $response->body() . "\n";

echo "Testing login right after that...\n";
$response = \Illuminate\Support\Facades\Http::asMultipart()->post($apiUrl.'/authentication/login', [
    ['name' => 'Username', 'contents' => $username],
    ['name' => 'Password', 'contents' => $password],
]);
echo "Login -> Status: " . $response->status() . " Body: " . $response->body() . "\n";

