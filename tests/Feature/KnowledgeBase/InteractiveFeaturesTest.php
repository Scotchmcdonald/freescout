<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('knowledge base exploration page loads with tours for admin', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

    $this->actingAs($admin)
        ->get('/knowledgebase/explore?view=tour')
        ->assertOk()
        ->assertSee('Admin Setup')
        ->assertSee('Start Demo Tour');
});

it('knowledge base filters tours appropriately', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

    $response = $this->actingAs($admin)
        ->get('/knowledgebase/explore?view=tour')
        ->assertOk();

    // Multi-page Admin Setup tour should be visible
    $response->assertSee('Admin Setup');
    // Single-page Knowledge Base Tour should not be in results
    $response->assertDontSee('Knowledge Base Tour', escape: false);
});

it('redirects unauthenticated guest accessing knowledge base explore', function () {
    // Authorization boundary: knowledge base routes require authentication
    $this->get('/knowledgebase/explore')
        ->assertRedirect();
});

it('redirects unauthenticated guest accessing knowledge base tour view', function () {
    // Authorization boundary: tour view also requires authentication
    $this->get('/knowledgebase/explore?view=tour')
        ->assertRedirect();
});
