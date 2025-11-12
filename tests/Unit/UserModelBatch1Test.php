<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\Conversation;
use App\Models\Mailbox;
use App\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Tests\UnitTestCase;

class UserModelBatch1Test extends UnitTestCase
{

    #[Test]
    public function user_has_is_admin_method(): void
    {
        // Arrange
        $adminUser = new User(['role' => User::ROLE_ADMIN]);
        $regularUser = new User(['role' => User::ROLE_USER]);

        // Assert
        $this->assertTrue($adminUser->isAdmin());
        $this->assertFalse($regularUser->isAdmin());
    }

    #[Test]
    public function user_has_is_active_method(): void
    {
        // Arrange
        $activeUser = new User(['status' => User::STATUS_ACTIVE]);
        $inactiveUser = new User(['status' => User::STATUS_INACTIVE]);

        // Assert
        $this->assertTrue($activeUser->isActive());
        $this->assertFalse($inactiveUser->isActive());
    }

    #[Test]
    public function user_has_get_full_name_method(): void
    {
        // Arrange
        $user = new User([
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john@example.com',
        ]);

        // Act
        $fullName = $user->getFullName();

        // Assert
        $this->assertEquals('John Doe', $fullName);
    }

    #[Test]
    public function user_get_full_name_returns_email_when_name_empty(): void
    {
        // Arrange
        $user = new User([
            'first_name' => '',
            'last_name' => '',
            'email' => 'john@example.com',
        ]);

        // Act
        $fullName = $user->getFullName();

        // Assert
        $this->assertEquals('john@example.com', $fullName);
    }

    #[Test]
    public function user_has_full_name_accessor(): void
    {
        // Arrange
        $user = new User([
            'first_name' => 'Jane',
            'last_name' => 'Smith',
        ]);

        // Act & Assert
        $this->assertEquals('Jane Smith', $user->full_name);
    }

    #[Test]
    public function user_has_name_attribute_accessor(): void
    {
        // Arrange
        $user = new User([
            'first_name' => 'Jane',
            'last_name' => 'Smith',
            'email' => 'jane@example.com',
        ]);

        // Act & Assert
        $this->assertEquals('Jane Smith', $user->name);
    }

    #[Test]
    public function user_has_mailboxes_relationship(): void
    {
        // Arrange
        $user = User::factory()->create();
        $mailbox = Mailbox::factory()->create();

        // Act
        $user->mailboxes()->attach($mailbox->id);

        // Assert
        $this->assertTrue($user->mailboxes()->exists());
        $this->assertEquals(1, $user->mailboxes()->count());
        $this->assertTrue($user->mailboxes->contains($mailbox));
    }

    #[Test]
    public function user_has_conversations_relationship(): void
    {
        // Arrange
        $user = User::factory()->create();
        $mailbox = Mailbox::factory()->create();
        $conversation = Conversation::factory()->create([
            'user_id' => $user->id,
            'mailbox_id' => $mailbox->id,
        ]);

        // Assert
        $this->assertTrue($user->conversations()->exists());
        $this->assertEquals(1, $user->conversations()->count());
        $this->assertTrue($user->conversations->contains($conversation));
    }

    #[Test]
    public function user_has_folders_relationship(): void
    {
        // Arrange
        $user = User::factory()->create();

        // Act
        $relationship = $user->folders();

        // Assert
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class, $relationship);
    }

    #[Test]
    public function user_has_followed_conversations_relationship(): void
    {
        // Arrange
        $user = User::factory()->create();

        // Act
        $relationship = $user->followedConversations();

        // Assert
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsToMany::class, $relationship);
    }

    #[Test]
    public function user_has_threads_relationship(): void
    {
        // Arrange
        $user = User::factory()->create();

        // Act
        $relationship = $user->threads();

        // Assert
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class, $relationship);
    }

    #[Test]
    public function user_has_subscriptions_relationship(): void
    {
        // Arrange
        $user = User::factory()->create();

        // Act
        $relationship = $user->subscriptions();

        // Assert
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class, $relationship);
    }

    #[Test]
    public function user_password_is_automatically_hashed(): void
    {
        // Arrange & Act
        $user = User::factory()->create([
            'password' => 'plaintext-password',
        ]);

        // Assert
        $this->assertNotEquals('plaintext-password', $user->password);
        $this->assertTrue(\Illuminate\Support\Facades\Hash::check('plaintext-password', $user->password));
    }

    #[Test]
    public function user_role_constants_are_defined(): void
    {
        // Assert
        $this->assertEquals(1, User::ROLE_USER);
        $this->assertEquals(2, User::ROLE_ADMIN);
    }

    #[Test]
    public function user_status_constants_are_defined(): void
    {
        // Assert
        $this->assertEquals(1, User::STATUS_ACTIVE);
        $this->assertEquals(2, User::STATUS_INACTIVE);
        $this->assertEquals(3, User::STATUS_DELETED);
    }
}
