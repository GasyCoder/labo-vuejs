<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$provider = \App\Models\SmsProvider::where('driver', 'mapi')->first();
$username = $provider->credentials['username'] ?? '';
$password = $provider->credentials['password'] ?? '';
$apiUrl = 'https://messaging.mapi.mg/api';

echo "Testing login with force=true...\n";
$response = \Illuminate\Support\Facades\Http::asMultipart()->post($apiUrl.'/authentication/login', [
    ['name' => 'Username', 'contents' => $username],
    ['name' => 'Password', 'contents' => $password],
    ['name' => 'force', 'contents' => 'true'],
]);
echo "force=true -> Status: " . $response->status() . " Body: " . $response->body() . "\n";

echo "Testing login with Force=true...\n";
$response = \Illuminate\Support\Facades\Http::asMultipart()->post($apiUrl.'/authentication/login', [
    ['name' => 'Username', 'contents' => $username],
    ['name' => 'Password', 'contents' => $password],
    ['name' => 'Force', 'contents' => 'true'],
]);
echo "Force=true -> Status: " . $response->status() . " Body: " . $response->body() . "\n";

echo "Testing with only Username and Password (control)...\n";
$response = \Illuminate\Support\Facades\Http::asMultipart()->post($apiUrl.'/authentication/login', [
    ['name' => 'Username', 'contents' => $username],
    ['name' => 'Password', 'contents' => $password],
]);
echo "Normal -> Status: " . $response->status() . " Body: " . $response->body() . "\n";

