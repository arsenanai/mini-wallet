<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use Inertia\Testing\AssertableInertia;

test('dashboard page is displayed with correct props', function () {
    // Arrange: Create a user and authenticate them
    $user = User::factory()->create();

    // Act: Simulate a GET request to the dashboard
    $response = $this->actingAs($user)->get('/dashboard');

    // Assert: Check for a successful response and verify the Inertia component and props
    $response->assertOk();

    $response->assertInertia(function (AssertableInertia $page) {
        $page->component('Dashboard')
            ->has('balance')
            ->has('transactions')
            ->has('transactions.data');
    });
});