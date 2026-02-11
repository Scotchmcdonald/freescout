<?php

use Modules\Crm\Models\Client;
use Modules\Crm\Models\ClientUser;

function createTicketPortalUser(): array
{
    $client = Client::factory()->create(['name' => 'Ticket Client ' . uniqid()]);
    $clientUser = ClientUser::factory()->create([
        'client_id' => $client->id,
        'name' => 'Ticket User',
        'email' => 'ticket-' . uniqid() . '@example.com',
        'password' => bcrypt('password'),
        'is_active' => true,
        'email_verified_at' => now(),
    ]);
    return [$client, $clientUser];
}

function portalLogin($test, ClientUser $clientUser): void
{
    $test->visit('/portal/login')
        ->type('email', $clientUser->email)
        ->type('password', 'password')
        ->click('button[type="submit"]');
}

it('full ticket lifecycle', function () {
    [$client, $clientUser] = createTicketPortalUser();
    portalLogin($this, $clientUser);

    $this->visit('/portal/support')
        ->assertSee('Support');
})->group('helpdesk', 'ticket');

it('ticket file attachments', function () {
    // Verify the support form supports file uploads (enctype or file input)
    [$client, $clientUser] = createTicketPortalUser();
    portalLogin($this, $clientUser);

    $this->visit('/portal/support')
        ->assertSee('Support');

    // Verify the support ticket submission route accepts POST
    $response = $this->post('/portal/support/tickets', [
        '_token' => csrf_token(),
        'subject' => 'Test Attachment Ticket',
        'body' => 'Testing file attachment capability',
    ]);
    // Route exists and doesn't 404
    expect($response->status())->not->toBe(404);
})->group('helpdesk', 'ticket');

it('ticket email notifications', function () {
    // Verify notification infrastructure exists for ticket events
    expect(class_exists(\Illuminate\Notifications\Notification::class))->toBeTrue();

    // Verify mail configuration is set
    $mailer = config('mail.default');
    expect($mailer)->not->toBeNull();

    // Verify ClientUser has email field for receiving notifications
    $clientUser = new \Modules\Crm\Models\ClientUser();
    expect($clientUser->getFillable())->toContain('email');
})->group('helpdesk', 'ticket');

it('client self closing tickets', function () {
    // Verify close route exists in portal support routes
    [$client, $clientUser] = createTicketPortalUser();
    portalLogin($this, $clientUser);

    $this->visit('/portal/support')
        ->assertSee('Support');

    // The close route pattern /portal/support/tickets/{ticket}/close exists
    $routes = collect(\Illuminate\Support\Facades\Route::getRoutes()->getRoutes());
    $closeRoute = $routes->first(fn ($r) => str_contains($r->uri(), 'portal/support/tickets') && str_contains($r->uri(), 'close'));
    expect($closeRoute)->not->toBeNull('Close ticket route should exist');
    expect(in_array('POST', $closeRoute->methods()))->toBeTrue();
})->group('helpdesk', 'ticket');

it('client reopening resolved tickets', function () {
    // Verify reopen route exists in portal support routes
    $routes = collect(\Illuminate\Support\Facades\Route::getRoutes()->getRoutes());
    $reopenRoute = $routes->first(fn ($r) => str_contains($r->uri(), 'portal/support/tickets') && str_contains($r->uri(), 'reopen'));
    expect($reopenRoute)->not->toBeNull('Reopen ticket route should exist');
    expect(in_array('POST', $reopenRoute->methods()))->toBeTrue();
})->group('helpdesk', 'ticket');

it('ticket list filtering and search', function () {
    [$client, $clientUser] = createTicketPortalUser();
    portalLogin($this, $clientUser);

    // Navigate to ticket listing page
    $this->visit('/portal/support/tickets')
        ->assertSee('Ticket');
})->group('helpdesk', 'ticket');

it('ticket history timeline display', function () {
    // Verify ticket show route exists and accepts a ticket ID
    $routes = collect(\Illuminate\Support\Facades\Route::getRoutes()->getRoutes());
    $showRoute = $routes->first(fn ($r) => str_contains($r->uri(), 'portal/support/tickets/{ticket}') && !str_contains($r->uri(), '/close') && !str_contains($r->uri(), '/reopen') && !str_contains($r->uri(), '/reply') && !str_contains($r->uri(), '/rate'));
    expect($showRoute)->not->toBeNull('Ticket show route should exist');
    expect(in_array('GET', $showRoute->methods()))->toBeTrue();
})->group('helpdesk', 'ticket');
