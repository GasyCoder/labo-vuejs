<?php

use App\Models\User;

test('guest cannot access prescription endpoints', function () {
    $endpoints = [
        ['DELETE', '/secretaire/prescription/1'],
        ['POST', '/secretaire/prescription/1/restore'],
        ['DELETE', '/secretaire/prescription/1/force-delete'],
        ['POST', '/secretaire/prescription/1/archive'],
        ['POST', '/secretaire/prescription/1/unarchive'],
        ['POST', '/secretaire/prescription/1/toggle-payment'],
        ['POST', '/secretaire/prescription/1/notify'],
    ];

    foreach ($endpoints as [$method, $uri]) {
        $response = $this->json($method, $uri);
        $response->assertStatus(401);
    }
});

test('user without permissions receives error redirect', function () {
    $user = Mockery::mock(User::class)->makePartial();
    $user->shouldReceive('hasPermission')->andReturn(false);

    $this->actingAs($user);

    $endpoints = [
        ['DELETE', '/secretaire/prescription/1'],
        ['POST', '/secretaire/prescription/1/restore'],
        ['DELETE', '/secretaire/prescription/1/force-delete'],
        ['POST', '/secretaire/prescription/1/archive'],
        ['POST', '/secretaire/prescription/1/unarchive'],
        ['POST', '/secretaire/prescription/1/toggle-payment'],
        ['POST', '/secretaire/prescription/1/notify'],
    ];

    foreach ($endpoints as [$method, $uri]) {
        // Mock a referer so back() redirection works
        $response = $this->withHeaders(['referer' => '/dummy-referer'])->json($method, $uri);
        $response->assertStatus(302);
        $response->assertSessionHas('error');
    }
});
