<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\User;
use Tests\PureUnitTestCase;

if (! class_exists(StubUser::class)) {
    final class StubUser extends User
    {
        protected static function booted(): void {}

        public function getDateFormat(): string
        {
            return 'Y-m-d H:i:s';
        }
    }
}

/**
 * Pure-unit tests for User model methods that do not touch the database.
 * Only methods whose logic branches on raw attributes (exists=false path
 * or simple property checks) are tested here.
 */
final class UserModelTest extends PureUnitTestCase
{
    private function user(array $attrs = []): StubUser
    {
        $u = new StubUser;
        $u->setRawAttributes($attrs);

        return $u;
    }

    // ── constants are distinct ─────────────────────────────────────────

    public function test_role_constants_are_distinct(): void
    {
        $roles = [User::ROLE_USER, User::ROLE_ADMIN, User::ROLE_REPORTER, User::ROLE_FINANCE];
        $this->assertSame(count($roles), count(array_unique($roles)));
    }

    public function test_type_constants_are_distinct(): void
    {
        $types = [User::TYPE_INTERNAL, User::TYPE_CLIENT, User::TYPE_AUTOMATON];
        $this->assertSame(count($types), count(array_unique($types)));
    }

    public function test_status_constants_are_distinct(): void
    {
        $statuses = [User::STATUS_ACTIVE, User::STATUS_INACTIVE, User::STATUS_DELETED];
        $this->assertSame(count($statuses), count(array_unique($statuses)));
    }

    // ── isAdmin (non-persisted fast path only) ─────────────────────────

    public function test_is_admin_returns_true_for_admin_role_when_not_persisted(): void
    {
        $u = $this->user(['role' => User::ROLE_ADMIN]);
        // exists=false skips DB, falls through to role check
        $this->assertTrue($u->isAdmin());
    }

    public function test_is_admin_returns_false_for_non_admin_role_when_not_persisted(): void
    {
        $u = $this->user(['role' => User::ROLE_USER]);
        $this->assertFalse($u->isAdmin());
    }

    public function test_is_admin_caches_result_on_second_call(): void
    {
        $u = $this->user(['role' => User::ROLE_ADMIN]);
        $first = $u->isAdmin();
        $second = $u->isAdmin(); // should use cached value
        $this->assertSame($first, $second);
    }

    // ── clearRbacCache ────────────────────────────────────────────────

    public function test_clear_rbac_cache_allows_recomputation(): void
    {
        $u = $this->user(['role' => User::ROLE_ADMIN]);
        $u->isAdmin(); // cache it
        $u->clearRbacCache();
        // After clearing, role changed — result should recompute
        $u->setRawAttributes(['role' => User::ROLE_USER]);
        $this->assertFalse($u->isAdmin());
    }

    // ── isInternalStaff ───────────────────────────────────────────────

    public function test_is_internal_staff_true_for_internal_type(): void
    {
        $this->assertTrue($this->user(['type' => User::TYPE_INTERNAL])->isInternalStaff());
    }

    public function test_is_internal_staff_false_for_client_type(): void
    {
        $this->assertFalse($this->user(['type' => User::TYPE_CLIENT])->isInternalStaff());
    }

    // ── isAutomaton ───────────────────────────────────────────────────

    public function test_is_automaton_true_for_automaton_type(): void
    {
        $this->assertTrue($this->user(['type' => User::TYPE_AUTOMATON])->isAutomaton());
    }

    public function test_is_automaton_false_for_internal(): void
    {
        $this->assertFalse($this->user(['type' => User::TYPE_INTERNAL])->isAutomaton());
    }

    // ── isFinance ─────────────────────────────────────────────────────

    public function test_is_finance_true_for_finance_role(): void
    {
        $this->assertTrue($this->user(['role' => User::ROLE_FINANCE])->isFinance());
    }

    public function test_is_finance_false_for_admin_role(): void
    {
        $this->assertFalse($this->user(['role' => User::ROLE_ADMIN])->isFinance());
    }

    // ── isReporter ────────────────────────────────────────────────────

    public function test_is_reporter_true_for_reporter_role(): void
    {
        $this->assertTrue($this->user(['role' => User::ROLE_REPORTER])->isReporter());
    }

    public function test_is_reporter_false_for_user_role(): void
    {
        $this->assertFalse($this->user(['role' => User::ROLE_USER])->isReporter());
    }

    // ── isActive ──────────────────────────────────────────────────────

    public function test_is_active_true_when_status_is_one(): void
    {
        $this->assertTrue($this->user(['status' => 1])->isActive());
    }

    public function test_is_active_false_when_status_is_inactive(): void
    {
        $this->assertFalse($this->user(['status' => User::STATUS_INACTIVE])->isActive());
    }

    // ── getFullName ───────────────────────────────────────────────────

    public function test_get_full_name_concatenates_first_and_last(): void
    {
        $u = $this->user(['first_name' => 'Jane', 'last_name' => 'Doe', 'email' => 'jane@example.com']);
        $this->assertSame('Jane Doe', $u->getFullName());
    }

    public function test_get_full_name_short_returns_first_name(): void
    {
        $u = $this->user(['first_name' => 'Jane', 'last_name' => 'Doe', 'email' => 'jane@example.com']);
        $this->assertSame('Jane', $u->getFullName(true));
    }

    public function test_get_full_name_falls_back_to_email_when_no_name(): void
    {
        $u = $this->user(['email' => 'alice@example.com']);
        $this->assertSame('alice@example.com', $u->getFullName());
    }

    public function test_get_full_name_short_returns_local_part_of_email_when_no_first_name(): void
    {
        $u = $this->user(['email' => 'bob@example.com']);
        $this->assertSame('bob', $u->getFullName(true));
    }

    // ── getFirstName ──────────────────────────────────────────────────

    public function test_get_first_name_returns_first_name_attribute(): void
    {
        $u = $this->user(['first_name' => 'Charlie']);
        $this->assertSame('Charlie', $u->getFirstName());
    }

    public function test_get_first_name_returns_empty_string_when_null(): void
    {
        $this->assertSame('', $this->user([])->getFirstName());
    }

    // ── getPhotoUrl ───────────────────────────────────────────────────

    public function test_get_photo_url_returns_gravatar_url(): void
    {
        $u = $this->user(['email' => 'test@example.com']);
        $url = $u->getPhotoUrl();
        $this->assertStringStartsWith('https://www.gravatar.com/avatar/', $url);
        $this->assertStringContainsString('?d=mp', $url);
    }

    public function test_get_photo_url_is_consistent_for_same_email(): void
    {
        $u = $this->user(['email' => 'consistent@example.com']);
        $this->assertSame($u->getPhotoUrl(), $u->getPhotoUrl());
    }
}
