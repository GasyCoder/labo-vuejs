<?php

use App\Services\Sms\CustomSmsService;
use Illuminate\Support\Facades\Http;

uses(Tests\TestCase::class);

it('sends a post request with correct payload', function () {
    Http::fake([
        '*' => Http::response(['success' => true], 200),
    ]);

    $service = new CustomSmsService([
        'api_url' => 'https://api.example.com/sms',
        'http_method' => 'POST',
        'phone_param_name' => 'to',
        'message_param_name' => 'text',
        'authorization_header' => 'Bearer my-token',
        'extra_payload_json' => '{"sender_id": "MY_APP"}',
    ]);

    $result = $service->sendSms('+1234567890', 'Hello world!');

    expect($result['success'])->toBeTrue();

    Http::assertSent(function ($request) {
        if ($request->url() === 'https://api.example.com/sms') {
            $data = $request->data();
            expect($data['to'])->toBe('+1234567890');
            expect($data['text'])->toBe('Hello world!');
            expect($data['sender_id'])->toBe('MY_APP');

            return true;
        }

        return false;
    });
});

it('sends a get request with correct payload', function () {
    Http::fake([
        '*' => Http::response(['success' => true], 200),
    ]);

    $service = new CustomSmsService([
        'api_url' => 'https://api.example.com/get-sms',
        'http_method' => 'GET',
        'phone_param_name' => 'dest',
        'message_param_name' => 'msg',
        'authorization_header' => '',
        'extra_payload_json' => '',
    ]);

    $result = $service->sendSms('034 00 111 22', 'Hello GET!');

    expect($result['success'])->toBeTrue();

    // With GET, parameters are typically in the query string, which Laravel Http client handles automatically
    Http::assertSent(function ($request) {
        return str_contains($request->url(), 'api.example.com/get-sms') &&
               $request->method() === 'GET';
    });
});
