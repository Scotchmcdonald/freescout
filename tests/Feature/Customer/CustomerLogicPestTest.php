<?php

use App\Models\Customer;
use App\Models\Email;

test('incoming email creates new customer', function () {
    $email = 'newcustomer@example.com';
    $this->assertDatabaseMissing('emails', ['email' => $email]);

    $customer = Customer::create($email, ['first_name' => 'New', 'last_name' => 'Cust']);

    expect($customer)->not->toBeNull();
    $this->assertDatabaseHas('customers', ['id' => $customer->id]);
    $this->assertDatabaseHas('emails', ['email' => $email, 'customer_id' => $customer->id, 'type' => 1]);
});

test('incoming email finds existing customer', function () {
    $existing = Customer::factory()->create();
    $email = 'existing@example.com';
    Email::factory()->create(['customer_id' => $existing->id, 'email' => $email]);

    $found = Customer::create($email, ['first_name' => 'Diff']);

    expect($found->id)->toBe($existing->id);
    expect($found->first_name)->not->toBe('Diff'); // Should not update existing name
    $this->assertDatabaseCount('customers', 1); // Assuming database was fresh
});

test('customer create sanitizes email', function () {
    $raw = 'Test.User@EXAMPLE.COM';
    $customer = Customer::create($raw, ['first_name' => 'T']);

    $this->assertDatabaseHas('emails', ['email' => 'test.user@example.com']);
});

test('customer create returns null for invalid email', function () {
    $customer = Customer::create('not-an-email', ['first_name' => 'Invalid']);
    expect($customer)->toBeNull();
});

test('customer create handles trailing dots', function () {
    $customer = Customer::create('user@example.com....', ['first_name' => 'Dot']);

    expect($customer)->not->toBeNull();
    $this->assertDatabaseHas('emails', ['email' => 'user@example.com']);
});

// SetData Tests
test('set data fills empty fields only by default', function () {
    $customer = Customer::factory()->create([
        'first_name' => 'Original',
        'company' => '',
    ]);

    $customer->setData(['first_name' => 'New', 'company' => 'New Comp'], false, true);

    $customer->refresh();
    expect($customer->first_name)->toBe('Original');
    expect($customer->company)->toBe('New Comp');
});

test('set data replaces all fields when replace is true', function () {
    $customer = Customer::factory()->create([
        'first_name' => 'Original',
        'company' => 'Old Comp',
    ]);

    $customer->setData(['first_name' => 'New', 'company' => 'New Comp'], true, true);

    $customer->refresh();
    expect($customer->first_name)->toBe('New');
    expect($customer->company)->toBe('New Comp');
});

test('set data uses background as notes if notes empty', function () {
    $customer = Customer::factory()->create(['notes' => '']);
    $customer->setData(['background' => 'BG Info'], false, true);

    expect($customer->refresh()->notes)->toBe('BG Info');
});

test('set data handles existing name components logic', function () {
    // If first name exists, don't set last name blindly?
    // Wait, the original test "test_customer_set_data_does_not_set_last_name_if_first_name_exists" logic seemed specific.
    // Logic: if current info is not empty, don't overwrite unless replace=true.

    $customer = Customer::factory()->create(['first_name' => 'John', 'last_name' => '']);

    // Attempt to set last name with replace=false
    // Ideally this SHOULD set last name because current last name is empty.
    // BUT the legacy test says: "does not set last name if first name exists"
    // Let's re-verify the legacy test code to be sure I understood the logic or if it was weird.
    // Legacy Code:
    // $customer = Customer::factory()->create(['first_name' => 'John', 'last_name' => '']);
    // $customer->setData(['last_name' => 'Should Not Be Set'], false, true);
    // $this->assertEquals('', $customer->last_name);

    // This implies that if *any* name part exists, maybe it treats the name as "occupied"?

    $customer->setData(['last_name' => 'Should Not Be Set'], false, true);
    expect($customer->refresh()->last_name)->toBe('');
    // If this fails, then my understanding of the Model logic vs Legacy Test assertion is off.
    // I will trust the legacy test assertion for now.
});

// Email Sanitization Unit Tests
test('sanitize email logic', function () {
    expect(Email::sanitizeEmail('User@EXAMPLE.COM'))->toBe('user@example.com');
    expect(Email::sanitizeEmail('user..name.@example.com'))->toBe('user..name@example.com');
    expect(Email::sanitizeEmail('user@example.com.....'))->toBe('user@example.com');

    expect(Email::sanitizeEmail('no-at-sign'))->toBeFalse();
    expect(Email::sanitizeEmail(''))->toBeFalse();
    expect(Email::sanitizeEmail(null))->toBeFalse();
});

test('customer create handles multiple emails data array', function () {
    $customer = Customer::create('primary@example.com', [
        'first_name' => 'Multi',
        'emails' => [
            ['value' => 'secondary@example.com', 'type' => 2],
        ],
    ]);

    // It creates the primary from the first arg, does it create the secondary?
    // The legacy test didn't assert the secondary email creation, only the primary.
    // Line 291 Legacy: $this->assertDatabaseHas('emails', ['email' => $emailAddress, 'type' => 1]);
    // It did NOT assert secondary.
    expect($customer)->not->toBeNull();
});

test('set data returns false if no changes', function () {
    $customer = Customer::factory()->create(['notes' => 'Existing']);
    $res = $customer->setData(['notes' => 'New'], false, false); // replace=false
    expect($res)->toBeFalse();
});

test('set data saves only if save true', function () {
    $customer = Customer::factory()->create(['company' => '']);

    // Save=false
    $customer->setData(['company' => 'Temp'], false, false);
    expect($customer->company)->toBe('Temp'); // In memory
    expect($customer->refresh()->company)->toBe(''); // In DB

    // Save=true
    $customer->setData(['company' => 'Perm'], false, true);
    expect($customer->refresh()->company)->toBe('Perm'); // In DB
});
