<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$provider = \App\Models\SmsProvider::where('driver', 'mapi')->first();
$username = $provider->credentials['username'] ?? '';
$password = $provider->credentials['password'] ?? '';
$apiUrl = 'https://messaging.mapi.mg/api';

echo "Username length: " . strlen($username) . "\n";
echo "Password length: " . strlen($password) . "\n";

echo "Testing login to see what happens...\n";
$response = \Illuminate\Support\Facades\Http::asMultipart()->post($apiUrl.'/authentication/login', [
    ['name' => 'Username', 'contents' => $username],
    ['name' => 'Password', 'contents' => $password],
]);
echo "Status: " . $response->status() . "\n";
echo "Body: " . $response->body() . "\n";

$data = $response->json();
if (str_contains($response->body(), 'en cours de session')) {
    echo "Session is stuck! Trying to logout with username/password...\n";
    $logoutResp = \Illuminate\Support\Facades\Http::asMultipart()->post($apiUrl.'/authentication/logout', [
        ['name' => 'Username', 'contents' => $username],
        ['name' => 'Password', 'contents' => $password],
    ]);
    echo "Logout response: " . $logoutResp->body() . "\n";

    echo "Trying form logout...\n";
    $logoutResp2 = \Illuminate\Support\Facades\Http::asForm()->post($apiUrl.'/authentication/logout', [
        'Username' => $username,
        'Password' => $password,
    ]);
    echo "Form Logout response: " . $logoutResp2->body() . "\n";

    echo "Trying to get token first... does login return token?\n";
    print_r($data);
} else {
    echo "Logged in successfully.\n";
$token = $data['token'] ?? '';
echo "Token: $token\n";
if ($token) {
    \Illuminate\Support\Facades\Http::withHeaders(['Authorization' => $token])->post($apiUrl.'/authentication/logout');
    echo "Logged out.\n";
}
}

