<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Http\Request;
use Modules\ClientPortal\Http\Middleware\AuthenticateClient;
use Modules\ClientPortal\Http\Middleware\EnsureClientIsActive;
use Modules\ClientPortal\Services\PortalTabRegistry;
use Modules\Crm\Models\Company;
use Symfony\Component\HttpFoundation\Response;

it('returns only public tabs for guests and preserves sort order', function () {
    $registry = new PortalTabRegistry();

    $registry->registerTab('Private', 'private-view', 'portal.view', 'heroicon-o-lock-closed', 50);
    $registry->registerTab('Public', 'public-view', 'public', 'heroicon-o-globe-alt', 20);
    $registry->registerTab('Open', 'open-view', '', 'heroicon-o-sparkles', 10);

    $tabs = $registry->getTabs();

    expect($tabs->pluck('view')->all())->toBe(['public-view'])
        ->and($registry->count())->toBe(3);
});

it('returns authenticated tabs in display order and can clear the registry', function () {
    $user = User::factory()->create(['role' => User::ROLE_USER]);
    $this->actingAs($user);

    $registry = new PortalTabRegistry();
    $registry->registerTab('Allowed', 'allowed-view', '', null, 30);
    $registry->registerTab('Denied', 'denied-view', 'definitely-missing-permission', null, 5);

    $tabs = $registry->getTabs();

    expect($tabs->pluck('view')->all())->toBe(['allowed-view']);

    $registry->clear();

    expect($registry->count())->toBe(0)
        ->and($registry->getTabs()->all())->toBe([]);
});

it('redirects non-client users to the portal login screen', function () {
    $middleware = new AuthenticateClient();
    $user = User::factory()->create([
        'type' => User::TYPE_INTERNAL,
        'role' => User::ROLE_USER,
    ]);

    $this->actingAs($user);

    $response = $middleware->handle(Request::create('/portal/dashboard', 'GET'), function () {
        return new Response('ok');
    });

    expect($response->isRedirect(route('portal.login')))->toBeTrue();
});

it('allows client users through the client authentication middleware', function () {
    $middleware = new AuthenticateClient();
    $user = User::factory()->create([
        'type' => User::TYPE_CLIENT,
        'role' => User::ROLE_USER,
    ]);

    $this->actingAs($user);

    $response = $middleware->handle(Request::create('/portal/dashboard', 'GET'), function () {
        return new Response('allowed');
    });

    expect($response->getContent())->toBe('allowed');
});

it('blocks users without an active company in the active-client middleware', function () {
    $middleware = new EnsureClientIsActive();
    $user = User::factory()->create([
        'type' => User::TYPE_CLIENT,
        'status' => User::STATUS_ACTIVE,
    ]);

    $this->actingAs($user);

    $response = $middleware->handle(Request::create('/portal/dashboard', 'GET'), function () {
        return new Response('allowed');
    });

    expect($response->isRedirect(route('portal.login')))->toBeTrue();
    expect(session('errors'))->not->toBeNull()
        ->and(session('errors')->has('email'))->toBeTrue();
});

it('allows users with an active primary company in the active-client middleware', function () {
    $middleware = new EnsureClientIsActive();
    $user = User::factory()->create([
        'type' => User::TYPE_CLIENT,
        'status' => User::STATUS_ACTIVE,
    ]);
    $company = Company::factory()->create(['is_active' => true]);

    $user->companies()->attach($company->id, [
        'role_id' => 1,
        'status' => 'approved',
        'is_primary' => true,
        'is_approver' => false,
        'approval_limit' => null,
        'manager_id' => null,
    ]);

    $this->actingAs($user);

    $response = $middleware->handle(Request::create('/portal/dashboard', 'GET'), function () {
        return new Response('allowed');
    });

    expect($response->getContent())->toBe('allowed');
});
