<?php

use Modules\ContractManager\Models\BillingTemplate;
use Modules\Crm\Models\Client;

test('client archived event pauses active billing templates for client', function () {
    $client = Client::factory()->create();
    $template = BillingTemplate::factory()->create([
        'client_id' => $client->id,
        'status' => 'active',
    ]);

    event(new \Modules\Crm\Events\ClientArchived($client->id, now()));

    expect($template->fresh()->status)->toBe('paused');
});
