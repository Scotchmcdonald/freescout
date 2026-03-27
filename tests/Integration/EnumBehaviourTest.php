<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Enums\ConversationStatus;
use App\Enums\ConversationType;
use App\Enums\ThreadState;
use App\Enums\ThreadType;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Enums\UserType;
use Tests\TestCase;

/**
 * Enums behaviour: labels, computed accessors, helpers.
 * Note: label() methods use __() helper, so the full framework must be booted.
 */
class EnumBehaviourTest extends TestCase
{
    // ── ConversationStatus ──────────────────────────────────────────────────

    public function test_conversation_status_values_are_correct(): void
    {
        $this->assertSame(1, ConversationStatus::Active->value);
        $this->assertSame(2, ConversationStatus::Pending->value);
        $this->assertSame(3, ConversationStatus::Closed->value);
        $this->assertSame(4, ConversationStatus::Spam->value);
    }

    public function test_conversation_status_from_value(): void
    {
        $this->assertSame(ConversationStatus::Active, ConversationStatus::from(1));
        $this->assertSame(ConversationStatus::Spam, ConversationStatus::from(4));
    }

    public function test_conversation_status_label_returns_string(): void
    {
        foreach (ConversationStatus::cases() as $case) {
            $this->assertIsString($case->label());
            $this->assertNotEmpty($case->label());
        }
    }

    public function test_conversation_status_color_returns_hex_string(): void
    {
        foreach (ConversationStatus::cases() as $case) {
            $color = $case->color();
            $this->assertStringStartsWith('#', $color);
        }
    }

    public function test_conversation_status_css_class_contains_text_class(): void
    {
        foreach (ConversationStatus::cases() as $case) {
            $this->assertStringContainsString('text-', $case->cssClass());
        }
    }

    public function test_conversation_status_options_covers_all_cases(): void
    {
        $options = ConversationStatus::options();
        $this->assertCount(4, $options);
        $this->assertArrayHasKey(1, $options);
        $this->assertArrayHasKey(4, $options);
    }

    // ── ConversationType ───────────────────────────────────────────────────

    public function test_conversation_type_values(): void
    {
        $this->assertSame(1, ConversationType::Email->value);
        $this->assertSame(2, ConversationType::Phone->value);
        $this->assertSame(3, ConversationType::Chat->value);
    }

    public function test_conversation_type_icon_is_non_empty_string(): void
    {
        foreach (ConversationType::cases() as $case) {
            $this->assertNotEmpty($case->icon());
        }
    }

    public function test_conversation_type_label_is_non_empty_string(): void
    {
        foreach (ConversationType::cases() as $case) {
            $this->assertNotEmpty($case->label());
        }
    }

    public function test_conversation_type_options_count(): void
    {
        $this->assertCount(3, ConversationType::options());
    }

    // ── ThreadState ────────────────────────────────────────────────────────

    public function test_thread_state_values(): void
    {
        $this->assertSame(1, ThreadState::DRAFT->value);
        $this->assertSame(2, ThreadState::PUBLISHED->value);
        $this->assertSame(3, ThreadState::HIDDEN->value);
        $this->assertSame(4, ThreadState::DELETED->value);
    }

    public function test_thread_state_label_non_empty(): void
    {
        foreach (ThreadState::cases() as $case) {
            $this->assertNotEmpty($case->label());
        }
    }

    public function test_thread_state_is_visible_only_for_published(): void
    {
        $this->assertTrue(ThreadState::PUBLISHED->isVisible());
        $this->assertFalse(ThreadState::DRAFT->isVisible());
        $this->assertFalse(ThreadState::HIDDEN->isVisible());
        $this->assertFalse(ThreadState::DELETED->isVisible());
    }

    public function test_thread_state_is_editable(): void
    {
        $this->assertTrue(ThreadState::DRAFT->isEditable());
        $this->assertTrue(ThreadState::PUBLISHED->isEditable());
        $this->assertFalse(ThreadState::HIDDEN->isEditable());
        $this->assertFalse(ThreadState::DELETED->isEditable());
    }

    // ── ThreadType ─────────────────────────────────────────────────────────

    public function test_thread_type_values(): void
    {
        $this->assertSame(1, ThreadType::MESSAGE->value);
        $this->assertSame(2, ThreadType::NOTE->value);
        $this->assertSame(3, ThreadType::DRAFT->value);
    }

    public function test_thread_type_label(): void
    {
        $this->assertSame('Reply', ThreadType::MESSAGE->label());
        $this->assertSame('Internal Note', ThreadType::NOTE->label());
        $this->assertSame('Draft', ThreadType::DRAFT->label());
    }

    public function test_thread_type_is_internal(): void
    {
        $this->assertFalse(ThreadType::MESSAGE->isInternal());
        $this->assertTrue(ThreadType::NOTE->isInternal());
        $this->assertTrue(ThreadType::DRAFT->isInternal());
    }

    public function test_thread_type_sends_email(): void
    {
        $this->assertTrue(ThreadType::MESSAGE->sendsEmail());
        $this->assertFalse(ThreadType::NOTE->sendsEmail());
        $this->assertFalse(ThreadType::DRAFT->sendsEmail());
    }

    // ── UserRole ───────────────────────────────────────────────────────────

    public function test_user_role_values(): void
    {
        $this->assertSame(1, UserRole::User->value);
        $this->assertSame(2, UserRole::Admin->value);
        $this->assertSame(3, UserRole::Reporter->value);
        $this->assertSame(4, UserRole::Finance->value);
    }

    public function test_user_role_label_non_empty(): void
    {
        foreach (UserRole::cases() as $case) {
            $this->assertNotEmpty($case->label());
        }
    }

    public function test_user_role_description_non_empty(): void
    {
        foreach (UserRole::cases() as $case) {
            $this->assertNotEmpty($case->description());
        }
    }

    public function test_user_role_is_admin_only_for_admin(): void
    {
        $this->assertTrue(UserRole::Admin->isAdmin());
        $this->assertFalse(UserRole::User->isAdmin());
        $this->assertFalse(UserRole::Reporter->isAdmin());
        $this->assertFalse(UserRole::Finance->isAdmin());
    }

    public function test_user_role_options_count(): void
    {
        $this->assertCount(4, UserRole::options());
    }

    // ── UserStatus ─────────────────────────────────────────────────────────

    public function test_user_status_values(): void
    {
        $this->assertSame(1, UserStatus::Active->value);
        $this->assertSame(2, UserStatus::Inactive->value);
        $this->assertSame(3, UserStatus::Deleted->value);
    }

    public function test_user_status_label_non_empty(): void
    {
        foreach (UserStatus::cases() as $case) {
            $this->assertNotEmpty($case->label());
        }
    }

    public function test_user_status_css_class_non_empty(): void
    {
        foreach (UserStatus::cases() as $case) {
            $this->assertNotEmpty($case->cssClass());
        }
    }

    public function test_user_status_is_active(): void
    {
        $this->assertTrue(UserStatus::Active->isActive());
        $this->assertFalse(UserStatus::Inactive->isActive());
        $this->assertFalse(UserStatus::Deleted->isActive());
    }

    public function test_user_status_options_excludes_deleted(): void
    {
        $options = UserStatus::options();
        $this->assertCount(2, $options);
        $this->assertArrayNotHasKey(UserStatus::Deleted->value, $options);
    }

    // ── UserType ───────────────────────────────────────────────────────────

    public function test_user_type_values(): void
    {
        $this->assertSame(1, UserType::Internal->value);
        $this->assertSame(2, UserType::Client->value);
        $this->assertSame(3, UserType::Automaton->value);
    }

    public function test_user_type_label(): void
    {
        $this->assertSame('Internal', UserType::Internal->label());
        $this->assertSame('Client', UserType::Client->label());
        $this->assertSame('Automaton', UserType::Automaton->label());
    }
}
