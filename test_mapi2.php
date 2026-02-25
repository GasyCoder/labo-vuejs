<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$username = config('services.mapi_sms.username') ?: env('MAPI_SMS_USERNAME');
$password = config('services.mapi_sms.password') ?: env('MAPI_SMS_PASSWORD');
$apiUrl = 'https://messaging.mapi.mg/api';

echo "Testing login with Force...\n";
$response = \Illuminate\Support\Facades\Http::asMultipart()->post($apiUrl.'/authentication/login', [
    ['name' => 'Username', 'contents' => $username],
    ['name' => 'Password', 'contents' => $password],
    ['name' => 'Force', 'contents' => 'true'],
    ['name' => 'force', 'contents' => 'true'],
    ['name' => 'Logout', 'contents' => 'true'],
]);
echo "Status: " . $response->status() . "\n";
echo "Body: " . $response->body() . "\n";

