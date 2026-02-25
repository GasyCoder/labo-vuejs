<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$username = config('services.mapi_sms.username') ?: env('MAPI_SMS_USERNAME');
$password = config('services.mapi_sms.password') ?: env('MAPI_SMS_PASSWORD');
$apiUrl = 'https://messaging.mapi.mg/api';

echo "Testing logout without token but with multipart...\n";
$response = \Illuminate\Support\Facades\Http::asMultipart()->post($apiUrl.'/authentication/logout', [
    ['name' => 'Username', 'contents' => $username],
    ['name' => 'Password', 'contents' => $password],
]);
echo "Status: " . $response->status() . "\n";
echo "Body: " . $response->body() . "\n";

echo "Testing logout without token but with form...\n";
$response = \Illuminate\Support\Facades\Http::asForm()->post($apiUrl.'/authentication/logout', [
    'Username' => $username,
    'Password' => $password,
]);
echo "Status: " . $response->status() . "\n";
echo "Body: " . $response->body() . "\n";

echo "Testing login...\n";
$response = \Illuminate\Support\Facades\Http::asMultipart()->post($apiUrl.'/authentication/login', [
    ['name' => 'Username', 'contents' => $username],
    ['name' => 'Password', 'contents' => $password],
]);
echo "Status: " . $response->status() . "\n";
echo "Body: " . $response->body() . "\n";
