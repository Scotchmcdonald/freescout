<?php

declare(strict_types=1);

namespace Tests\Unit\Crm;

use Modules\Crm\Models\Client;
use Modules\Crm\Models\Contact;
use Tests\PureUnitTestCase;

/**
 * Bare subclasses that prevent Eloquent's casts resolver and DB-touching
 * model observers from firing during tests.
 */
final class TestClient extends Client
{
    protected static function booted(): void {}

    protected function casts(): array
    {
        return [];
    }
}

final class TestContact extends Contact
{
    protected static function booted(): void {}

    protected function casts(): array
    {
        return [];
    }
}

// ─────────────────────────────────────────────────────────────────────────────
// Client::isActive
// ─────────────────────────────────────────────────────────────────────────────

class ClientIsActiveTest extends PureUnitTestCase
{
    public function test_is_active_returns_true_when_status_is_active(): void
    {
        $client = new TestClient;
        $client->status = 'active';

        $this->assertTrue($client->isActive());
    }

    public function test_is_active_returns_false_when_status_is_inactive(): void
    {
        $client = new TestClient;
        $client->status = 'inactive';

        $this->assertFalse($client->isActive());
    }

    public function test_is_active_returns_false_when_status_is_suspended(): void
    {
        $client = new TestClient;
        $client->status = 'suspended';

        $this->assertFalse($client->isActive());
    }

    public function test_is_active_is_case_sensitive(): void
    {
        $client = new TestClient;
        $client->status = 'Active';   // uppercase A

        $this->assertFalse($client->isActive());
    }
}

// ─────────────────────────────────────────────────────────────────────────────
// Contact::getFullNameAttribute
// ─────────────────────────────────────────────────────────────────────────────

class ContactFullNameTest extends PureUnitTestCase
{
    private function contact(string $first, string $last): TestContact
    {
        $c = new TestContact;
        $c->first_name = $first;
        $c->last_name = $last;

        return $c;
    }

    public function test_full_name_concatenates_first_and_last_name(): void
    {
        $this->assertSame('Jane Smith', $this->contact('Jane', 'Smith')->getFullNameAttribute());
    }

    public function test_full_name_includes_space_separator(): void
    {
        $name = $this->contact('Bob', 'Jones')->getFullNameAttribute();
        $this->assertStringContainsString(' ', $name);
    }

    public function test_full_name_with_single_part_names(): void
    {
        $this->assertSame('Alice Wonderland', $this->contact('Alice', 'Wonderland')->getFullNameAttribute());
    }

    public function test_full_name_with_hyphenated_last_name(): void
    {
        $this->assertSame('Mary Smith-Jones', $this->contact('Mary', 'Smith-Jones')->getFullNameAttribute());
    }

    public function test_full_name_preserves_exact_casing(): void
    {
        $this->assertSame('DE LA CRUZ', $this->contact('DE', 'LA CRUZ')->getFullNameAttribute());
    }
}
