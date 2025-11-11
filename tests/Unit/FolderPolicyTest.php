<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\Folder;
use App\Models\Mailbox;
use App\Models\User;
use App\Policies\FolderPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FolderPolicyTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_any_folder(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $folder = Folder::factory()->create();
        $policy = new FolderPolicy;

        $this->assertTrue($policy->view($admin, $folder));
    }

    public function test_user_can_view_own_folder(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_USER]);
        $folder = Folder::factory()->create(['user_id' => $user->id]);
        $policy = new FolderPolicy;

        $this->assertTrue($policy->view($user, $folder));
    }

    public function test_user_can_view_folder_with_mailbox_access(): void
    {
        $mailbox = Mailbox::factory()->create();
        $user = User::factory()->create(['role' => User::ROLE_USER]);
        $user->mailboxes()->attach($mailbox->id);

        $folder = Folder::factory()->create(['mailbox_id' => $mailbox->id]);
        $policy = new FolderPolicy;

        $this->assertTrue($policy->view($user, $folder));
    }

    public function test_user_cannot_view_folder_without_mailbox_access(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_USER]);
        $folder = Folder::factory()->create();
        $policy = new FolderPolicy;

        $this->assertFalse($policy->view($user, $folder));
    }
}
