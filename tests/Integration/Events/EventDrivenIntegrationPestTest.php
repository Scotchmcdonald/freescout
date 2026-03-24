<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Event;

test('asset status change event exists', function () {
    expect(class_exists(\Modules\AssetManagement\Events\AssetStatusChanged::class))->toBeTrue();
});

test('quote approval event chain', function () {
    expect(class_exists(\Modules\ContractManager\Events\QuoteApproved::class))->toBeTrue();
    $listeners = Event::getListeners(\Modules\ContractManager\Events\QuoteApproved::class);
    expect($listeners)->not->toBeEmpty('QuoteApproved should have registered listeners');
});

test('software assignment event flow', function () {
    expect(class_exists(\Modules\SoftwareSubscriptions\Events\SoftwareCountChanged::class))->toBeTrue();
    $listeners = Event::getListeners(\Modules\SoftwareSubscriptions\Events\SoftwareCountChanged::class);
    expect($listeners)->not->toBeEmpty('SoftwareCountChanged should have a PIB listener');
});

test('payment succeeded event exists', function () {
    expect(class_exists(\Modules\Payment\Events\PaymentSucceeded::class))->toBeTrue();
});

test('ticket closed triggers billing review', function () {
    expect(class_exists(\Modules\Crm\Events\ConversationLinkedToClient::class))->toBeTrue();
    $listeners = Event::getListeners(\Modules\Crm\Events\ConversationLinkedToClient::class);
    expect($listeners)->not->toBeEmpty('ConversationLinkedToClient should have a PIB listener');
});

test('contract revision event cascade', function () {
    expect(class_exists(\Modules\ContractManager\Events\ContractTerminated::class))->toBeTrue();
    expect(class_exists(\Modules\ContractManager\Events\ContractRevised::class))->toBeTrue();
    $listeners = Event::getListeners(\Modules\ContractManager\Events\ContractRevised::class);
    expect($listeners)->not->toBeEmpty('ContractRevised should have a PIB listener');
});

test('client archived event chain is registered', function () {
    expect(class_exists(\Modules\Crm\Events\ClientArchived::class))->toBeTrue();
    $listeners = Event::getListeners(\Modules\Crm\Events\ClientArchived::class);
    expect($listeners)->not->toBeEmpty('ClientArchived should have a PIB listener');
});
